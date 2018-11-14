<?php

namespace Gitphp;

require_once __DIR__ . '/bootstrap.php';

if (!extension_loaded('posix')) die("load posix ext\n");

class_exists(\lib\Log::class);

$git_user = 'git';
$homedir = posix_getpwnam($git_user);
$homedir = $homedir['dir'] ?? null;
if ($homedir === null) die("no homedir for $git_user\n");

$authorized_keys_file = $homedir . '/.ssh/authorized_keys';
$command_path = __DIR__ . '/ssh_serve.php';

$User = new User();
\lib\Log::add(\lib\Log::C_SQL, \lib\Log::L_DEBUG, new \lib\LogConsole());
$rows = $User->getAll('SELECT * FROM #TBL_USER#');
var_dump($rows);

function ssh_format($command_path, $user, $key) {
    $ssh_command = "command=\"$command_path $user\",no-port-forwarding,no-agent-forwarding,no-X11-forwarding,no-pty $key";
    return $ssh_command;
}

file_put_contents($authorized_keys_file, '');
foreach ($rows as $row) {
    $ssh_command = ssh_format($command_path, $row['user'], $row['pubkey']);
    $res = file_put_contents($authorized_keys_file, $ssh_command . "\n", FILE_APPEND);
    printf("wrote %s = %s\n", $authorized_keys_file, var_export($res, 1));
}

sleep(60);
