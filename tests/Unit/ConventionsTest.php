<?php

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Symfony\Component\Finder\Finder;

/*
 * The CRUD conventions from CRUD_FLOW.md, as checks that fail the build.
 *
 * These run in tests/Unit, where Pest.php binds neither TestCase nor
 * RefreshDatabase: nothing here needs a booted app or a database, only the
 * files on disk and the autoloader.
 *
 * Two of the seven conventions have no check here. "Follow Laravel standards"
 * is Pint and PHPStan, which composer ci:check already runs, and "do not
 * over-engineer" is a judgement a reviewer makes, not a string a test can find.
 *
 * The last two tests cover CRUD_FLOW.md section 1 rather than a numbered
 * convention: query logic belongs in a builder under app/QueryBuilders/, not in
 * a scope on the model.
 */

function projectPath(string $relative = ''): string
{
    return rtrim(dirname(__DIR__, 2).'/'.ltrim($relative, '/'), '/');
}

/**
 * @return list<string> paths relative to the project root
 */
function conventionFiles(string $directory, string $extension): array
{
    if (! is_dir(projectPath($directory))) {
        return [];
    }

    $files = [];

    foreach (Finder::create()->files()->in(projectPath($directory))->name("*.{$extension}") as $file) {
        $files[] = $directory.'/'.str_replace('\\', '/', $file->getRelativePathname());
    }

    sort($files);

    return $files;
}

/**
 * Every file the frontend is written by hand. `routes/`, `actions/` and
 * `wayfinder/` are wayfinder's output and `generated/` is the TypeScript
 * transformer's, so a convention about how we write code has nothing to say
 * about them.
 *
 * @return list<string>
 */
function authoredFrontendFiles(): array
{
    return array_values(array_filter(
        [...conventionFiles('resources/js', 'ts'), ...conventionFiles('resources/js', 'vue')],
        fn (string $file) => ! str_starts_with($file, 'resources/js/routes/')
            && ! str_starts_with($file, 'resources/js/actions/')
            && ! str_starts_with($file, 'resources/js/wayfinder/')
            && ! str_starts_with($file, 'resources/js/generated/'),
    ));
}

/**
 * @return list<class-string>
 */
function controllerClasses(): array
{
    return array_map(
        fn (string $file) => str_replace(
            ['app/Http/Controllers/', '/', '.php'],
            ['App\\Http\\Controllers\\', '\\', ''],
            $file,
        ),
        conventionFiles('app/Http/Controllers', 'php'),
    );
}

/**
 * Model files that are really models. Concerns/ and Scopes/ under app/Models
 * are tenancy infrastructure (BelongsToTenant, TenantScope), not models: a
 * scope that lives in the trait is not the model's own, and the scope's own
 * `where` is the enforcement, not an offender. Both are skipped here for the
 * same reason the tenancy, lacodix and spatie traits are not counted below.
 *
 * @return list<string> paths relative to the project root
 */
function modelFiles(): array
{
    return array_values(array_filter(
        conventionFiles('app/Models', 'php'),
        fn (string $file) => ! str_starts_with($file, 'app/Models/Concerns/')
            && ! str_starts_with($file, 'app/Models/Scopes/'),
    ));
}

/**
 * @return list<class-string>
 */
function modelClasses(): array
{
    return array_map(
        fn (string $file) => str_replace(
            ['app/Models/', '/', '.php'],
            ['App\\Models\\', '\\', ''],
            $file,
        ),
        modelFiles(),
    );
}

/**
 * The `scopeX` methods a model declares in its own file.
 *
 * A trait's public methods count as methods of the class using it and report
 * that class from `getDeclaringClass()`, the same reason CRUD_FLOW.md keeps
 * `AuthorizesRequests` off the base controller. So the tenancy, lacodix and
 * spatie traits would otherwise read as scopes the model wrote itself.
 * `getFileName()` still points at the trait, which is what separates the two.
 *
 * @param  class-string  $model
 * @return list<ReflectionMethod>
 */
function ownScopeMethods(string $model): array
{
    $reflection = new ReflectionClass($model);

    return array_values(array_filter(
        $reflection->getMethods(),
        fn (ReflectionMethod $method) => $method->getFileName() === $reflection->getFileName()
            && preg_match('/^scope[A-Z]/', $method->getName()) === 1,
    ));
}

/**
 * The builder class a model already points at, or null when it has none. The
 * failure message uses it to name where a scope should have gone.
 *
 * @param  class-string  $model
 */
function builderFor(string $model): ?string
{
    foreach ((new ReflectionClass($model))->getAttributes() as $attribute) {
        if ($attribute->getName() === 'Illuminate\\Database\\Eloquent\\Attributes\\UseEloquentBuilder') {
            return (string) ($attribute->getArguments()[0] ?? null);
        }
    }

    return null;
}

test('a controller exposes REST actions only', function (string $controller) {
    $rest = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    $declared = collect((new ReflectionClass($controller))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $method) => $method->class === $controller)
        ->map(fn (ReflectionMethod $method) => $method->getName())
        // __construct and __invoke are wiring, not actions.
        ->reject(fn (string $name) => str_starts_with($name, '__'))
        ->values();

    expect($declared->diff($rest)->all())->toBe([]);
})->with(controllerClasses());

test('a controller leaves searching and filtering to the model', function (string $file) {
    // Request-driven narrowing belongs in lacodix filters and model scopes, so
    // one whitelist decides what a query parameter may reach. `orderBy` is not
    // listed: a fixed ordering is not a filter and needs no ceremony.
    $handWritten = ['->where(', '->orWhere(', '->whereHas(', '->whereIn(', '->having('];

    $found = array_values(array_filter(
        $handWritten,
        fn (string $call) => str_contains((string) file_get_contents(projectPath($file)), $call),
    ));

    expect($found)->toBe([]);
})->with(conventionFiles('app/Http/Controllers', 'php'));

test('every Data object reaches TypeScript', function () {
    $files = conventionFiles('app/Data', 'php');

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        /** @var class-string $class */
        $class = str_replace(['app/Data/', '.php'], ['App\\Data\\', ''], $file);

        $attributes = (new ReflectionClass($class))->getAttributes();
        $names = array_map(fn (ReflectionAttribute $attribute) => $attribute->getName(), $attributes);

        expect($names)->toContain(TypeScript::class);
    }
});

test('the frontend reaches the API through a service', function () {
    $offenders = array_values(array_filter(
        authoredFrontendFiles(),
        fn (string $file) => ! str_starts_with($file, 'resources/js/services/')
            && preg_match('/\baxios\b/', (string) file_get_contents(projectPath($file))) === 1,
    ));

    expect($offenders)->toBe([]);
});

test('the frontend builds every URL with wayfinder', function () {
    // A quoted absolute path is a route spelled out by hand: it survives a
    // rename that the generated helpers would have caught at build time. The
    // one file below predates the rule: PasskeyVerify falls back to a quoted
    // '/dashboard' when the server answers a passkey visit without a redirect.
    $baseline = [
        'resources/js/components/PasskeyVerify.vue',
    ];

    $offenders = array_values(array_filter(
        authoredFrontendFiles(),
        fn (string $file) => ! in_array($file, $baseline, true)
            && preg_match(
                '#[\'"`]/[a-zA-Z][a-zA-Z0-9/_.-]*[\'"`]#',
                (string) file_get_contents(projectPath($file)),
            ) === 1,
    ));

    expect($offenders)->toBe([]);
});

test('a page or component stays small enough to read', function (string $file) {
    /*
     * Past this a .vue file is doing more than one job and its pieces are worth
     * naming. The baselined files predate the rule: Welcome.vue is the starter
     * kit's page and the two resource Index pages are the preview's, and none
     * has been split yet.
     */
    $limit = 300;
    $baseline = [
        'resources/js/pages/Welcome.vue',
        'resources/js/pages/transfers/Index.vue',
        'resources/js/pages/transactions/Index.vue',
    ];

    $lines = count(file(projectPath($file)) ?: []);

    expect($lines)->toBeLessThanOrEqual(in_array($file, $baseline, true) ? 450 : $limit);
})->with(conventionFiles('resources/js', 'vue'));

test('a model declares no scopes of its own', function () {
    /*
     * Query logic lives in a builder under app/QueryBuilders/, reached through
     * #[UseEloquentBuilder]. A scope puts the same thing on the model, where it
     * is invisible to anyone reading the builder and grows the model into a
     * dumping ground.
     *
     * Scopes arriving through the tenancy, lacodix and spatie traits are not the
     * model's own and are not counted, because a third-party trait cannot be
     * moved.
     */
    $offenders = [];

    foreach (modelClasses() as $model) {
        $builder = builderFor($model);

        foreach (ownScopeMethods($model) as $method) {
            $short = (new ReflectionClass($model))->getShortName();

            $offenders[] = sprintf(
                '%s::%s() must move to %s',
                $short,
                $method->getName(),
                $builder ?? 'a new App\\QueryBuilders\\'.$short.'QueryBuilder',
            );
        }
    }

    expect($offenders)->toBe([]);
});

test('a model leaves query building to its builder', function (string $file) {
    /*
     * The same list the controller check uses, for the same reason: a query
     * written here is a second definition of something the builder already
     * names, and only one of the two gets found later.
     *
     * Relations are not queries. belongsTo() and hasMany() declare structure
     * and use none of these calls, so they pass untouched.
     */
    $handWritten = [
        '->where(', '->orWhere(', '->whereHas(', '->whereDoesntHave(',
        '->whereIn(', '->whereNull(', '->having(',
    ];

    $contents = (string) file_get_contents(projectPath($file));

    $found = array_values(array_map(
        fn (string $call) => sprintf('%s writes %s) by hand', $file, $call),
        array_filter($handWritten, fn (string $call) => str_contains($contents, $call)),
    ));

    expect($found)->toBe([]);
})->with(modelFiles());
