<?php

$dotenv = require_once dirname(__DIR__).'/src/init.php';

$dotenv->required('AMOCRM_CLIENT_CODE')->notEmpty();

function mysqli_exec_sync(mysqli $handle, string $sql)
{
    $handle->multi_query($sql);

    do {
        if ($result = $handle->store_result()) {
            $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
    } while ($handle->next_result());
}

mkdir(PROJECT_ROOT.'/.runtime');
$sql = file_get_contents(__DIR__.'/create_tables.sql');
mysqli_exec_sync(\App\AccessTokenStore::$mysqli, $sql);

$apiClient = new \AmoCRM\Client\AmoCRMApiClient($_SERVER['AMOCRM_CLIENT_ID'], $_SERVER['AMOCRM_CLIENT_SECRET'],
    $_SERVER['AMOCRM_REDIRECT_URL']);
$apiClient->setAccountBaseDomain($_SERVER['AMOCRM_BASE_DOMAIN']);
\App\AccessTokenStore::acquire($apiClient);
