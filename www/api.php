<?php

namespace Gitphp;

require_once __DIR__ . '/../bootstrap.php';

if (isset($_GET['a'])) {
    // todo make user management pages
    class_exists(\lib\Log::class);
    \lib\StatSlow::enabled(true);
    \lib\Log::add(\lib\Log::C_SQL, \lib\Log::L_DEBUG, new \lib\LogScreen());
    $User = new User();
    $u = $_POST['u'] ?? null;
    $key = $_POST['key'] ?? null;
    if ($u && $key) {
        $res = $User->query('INSERT INTO #TBL_USER# SET user = #user#, pubkey = #pubkey#', ['user' => $u, 'pubkey' => $key]);
        var_dump($res);
    }
    ?>
<form method="post" action="?a=1">
    <input name="u" />
    <textarea name="key"></textarea>
    <input type="submit" />
</form>
<?php

    $rows = $User->getAll('SELECT * FROM #TBL_USER#');
    echo '<pre>';
    var_dump($rows);
    echo '</pre>';
    \lib\StatSlow::displayErrors();
    die;
}

class_exists(\lib\Log::class);
\lib\StatSlow::enabled(true);



$LogSs = new \lib\LogSs('kek');
\lib\Log::add(\lib\Log::C_SQL, \lib\Log::L_DEBUG, $LogSs);
\lib\Log::add(\lib\Log::C_CTRL, \lib\Log::L_DEBUG, $LogSs);

$Request = new \lib\Request();
$Request->init();
$Response = new \lib\Response();

$Controller = new Controller($Request, $Response);
$Controller->run();
