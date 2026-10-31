<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
</head>
<body>
    <div>
        <h1>Verifikasi Email</h1>

        <p>
            Sebelum melanjutkan, silakan verifikasi alamat email kamu
            melalui tautan yang telah kami kirimkan ke email kamu.
        </p>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit">
                Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit">
                Logout
            </button>
        </form>
    </div>
</body>
</html>