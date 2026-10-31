<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated');
    }
};
?>

<div>
    <h1>Profile Settings</h1>

    <form wire:submit="updateProfileInformation">
        <div>
            <label for="name">Nama</label>

            <input
                id="name"
                type="text"
                wire:model="name"
                required
            />

            @error('name')
                <span>{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="email">Email</label>

            <input
                id="email"
                type="email"
                wire:model="email"
                required
            />

            @error('email')
                <span>{{ $message }}</span>
            @enderror
        </div>

        <button type="submit">
            Simpan Perubahan
        </button>
    </form>
</div>