<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Password</title>
</head>
<body>
    <div>
        <h1>Konfirmasi Password</h1>

        <p>
            Silakan masukkan password kamu untuk melanjutkan.
        </p>

        @if ($errors->any())
            <div>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div>
                <label for="password">Password</label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit">
                Konfirmasi
            </button>
        </form>
    </div>
</body>
</html>