<?php

namespace App;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;

class AccessTokenStore
{
    const FIELDS = ['access_token', 'refresh_token', 'expires_in', 'base_domain'];

    public static \mysqli $mysqli;

    public static function get(): AccessToken
    {
        $result = self::$mysqli->execute_query('SELECT '.implode(',', self::FIELDS).' FROM access_token');

        return new AccessToken($result->fetch_assoc());
    }

    public static function acquire(AmoCRMApiClient $apiClient)
    {
        $oauthClient = $apiClient->getOAuthClient($_SERVER['AMOCRM_CLIENT_ID']);
        $accessToken = $oauthClient->getAccessTokenByCode($_SERVER['AMOCRM_CLIENT_CODE']);

        self::$mysqli->execute_query('INSERT INTO access_token ('.implode(',', self::FIELDS).') VALUES (?,?,?,?)',
            [$accessToken->getToken(), $accessToken->getRefreshToken(), $accessToken->getExpires(), $apiClient->getAccountBaseDomain()]);
    }
}
