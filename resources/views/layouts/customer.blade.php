<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Kantin Multi Tenant' }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        .container {
            width: 100%;
            max-width: 1024px;
            margin: 0 auto;
            padding: 16px;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .navbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 60px;
        }

        .brand {
            font-size: 20px;
            font-weight: bold;
        }

        .content {
            padding: 20px 0;
        }

        button,
        input {
            min-height: 44px;
        }

        @media (min-width: 768px) {
            .container {
                padding: 24px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="container navbar-inner">
            <div class="brand">
                Kantin Multi Tenant
            </div>

            <div>
                Pelanggan
            </div>
        </div>
    </nav>

    <main class="container content">
        {{ $slot }}
    </main>

</body>
</html>