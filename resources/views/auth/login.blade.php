<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kantin Multi Tenant</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">

            <h1 class="text-2xl font-bold text-center mb-2">
                Kantin Multi Tenant
            </h1>

            <p class="text-center text-gray-500 mb-6">
                Silakan login untuk melanjutkan
            </p>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block mb-1 font-medium">
                        Email
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full border rounded-lg px-3 py-2"
                    >
                </div>

                <div class="mb-4">
                    <label for="password" class="block mb-1 font-medium">
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="w-full border rounded-lg px-3 py-2"
                    >
                </div>

                <div class="mb-6">
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                        >
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-2 rounded-lg font-medium"
                >
                    Login
                </button>
            </form>

        </div>
    </div>

</body>
</html>
