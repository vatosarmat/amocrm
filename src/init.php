<?php

define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT.'/vendor/autoload.php';

function log_json($v, $suffix = "\n")
{
    $file = PROJECT_ROOT.'/.runtime/output';
    if (is_string($v)) {
        $str = $v;
    } else {
        $str = json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return file_put_contents($file, $str.$suffix, FILE_APPEND);
}

function log_error(mysqli_sql_exception $e)
{
    log_json([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}

function log_all()
{
    log_json("\n-------------------------------------------------");
    log_json('GET');
    log_json($_GET, "\n\n");
    log_json('POST');
    log_json($_POST, "\n\n");
}

function stdout($v, $suffix = "\n")
{
    if (is_string($v)) {
        $str = $v;
    } else {
        if (defined('APP_DEBUG')) {
            $json_str = json_encode($v, JSON_UNESCAPED_SLASHES);
            $temp_file = PROJECT_ROOT.'/.runtime/output';
            file_put_contents($temp_file, $json_str);
            $str = shell_exec("jq --color-output < $temp_file");
        } else {
            $str = json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }

    file_put_contents('php://stdout', $str.$suffix);
}

$dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
$env = $dotenv->load();
$dotenv->required('AMOCRM_CLIENT_ID')->notEmpty();
$dotenv->required('AMOCRM_CLIENT_SECRET')->notEmpty();
$dotenv->required('AMOCRM_CLIENT_CODE')->notEmpty();
$dotenv->required('AMOCRM_REDIRECT_URL')->notEmpty();
$dotenv->required('AMOCRM_BASE_DOMAIN')->notEmpty();

$dotenv->required('MYSQL_HOST')->notEmpty();
$dotenv->required('MYSQL_USER')->notEmpty();
$dotenv->required('MYSQL_PASSWORD')->notEmpty();
$dotenv->required('MYSQL_DATABASE_NAME')->notEmpty();

$mysqli = new \mysqli($_SERVER['MYSQL_HOST'], $_SERVER['MYSQL_USER'], $_SERVER['MYSQL_PASSWORD'], $_SERVER['MYSQL_DATABASE_NAME']);

\App\AccessTokenStore::$mysqli = $mysqli;
\App\AmoLeadsStore::$mysqli = $mysqli;
\App\AmoContactsStore::$mysqli = $mysqli;

return $dotenv;
