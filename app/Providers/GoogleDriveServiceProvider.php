<?php

namespace App\Providers;

use \Google_Client;
use \Google_Service_Drive;
use Illuminate\Support\ServiceProvider;

class GoogleDriveServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Google_Client::class, function ($app)
        {
            $client = new Google_Client();
            $client->setApplicationName(config('gdrive.app_name'));
            $client->setScopes([
                Google_Service_Drive::DRIVE_FILE
            ]);
            $client->setAuthConfig(base_path('oauth-credentials.json'));
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');
            return $client;
        });
    }
}
