<?php

namespace App\Providers;

use App\Extensions\Filesystem\JasonFilesystem;
use App\Extensions\SmsGateways\DebugGateway;
use App\Models\Administrator;
use App\Models\Setting;
use App\Models\System;
use App\Models\User;
use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\MasterSupervisor;
use Overtrue\EasySms\EasySms;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        URL::forceHttps(config('custom.force_https'));
    }

    private function registerEasySms(): void
    {
        $this->app->singleton(EasySms::class, function() {
            $easySms = new EasySms(config('easy-sms'));

            $easySms->extend('debug', function($config) {
                return new DebugGateway($config);
            });

            return $easySms;
        });
    }

    public function boot(): void
    {
        MasterSupervisor::determineNameUsing(function() {
            return config('custom.server_id');
        });

        $this->bootMorphRelationMap();
        $this->bootRateLimiter();
        JasonFilesystem::registerFilesystem();
    }

    private function bootMorphRelationMap(): void
    {
        Relation::enforceMorphMap([
            'admin' => Administrator::class,
            'system' => System::class,
            'user' => User::class,
        ]);
    }

    private function bootRateLimiter(): void
    {
        RateLimiter::for('api', function(Request $request) {
            return Limit::perMinute(config('custom.api_rate_limit'))
                ->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('uploads', function(Request $request) {
            return Limit::perMinute(10)
                ->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('login', function(Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip());
        });
        RateLimiter::for('sms', function(Request $request) {
            return Limit::perMinute(2)
                ->by($request->ip());
        });
    }

    public function provides(): array
    {
    }
}
