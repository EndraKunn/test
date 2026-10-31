<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Auth\LoginLockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_fifth_failed_attempt_locks_account_for_15_minutes(): void
    {
        $lockout = app(LoginLockout::class);
        $email = 'operator@example.com';

        for ($i = 1; $i <= 4; $i++) {
            $this->assertFalse($lockout->recordFailure($email));
        }

        $this->assertTrue($lockout->recordFailure($email));
        $this->assertTrue($lockout->isLocked($email));
        $this->assertSame(15, $lockout->minutesRemaining($email));
    }

    public function test_failure_window_resets_after_10_minutes(): void
    {
        $lockout = app(LoginLockout::class);
        $email = 'kasir@example.com';

        for ($i = 1; $i <= 4; $i++) {
            $this->assertFalse($lockout->recordFailure($email));
        }

        $this->travel(10)->minutes();

        $this->assertFalse($lockout->recordFailure($email));
        $this->assertFalse($lockout->isLocked($email));

        for ($i = 1; $i <= 3; $i++) {
            $this->assertFalse($lockout->recordFailure($email));
        }

        $this->assertTrue($lockout->recordFailure($email));
        $this->assertTrue($lockout->isLocked($email));
    }

    public function test_lock_expires_after_15_minutes(): void
    {
        $lockout = app(LoginLockout::class);
        $email = 'owner@example.com';

        for ($i = 1; $i <= 5; $i++) {
            $lockout->recordFailure($email);
        }

        $this->assertTrue($lockout->isLocked($email));

        $this->travel(14)->minutes();

        $this->assertTrue($lockout->isLocked($email));
        $this->assertSame(1, $lockout->minutesRemaining($email));

        $this->travel(1)->minute();

        $this->assertFalse($lockout->isLocked($email));
        $this->assertSame(0, $lockout->minutesRemaining($email));
    }

    public function test_clear_resets_failure_counter_and_lock(): void
    {
        $lockout = app(LoginLockout::class);
        $email = 'manager@example.com';

        for ($i = 1; $i <= 5; $i++) {
            $lockout->recordFailure($email);
        }

        $this->assertTrue($lockout->isLocked($email));

        $lockout->clear($email);

        $this->assertFalse($lockout->isLocked($email));
        $this->assertSame(0, $lockout->minutesRemaining($email));

        for ($i = 1; $i <= 4; $i++) {
            $this->assertFalse($lockout->recordFailure($email));
        }

        $this->assertFalse($lockout->isLocked($email));
    }

    public function test_http_login_locks_account_after_five_failures_and_unlocks_after_15_minutes(): void
    {
        $user = User::factory()->create([
            'name' => 'Lockout User',
            'email' => 'lockout@example.com',
            'password' => 'correct-password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');

            $this->assertGuest();
        }

        // Kata sandi benar tetap ditolak selama akun terkunci.
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        // Login diperbolehkan setelah penguncian berakhir.
        $this->travel(15)->minutes();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_email_wrong_password_and_inactive_account_use_same_error(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => 'correct-password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'correct-password',
            'status' => 'inactive',
            'email_verified_at' => now(),
        ]);

        $unknownResponse = $this->post(route('login.store'), [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ]);

        $wrongPasswordResponse = $this->post(route('login.store'), [
            'email' => $existingUser->email,
            'password' => 'wrong-password',
        ]);

        $inactiveResponse = $this->post(route('login.store'), [
            'email' => $inactiveUser->email,
            'password' => 'correct-password',
        ]);

        $expectedMessage = 'Surel atau kata sandi tidak sesuai.';

        $unknownResponse->assertSessionHasErrors([
            'email' => $expectedMessage,
        ]);

        $wrongPasswordResponse->assertSessionHasErrors([
            'email' => $expectedMessage,
        ]);

        $inactiveResponse->assertSessionHasErrors([
            'email' => $expectedMessage,
        ]);

        $this->assertGuest();
    }
}
