<?php

namespace App\Providers;

use App\Components\RocketChat\Contracts\RocketChatClientContract;
use App\Components\RocketChat\RocketChatClient;
use App\Services\DailyPollService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RocketChatClient::class, function () {
            return RocketChatClient::fromConfig(config('bot'));
        });

        $this->app->alias(RocketChatClient::class, RocketChatClientContract::class);

        $this->app->singleton(DailyPollService::class, function ($app) {
            return new DailyPollService($app->make(RocketChatClientContract::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
