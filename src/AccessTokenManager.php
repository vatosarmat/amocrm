<?php

namespace App;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class AccessTokenManager
{
    private string $filePath;

    private AmoCRMApiClient $apiClient;

    public function __construct(AmoCRMApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->filePath = PROJECT_ROOT.'/.runtime/'.md5($_SERVER['AMOCRM_CLIENT_ID'].'.json');
    }

    public function get(): AccessTokenInterface
    {
        $accessToken = null;
        if (file_exists($this->filePath) && $tokenString = file_get_contents($this->filePath)) {
            $tokenAr = json_decode($tokenString, true);
            if (
                isset($tokenAr['access_token'])
                && isset($tokenAr['refresh_token'])
                && isset($tokenAr['expires_in'])
                && isset($tokenAr['base_domain'])
            ) {
                $accessToken = new AccessToken([
                    'access_token' => $tokenAr['access_token'],
                    'refresh_token' => $tokenAr['refresh_token'],
                    'expires_in' => $tokenAr['expires_in'],
                    'base_domain' => $tokenAr['base_domain'],
                ]);
            }
        }

        if (! $accessToken) {
            $oauthClient = $this->apiClient->getOAuthClient($_SERVER['AMOCRM_CLIENT_ID']);
            $oauthClient->setBaseDomain($_SERVER['AMOCRM_BASE_DOMAIN']);
            $accessToken = $oauthClient->getAccessTokenByCode($_SERVER['AMOCRM_CLIENT_CODE']);

            $tokenAr = [
                'access_token' => $accessToken->getToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'expires_in' => $accessToken->getExpires(),
                'base_domain' => $this->apiClient->getAccountBaseDomain(),
            ];

            file_put_contents($this->filePath, json_encode($tokenAr));
        }

        return $accessToken;
    }
}
