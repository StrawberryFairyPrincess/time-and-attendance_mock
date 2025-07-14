<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
// use Laravel\Fortify\Contracts\LogoutResponse;
// use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
// use App\Http\Responses\RegisterResponse;
use App\Models\Member;
use App\Actions\Fortify\CreateNewMember;
use Laravel\Fortify\Contracts\CreatesNewUsers;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * アプリケーションの全サービスの登録
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(
            CreatesNewUsers::class,
            CreateNewMember::class
        );

        // login処理の実行回数を1分あたり10回までに制限
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
