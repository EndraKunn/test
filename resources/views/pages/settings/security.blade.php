<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Features;
use Livewire\Component;

new class extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $twoFactorEnabled = false;

    public function mount(): void
    {
        $user = Auth::user();

        /*
         * Jika 2FA pernah dimulai tetapi belum dikonfirmasi,
         * batalkan konfigurasi 2FA yang menggantung.
         */
        if (
            $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at === null
        ) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ])->save();
        }

        $this->twoFactorEnabled =
            $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        $user = Auth::user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError(
                'current_password',
                'Password saat ini salah.',
            );

            return;
        }

        $user->password = $this->password;
        $user->save();

        $this->reset([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    }
};
?>

<div>
    <h1>Security Settings</h1>

    @if (Features::enabled(Features::passkeys()))
        <section>
            <h2>Passkeys</h2>

            <p>No passkeys yet</p>

            <p>Manage your passkeys for passwordless sign-in</p>

            <p>Add a passkey to sign in without a password</p>
        </section>
    @endif

    @if (Features::enabled(Features::twoFactorAuthentication()))
        <section>
            <h2>Two-factor authentication</h2>

            @if (! $twoFactorEnabled)
                <button type="button">
                    Enable 2FA
                </button>
            @endif
        </section>
    @endif

    <section>
        <h2>Update password</h2>

        <form wire:submit="updatePassword">
            <div>
                <label for="current_password">
                    Current password
                </label>

                <input
                    id="current_password"
                    type="password"
                    wire:model="current_password"
                />

                @error('current_password')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password">
                    New password
                </label>

                <input
                    id="password"
                    type="password"
                    wire:model="password"
                />

                @error('password')
                    <span>{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password_confirmation">
                    Confirm new password
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    wire:model="password_confirmation"
                />
            </div>

            <button type="submit">
                Update password
            </button>
        </form>
    </section>
</div>