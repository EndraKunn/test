<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Admin Kantin' }}</title>

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

        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            background: white;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th,
        td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #f1f5f9;
        }

        @media (max-width: 767px) {
            .container {
                padding: 12px;
            }

            .header {
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <strong>Admin Kantin Multi Tenant</strong>
</header>

<main class="container">

    {{ $slot }}

</main>

</body>
</html>