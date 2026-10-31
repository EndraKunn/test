<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use App\Support\Auth\LoginLockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Fortify Views
        |--------------------------------------------------------------------------
        */

        Fortify::loginView(
            fn () => view('auth.login'),
        );

        Fortify::registerView(
            fn () => view('auth.register'),
        );

        Fortify::verifyEmailView(
            fn () => view('auth.verify-email'),
        );

        Fortify::confirmPasswordView(
            fn () => view('auth.confirm-password'),
        );

        Fortify::requestPasswordResetLinkView(
            fn () => view('auth.forgot-password'),
        );

        Fortify::resetPasswordView(
            fn ($request) => view('auth.reset-password', [
                'request' => $request,
            ]),
        );

        /*
        |--------------------------------------------------------------------------
        | Fortify Actions
        |--------------------------------------------------------------------------
        */

        Fortify::createUsersUsing(
            CreateNewUser::class,
        );

        Fortify::updateUserProfileInformationUsing(
            UpdateUserProfileInformation::class,
        );

        Fortify::updateUserPasswordsUsing(
            UpdateUserPassword::class,
        );

        Fortify::resetUserPasswordsUsing(
            ResetUserPassword::class,
        );

        Fortify::redirectUserForTwoFactorAuthenticationUsing(
            RedirectIfTwoFactorAuthenticatable::class,
        );

        Fortify::authenticateUsing(function (Request $request): User {
            $lockout = app(LoginLockout::class);

            $email = Str::lower(
                trim((string) $request->input(Fortify::username())),
            );

            if ($lockout->isLocked($email)) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Akun terkunci sementara karena terlalu banyak percobaan gagal. '
                        .'Coba lagi dalam '.$lockout->minutesRemaining($email).' menit.',
                ]);
            }

            $user = User::query()
                ->where('email', $email)
                ->first();

            if (
                $user !== null
                && $user->isActive()
                && Hash::check(
                    (string) $request->input('password'),
                    $user->password,
                )
            ) {
                $lockout->clear($email);

                return $user;
            }

            $lockout->recordFailure($email);

            throw ValidationException::withMessages([
                Fortify::username() => 'Surel atau kata sandi tidak sesuai.',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        */

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(20)->by(
                $request->ip() ?? 'unknown',
            );
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id'),
            );
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId())
                .'|'
                .$request->ip(),
            );
        });
    }
}
