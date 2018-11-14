#!/usr/bin/env php
<?php

namespace Gitphp;

require_once __DIR__ . '/bootstrap.php';

function error($msg) {
    fwrite(STDERR, $msg . "\n");
    exit(1);
}

function var_dump_stderr(...$var) {
    ob_start();
    foreach ($var as $v) var_dump($v);
    fwrite(STDERR, ob_get_clean() . "\n");
}

dl('pcntl.so');

if (!extension_loaded('pcntl')) error('no pcntl');

if (($user = $argv[1] ?? null) === null) {
    error('no user');
}
$original_command = explode(' ', getenv('SSH_ORIGINAL_COMMAND'));
const READ = 1;
const WRITE = 2;
$commands = [
    'git-upload-pack' => READ,
    'git-upload-archive' => READ,
    'git-receive-pack' => WRITE,
];
if (!isset($commands[$original_command[0]])) {
    error('bad command');
}
if (($repo = $original_command[1] ?? null) === null) {
    error('no repo');
}

$repo = trim($repo, '\'');

// filter beginning and ending dots in path components
$repo = implode(
    '/',
    array_filter(
        array_map(
            function ($e) { return trim($e, '.'); },
            explode('/', $repo)
        )
    )
);

$project_root = \lib\Context::getConfig('repositories');

$full_path = $project_root . '/' . $repo;
$has_suffix = function ($string, $suffix) { return substr($string, -strlen($suffix)) === $suffix; };
if (!is_dir($full_path)) {
    if ($has_suffix($full_path, '.git')) {
        error("no such repo: $full_path");
    }
    $full_path .= '.git';
    if (!is_dir($full_path)) {
        error('no such repo2');
    }
}

$User = new User();
$user_to_id = $User->getAssoc('SELECT user, id FROM #TBL_USER#');

$id = $user_to_id[$user] ?? null;

$check_access = false;
if ($id == 1) {
    $check_access = true;
} else if ($id == 7 && in_array($repo, ['gitphp', 'lib'])) {
    $check_access = true;
}

if (!$check_access) {
//    error('access denied');
}

$git_shell_path = array_reduce(['/usr/local/bin/git-shell', '/usr/bin/git-shell'], function ($c, $e) {
    return file_exists($e) ? $e : $c;
});
if (!$git_shell_path) error('no shell');

pcntl_exec($git_shell_path, ['-c', $original_command[0] . ' ' . escapeshellarg($full_path)]);
error('exec failed');
