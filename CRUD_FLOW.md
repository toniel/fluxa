# CRUD flow

How a resource is built in this app, end to end. Written from `Category`, the
reference implementation until a real one lands — the shapes come from
`IMPLEMENTATION_PLAN.md` and the conventions below are the ones aiu-alumni
proved out and this app adopted. Read those files when a sketch here is not
enough.

| Layer         | File                                                 |
| ------------- | ---------------------------------------------------- |
| Model         | `app/Models/Category.php`                            |
| Outbound Data | `app/Data/CategoryData.php`                          |
| Inbound Data  | `app/Data/CategoryFormData.php`                      |
| Write action  | `app/Actions/UpsertCategoryAction.php`               |
| Controller    | `app/Http/Controllers/Tenant/CategoryController.php` |
| Pages         | `resources/js/pages/categories/`                     |

Work through the steps in order — each assumes the one before it exists.

---

## 1. Migration and model

The model needs `#[Fillable]`. Nothing in this app calls `Model::unguard()`, so
without it `create()` and `update()` discard every attribute and the request
still redirects with no error.

```php
#[Fillable(['name', 'type', 'icon', 'is_default'])]
#[UseEloquentBuilder(CategoryQueryBuilder::class)]
#[UsePolicy(CategoryPolicy::class)]
class Category extends Model
{
    use BelongsToTenant;
    use HasFactory;
}
```

List the columns the form submits; leave `id`, the timestamps, and `tenant_id`
out. `tenant_id` is filled for you by the `BelongsToTenant` creating hook, so no
action ever writes it, and it is not the only thing keeping a row honest: the
`is_archived`-style row flags are assertable, a `tenant_id` smuggled into the
payload is not.

Ids are `bigIncrements`, not ULIDs — a deliberate deviation from the starter
kit's `users` table, and this app stays consistent with it.

Fill in the factory's `definition()` while you are there. A generated stub
returns `[]` and fails on the first NOT NULL column, with an error that points
at the database rather than at the factory.

### Conditional queries go in a custom builder

Anything the model narrows by is query logic, not model state. Route it to a
custom Eloquent builder under `app/QueryBuilders/` and point the model at it
with `#[UseEloquentBuilder]`:

```php
class CategoryQueryBuilder extends Builder
{
    public function ofType(CategoryType $type): static
    {
        return $this->where('type', $type->value);
    }

    public function orderedForListing(): static
    {
        return $this->orderBy('name');
    }
}
```

The call site is unchanged — `Category::query()->ofType($type)` — and the model
stays limited to columns, relations, casts and the lacodix whitelists. A scope
on the model is the one-off that grows the model into a dumping ground.

Tenant isolation is not a `where` you write here: the `BelongsToTenant` trait
installs a global scope, so every query is already confined to the active
tenant and a cross-tenant row answers 404 before a controller runs. The builder
is for the tenant-aware questions — which type, which account, which month.

Both spellings count. `#[Scope]` is the Laravel 13 attribute, while this repo
writes the older `scopeX()` prefix, so the prefix is what to grep for. Grepping
only for the attribute returns nothing here and reads as a clean bill of
health. A scope arriving through a third-party trait (`BelongsToTenant`,
lacodix, spatie) is not the model's own and does not count.

`ConventionsTest` holds two lines of this rule, neither of them baselined:

- A model declares no scopes of its own. Any model that needs query logic has a
  builder to put it in, so there is nothing left for a scope to be.
- No model writes a query by hand. The `where` family is forbidden under
  `app/Models/` the same way it is in controllers, and for the same reason: a
  query spelled out in two places is found in only one of them.

Relations are not queries. `belongsTo()` and `hasMany()` declare structure and
use none of those calls, so they pass untouched.

## 2. Two Data objects, not one

The step most worth getting right, because getting it wrong fails quietly.

**`CategoryData`** — outbound. Describes a row that exists, so `id` is required.
It carries `#[TypeScript]`, which is what puts it in `generated.d.ts` for the
pages to type their props against.

**`CategoryFormData`** — inbound. Only what the editor submits, plus the
validation attributes. No `id`. It carries `#[TypeScript]` like every other Data
object, so the form page types its payload against the same shape the request
validates.

```php
class CategoryFormData extends Data
{
    public function __construct(
        #[Required, Max(50)]
        public string $name,
        #[In(CategoryType::values())]
        public CategoryType $type,
        #[Nullable]
        public ?string $icon,
        public bool $is_default = false,
    ) {}
}
```

**Type-hinting the outbound Data on `store()` is the classic bug.** A create form
has no `id` to send, so validation fails on `id`, Inertia bounces it back as a
302, and the message lands on a field with no input to display it — a save that
does nothing and says nothing:

```
STATUS: 302   SESSION ERRORS: {"id":["The id field is required."]}   COUNT: 0
```

One trap in the attributes: PHP forbids spread in an attribute argument list,
so write `#[In(CategoryType::values())]`, not `#[In(...CategoryType::values())]`.
`config/data.php` here is more forgiving than the repo this port came from: it
accepts `[DATE_ATOM, 'Y-m-d']`, so a bare `2026-10-17` passes.

## 3. An Upsert action

`store()` and `update()` write the same columns, so the write lives in one place.

```php
class UpsertCategoryAction
{
    use AsAction;

    /**
     * Create a category, or update the one passed in.
     *
     * The category to update comes from the caller (route binding), never from
     * the payload: an id in the body would let anyone overwrite another row by
     * guessing its id.
     */
    public function handle(CategoryFormData $data, ?Category $category = null): Category
    {
        if ($category instanceof Category) {
            $category->update($data->toArray());

            return $category;
        }

        return Category::create($data->toArray());
    }
}
```

`tenant_id` fills itself through the `BelongsToTenant` creating hook, so the
action does not need to know about tenancy to be safe.

Do not reach for `updateOrCreate(['id' => $data->id ?? null], ...)`. It takes the
target row from the request body, which is an authorisation hole. And keep the
write out of the controller: a second call site is how two flavors of the same
write drift apart.

## 4. Controller

```php
public function create(): Response
{
    return Inertia::render('categories/Create', [
        'types' => CategoryType::values(),
        'icons' => categoryIcons(),
    ]);
}

public function store(CategoryFormData $data, UpsertCategoryAction $action): RedirectResponse
{
    $action->handle($data);

    // Inertia::flash, not ->with(): Response::resolveFlashData() pulls from
    // Inertia's own session key, which is what initializeFlashToast reads.
    Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori disimpan.']);

    return to_route('categories.index');
}

public function edit(Category $category): Response
{
    return Inertia::render('categories/Edit', [
        'category' => CategoryData::from($category),
        'types' => CategoryType::values(),
        'icons' => categoryIcons(),
    ]);
}

public function update(CategoryFormData $data, Category $category, UpsertCategoryAction $action): RedirectResponse
{
    Gate::authorize('update', $category);

    $action->handle($data, $category);

    Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori disimpan.']);

    return to_route('categories.index');
}
```

`create()` and `edit()` must pass the same option lists, or the two pages drift.

Register with `Route::resource` inside the tenant group in
`routes/tenant.php` — after tenancy middleware is on the group, that is:

```php
Route::middleware(['auth', 'verified', 'tenant'])->group(function (): void {
    Route::resource('categories', CategoryController::class);
});
```

That is what gives Wayfinder `resources/js/wayfinder/categories/index.ts` with
`index`, `create`, `store`, `edit`, `update`, `destroy`. Resource routing and
wayfinder output belong in the same commit, or `vue-tsc` breaks on the imports
that arrived a moment too early.

Name the actions the resource does not have in `->except()`, and delete the
generated stub with them. A routed stub with an empty body is worse than no
route, since the button that eventually calls it answers 200 and changes
nothing.

Every stub method the generator left behind needs a return type — PHPStan runs at
level 7, where `missingType.return` fails the build. `: void` is fine for a
method whose body is still a `//`.

### Authorization goes in a policy

`can:` middleware on a route knows the permission and nothing else. It never
sees the row, so any rule that depends on the record cannot live there.
`Category` carries `#[UsePolicy(CategoryPolicy::class)]`, and role checks read
`TenantContext::role()` (in memory, zero queries) rather than re-fetching a
membership. Laravel registers the policy from the attribute, so there is no
`AuthServiceProvider` to update.

Three places, and which one a rule belongs to:

| The rule depends on                | Where it goes                            |
| ---------------------------------- | ---------------------------------------- |
| a permission, with no model behind | `can:` middleware on the route           |
| a permission and the row           | a policy, via `Gate::authorize()`        |
| the row's state, not the user      | the controller, with its own status code |

```php
public function update(User $user, Category $category): bool
{
    return $user->can(PermissionEnum::CategoriesManage->value)
        && app(TenantContext::class)->role() !== TenantRole::Member;
}
```

**Use `denyAsNotFound()` where a denial would leak.** A 403 on a row tells the
caller who guessed the URL that it exists. For tenant rows the global scope
already answers 404 before the controller runs — keep it that way for the
resources that are not scoped (member rows, invitations, the tenant switch),
where a 403 would confirm that a tenant or user with that id exists:

```php
return Response::denyAsNotFound();
```

**A state check is not authorization.** Whether a row is archived, expired or
cancelled is the request's business, answered with its own status code, not a
403 that sends the next person hunting through permissions.

**Do not reach for `AuthorizesRequests`.** `$this->authorizeResource()` is
shorter wiring, but a trait's public methods count as methods of the class using
it, so `authorize`, `authorizeForUser` and `authorizeResource` would land on
`app/Http/Controllers/Controller.php`, which is itself one of the classes
convention 1 reflects over. The suite goes red. `Gate::authorize()` adds no
public method, and it states the rule inside the method the reader is already
in.

## 5. Pages: one Form, two wrappers

```
resources/js/pages/categories/
├── Form.vue     every field, useForm, submit()
├── Create.vue   <CategoryForm :action="store.url()">
├── Edit.vue     <CategoryForm :action="update.url(id)" method="put" :category>
└── Index.vue
```

`Form.vue` owns the fields and the submission; the wrappers only decide where it
posts. Each wrapper stays at about twenty lines: `defineOptions({ layout })`, a
`<Head>`, and one `<CategoryForm>`.

```ts
const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'put';
        types?: string[];
        // Absent when creating, which is what leaves every field empty.
        category?: App.Data.CategoryData | null;
    }>(),
    { method: 'post', types: () => [], category: null },
);

const form = useForm({
    name: props.category?.name ?? '',
    type: props.category?.type ?? '',
    icon: props.category?.icon ?? '',
    is_default: props.category?.is_default ?? false,
});

function submit(): void {
    if (props.method === 'put') {
        form.put(props.action);

        return;
    }

    form.post(props.action);
}
```

Seed each field from `props.category?.x ?? <empty>`, using the column name the
Data object uses — `tenant_id` never appears on the form. A mismatch is a
`vue-tsc` error rather than a runtime one, so run the typecheck.

The layout title and description go in `defineOptions`; the page still needs its
own `<Head>`.

## 6. Two frontend traps

**Stored datetimes are wall-clock, not instants.** A plain `dateTime` column
drops an offset on the way in and reads back stamped `+00:00`; passing that
through `new Date()` shifts the hour by the viewer's own offset — 18:30 renders
as "1:30 am" in WIB. Convert by slicing strings, with the offset pinned to
`+00:00`. Where a resource only needs a day (this app stores `transaction_date`
and `transfer_date` as `date`), use `date` and dodge the trap entirely. The same
rule applies to any list or card that formats a stored datetime.

**`transform()` breaks `form.errors` typing.** Renaming a key on the way out means
the server reports a name the form object never had, and `FormDataErrors` does
not know it. Read those back through a cast, and say why:

```ts
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);
```

Amounts are the third quiet trap, the financial one: keep them as raw
`decimal:2` strings from the backend and format them in the frontend
(`resources/js/lib/currency.ts`), never as floats that round-trip through JSON.

## 7. Test what fails silently

Write a feature test for the things that redirect happily while doing nothing:

- a row is actually created — `assertSessionHasNoErrors()` turns a quiet 302 into
  a readable failure
- an update leaves the row count alone
- an `id` smuggled into the payload does not overwrite another row
- an invalid payload is still rejected
- each policy ability denies the role that should not have it, and the hidden
  row answers 404 rather than 403

---

# Conventions

Seven rules every resource follows. Five of them are checked by
`tests/Unit/ConventionsTest.php`, which runs inside `composer ci:check`, so
breaking one fails the pull request rather than the review.

| #   | Rule                                              | What enforces it                                    |
| --- | ------------------------------------------------- | --------------------------------------------------- |
| 1   | A controller exposes REST actions only            | `ConventionsTest`, reflection over every controller |
| 2   | Search and filter go through the lacodix traits   | `ConventionsTest`, no `where` family in controllers |
| 3   | The frontend reaches the API through a service    | `ConventionsTest`, `axios` only under `services/`   |
| 4   | Every URL comes from wayfinder                    | `ConventionsTest`, no absolute path literals        |
| 5   | Payloads are `app/Data` objects                   | `ConventionsTest` + `composer types:sync`           |
| 6   | A `.vue` file over 300 lines is split             | `ConventionsTest`, line count per file              |
| 7   | Laravel standards, efficient, no over-engineering | Pint, PHPStan, lazy-loading guard, and review       |

## 1. A controller exposes REST actions only

`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`. Nothing else,
public. A private helper that shapes a query is a scope or a filter that has not
been moved yet, and it is invisible to everything but that one controller. An
invokable controller (`__invoke`) is wiring, not an action, and passes.

## 2. Search and filter go through the lacodix traits

`Category` carries `IsSearchable`, `IsSortable` and `HasFilters`, so one
whitelist per model decides what a query parameter may reach:

```php
protected array $searchable = ['name'];
protected array $sortable = ['name' => null, 'is_default' => null];

public function filters(): array
{
    return [EnumFilter::make('type')->setQueryName('type')];
}
```

The controller then reads `->filterByQueryString()->searchByQueryString()->sortByQueryString()`
and nothing else. Hand-written `where` clauses over request input are how a sort
column becomes an injection point and a filter silently stops being applied, so
the check forbids the `where` family in controllers outright. A fixed ordering
(`orderBy`, `latest`) is not a filter and stays allowed.

Authorization is not a filter either: tenant isolation is a global scope, and
role gates live in policies, so listings and policy `view()` run the same rules.
Even the `filterFromRequest()` sketch from the old plan loses here: with lacodix
installed it would be a second filter engine, and this app does not maintain
two.

## 3. The frontend reaches the API through a service

One file per resource under `resources/js/services/`, exporting typed functions.
`axios` appears there and nowhere else, so the URL, the parameter names and the
response type live in one place instead of being restated at each call site.
Today the frontend fetches via Inertia, so there are no services to write and no
`axios` to move — the check still holds for the day an API endpoint appears.

## 4. Every URL comes from wayfinder

Import the generated helper (`import { index } from '@/wayfinder/categories'`)
and call `index.url()`. A quoted absolute path is a route spelled out by hand: it
survives a rename that the generated helpers would have caught at build time, and
it is exactly how a table ends up fetching the Inertia page instead of the API.

## 5. Payloads are `app/Data` objects

Every class in `app/Data` carries `#[TypeScript]`, so `generated.d.ts` describes
what the backend actually sends and the pages type their props against it.
`composer types:sync` regenerates the file and fails if the result differs from
what is committed, so a changed Data object cannot reach main with stale types.

## 6. A `.vue` file over 300 lines is split

Past that a page is doing more than one job. The three files that predate the
rule — `pages/Welcome.vue`, `pages/transfers/Index.vue` and
`pages/transactions/Index.vue` — are baselined in the test until they are split.
A new file gets no baseline; the fourth offender fails the build.

## 7. Laravel standards, efficient, no over-engineering

Pint and PHPStan level 7 cover the style and the types.
`Model::preventLazyLoading()` is on outside production, so an N+1 fails the suite
instead of slowing a page. The last part is a judgement: prefer the framework's
own mechanism over a new abstraction, and do not add a layer before there are two
callers for it. No test can check that, so it belongs to the reviewer.

---

# Before you push

Run `composer ci:check`. It is the same command GitHub Actions runs
(`.github/workflows/tests.yml`), and it chains lint, types, and tests:

```
composer ci:check
```

which expands to:

| Command                    | Catches                                  |
| -------------------------- | ---------------------------------------- |
| `npm run check`            | JS/Vue lint and formatting               |
| `npm run types:check`      | `vue-tsc --noEmit`                       |
| `composer lint:check`      | `pint --parallel --test`                 |
| `composer types:check`     | `phpstan analyse` (level 7)              |
| `php artisan test`         | the Pest suite, floored at 90% coverage  |
| `bin/no-zero-coverage.php` | a file under `app/` that no test reaches |

Two generator commands run **before** that, and only when their input changed:

```bash
php artisan typescript:transform          # after adding or editing a #[TypeScript] Data object
php artisan wayfinder:generate --with-form  # after changing routes, if the dev server is not running
```

`typescript:transform` writes `resources/js/generated/generated.d.ts`, which
**is committed** — so a stale file is a real diff and a real type error for the
next person.

`wayfinder:generate` writes `resources/js/wayfinder/`, which is gitignored and
regenerated by the Vite plugin on `npm run dev`; run it by hand only when you
need those files without a dev server (a fresh clone, or CI). **`--with-form` is
not optional.** `vite.config.ts` configures the plugin with `formVariants: true`,
so the pages call `store.form()` and friends; regenerating without the flag drops
those and breaks `vue-tsc` across the pages.

If lint reports fixable problems, `npm run check:fix` and `composer lint` apply
them. Re-run `composer ci:check` afterwards. Note that `npm run check` covers
Markdown as well as JS and Vue, so a new `.md` file can fail the gate on
formatting alone.

## Coverage

`composer ci:check` runs the suite with `--coverage --min=90`, so a coverage
driver is not optional. Install it once, matching your PHP version:

```bash
sudo apt install php8.5-pcov
```

Without it the suite stops at `Code coverage driver not available`. CI installs
it through `coverage: pcov` on `setup-php`, so the gate is the same in both
places.

Two scripts read the report without the floor, for when you are hunting a gap
rather than gating a push:

```bash
composer test:coverage        # per-file table in the terminal
composer test:coverage:html   # browsable report in storage/coverage (gitignored)
```

`phpunit.xml` already limits `<source>` to `app`, and nothing is excluded from
it, so the percentage is the real one rather than a number massaged by
exclusions. `storage/coverage/` is gitignored by the script that writes it.

### Why a percentage is not enough

A percentage over the whole of `app/` cannot see a whole feature arrive
untested — a feature small enough to leave the total above 90 while carrying no
tests at all. So `composer test` runs a second check over the same clover
report:

```
no-zero-coverage: every file under app/ is reached by a test (1 allowed at 0%).
```

`bin/no-zero-coverage.php` fails the build when any file under `app/` has tests
covering none of it, which is the shape an untested feature actually has. Its
`ALLOWED` list holds the files where running the code in a test would prove less
than what already runs it; each entry carries that reason, and the script says
so when an entry stops being true.

Raise `--min` when the baseline moves up for a real reason, never to chase the
number. On a small codebase a tight floor buys ceremony tests, not safety.

What the percentage will not tell you: whether each branch of an authorization
rule is exercised. A policy reaches 100% the moment one test touches each
method, while the denial path stays unproven. That matrix is checked by hand.
It also weighs every line the same — read the per-file column, not the total.

## Known baseline

None. The suite is green, so a failure in your run is yours.
