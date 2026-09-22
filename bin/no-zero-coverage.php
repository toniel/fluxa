<?php

/*
 * Fail the build when a file under app/ has no test touching it at all.
 *
 * The floor in `composer test` is a percentage over the whole of app/, which on
 * a codebase this size is a blunt instrument: a feature small enough to be
 * merged with no tests would still leave the total above 90. This reads the
 * same run's clover report and asks a different question, one file at a time.
 *
 * Usage: php bin/no-zero-coverage.php storage/coverage/clover.xml
 */

/**
 * Files allowed to sit at 0%, each with the reason a test would prove less than
 * what already runs the code. Add to this list only for that reason.
 */
const ALLOWED = [
    // configure() runs only when the transformer runs. `composer types:sync`
    // executes it for real and fails on a changed generated.d.ts, which is a
    // stronger check than a test asserting the same wiring back to itself.
    'app/Providers/TypeScriptTransformerServiceProvider.php',
];

$root = dirname(__DIR__);
$report = $argv[1] ?? '';

if (! is_file($report)) {
    fwrite(STDERR, "no-zero-coverage: no clover report at '{$report}'.\n");
    fwrite(STDERR, "Run it after `php artisan test --coverage-clover=<path>`.\n");

    exit(1);
}

$xml = simplexml_load_file($report);

if ($xml === false) {
    fwrite(STDERR, "no-zero-coverage: '{$report}' is not readable XML.\n");

    exit(1);
}

$untouched = [];
$covered = [];

foreach ($xml->xpath('//file') ?: [] as $file) {
    $metrics = $file->metrics;

    if ($metrics === null) {
        continue;
    }

    $path = str_replace($root.'/', '', (string) $file['name']);
    $statements = (int) $metrics['statements'];

    if ($statements === 0) {
        continue;
    }

    if ((int) $metrics['coveredstatements'] === 0) {
        $untouched[] = $path;

        continue;
    }

    $covered[] = $path;
}

// An allowlist entry that is now covered, or gone, is a line nobody has to keep
// reading. Said out loud rather than failed on: coverage went up, not down.
$stale = array_intersect(ALLOWED, $covered);

foreach ($stale as $path) {
    fwrite(STDERR, "no-zero-coverage: {$path} is covered now, so drop it from ALLOWED.\n");
}

$offenders = array_values(array_diff($untouched, ALLOWED));

if ($offenders === []) {
    $count = count($untouched);

    echo 'no-zero-coverage: every file under app/ is reached by a test'.
        ($count > 0 ? " ({$count} allowed at 0%)" : '').".\n";

    exit(0);
}

sort($offenders);

fwrite(STDERR, "\nno-zero-coverage: no test reaches these files.\n\n");

foreach ($offenders as $path) {
    fwrite(STDERR, "  {$path}\n");
}

fwrite(STDERR, "\nWrite one, or add the file to ALLOWED in bin/no-zero-coverage.php\n");
fwrite(STDERR, "with the reason a test would prove less than what already runs it.\n");

exit(1);
