<?php

namespace App\Libs\Google;

class GoogleApiFactory
{
    /**
     * Getter for the google service.
     *
     *
     * @throws \Google\Exception|\Exception
     * @throws \ReflectionException
     */
    public static function make(string $service, string $auth_config = '', ?string $app_key = null): \Google_Service
    {
        if (class_exists($service)) {

            $auth_config = $auth_config ?: config('google_api.auth_config_path');
            if (! str_starts_with($auth_config, '/')) {
                $auth_config = base_path('disks/'.$auth_config);
            }
            $app_key = $app_key ?: config('google_api.app_key');

            // create an instance of the google client for OAuth2
            $client = new \Google_Client();

            // set config
            if ($auth_config) {
                $client->setAuthConfig($auth_config);
            } elseif ($app_key) {
                $client->setDeveloperKey($app_key);
            } else {
                throw new \Exception('Auth Config or App Key is required');
            }

            $service = new \ReflectionClass($service);

            return $service->newInstance($client);
        }

        throw new \Exception('Unsupported '.$service);
    }

    public static function makeSearchConsoleService(string $authConfig = '', ?string $app_key = null): \Google_Service_SearchConsole
    {
        $service = self::make(\Google_Service_SearchConsole::class, $authConfig, $app_key);
        $service->getClient()->addScope([\Google_Service_SearchConsole::WEBMASTERS]);

        return $service;
    }

    public static function makeIndexingService(string $authConfig = '', ?string $app_key = null): \Google_Service_Indexing
    {
        $service = self::make(\Google_Service_Indexing::class, $authConfig, $app_key);
        $service->getClient()->addScope([\Google_Service_Indexing::INDEXING]);

        return $service;
    }
}
