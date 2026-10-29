<?php

declare(strict_types=1);

// Workspace entry point. The canonical engineering gate lives in the Toolkit.
$root = dirname(__DIR__);
$workspace = dirname($root);
$adapter = $workspace . '/Developer-Toolkit/scripts/release-preflight/php_sdk.py';
if (!is_file($adapter) || !is_file($workspace . '/AGENTS.md')) {
    fwrite(STDERR, "composer check requires the Raims engineering workspace and its sibling Developer-Toolkit checkout. No checks were run.\n");
    exit(1);
}
ini_set('log_errors', '0');
ini_set('display_errors', 'stderr');
ini_set('display_startup_errors', '1');
ini_set('html_errors', '0');
$bootstrapDirectory = $workspace . '/.tmp/raims-php-sdk/bootstrap-' . getmypid() . '-' . hrtime(true);
if (!mkdir($bootstrapDirectory, 0777, true) && !is_dir($bootstrapDirectory)) {
    fwrite(STDERR, "Unable to create the workspace validation log directory.\n");
    exit(1);
}
ini_set('error_log', $bootstrapDirectory . '/php-errors.log');
ini_set('log_errors', '1');
foreach (array_slice($argv, 1) as $argument) {
    if ($argument !== '--prepare-tools') {
        fwrite(STDERR, "Supported option: --prepare-tools.\n");
        exit(1);
    }
}
$command = [getenv('RAIMS_PYTHON') ?: 'python', $adapter, '--source', $root, '--workspace', $workspace];
if (in_array('--prepare-tools', $argv, true)) {
    $command[] = '--prepare-tools';
}
$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $root, null, ['bypass_shell' => true]);
if (!is_resource($process)) {
    fwrite(STDERR, "Unable to start the engineering check. Configure Python in PATH or RAIMS_PYTHON.\n");
    exit(1);
}
exit(proc_close($process));
