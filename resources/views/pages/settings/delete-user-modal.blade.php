<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser()
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (! Hash::check($this->password, $user->password)) {
            $this->addError(
                'password',
                'Password yang diberikan salah.',
            );

            return;
        }
Auth::logout();
$user->delete();

$this->redirect('/');
    }
};
?>

<div>
    <h2>Hapus Akun</h2>

    <p>
        Tindakan ini akan menghapus akun kamu secara permanen.
    </p>

    <form wire:submit="deleteUser">
        <div>
            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                wire:model="password"
                required
                autocomplete="current-password"
            />

            @error('password')
                <span>{{ $message }}</span>
            @enderror
        </div>

        <button type="submit">
            Hapus Akun
        </button>
    </form>
</div>