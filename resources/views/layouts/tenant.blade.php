<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Dashboard Tenant' }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: #0f172a;
            color: white;
            padding: 24px 16px;
            transition: width 0.2s ease;
            flex-shrink: 0;
        }

        .sidebar.collapsed {
            width: 72px;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 20px;
        }

        .sidebar h2 {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
        }

        .toggle-button {
            min-width: 44px;
            min-height: 44px;
            border: 0;
            border-radius: 6px;
            background: #1e293b;
            color: white;
            cursor: pointer;
            font-size: 18px;
        }

        .sidebar a {
            display: block;
            padding: 12px;
            margin-bottom: 6px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar a:hover {
            background: #1e293b;
        }

        .sidebar.collapsed h2,
        .sidebar.collapsed a {
            font-size: 0;
        }

        .sidebar.collapsed a::before {
            content: "•";
            font-size: 20px;
        }

        .main {
            flex: 1;
            padding: 24px;
            min-width: 0;
        }

        @media (max-width: 767px) {
            .layout {
                display: block;
            }

            .sidebar {
                width: 100%;
            }

            .sidebar.collapsed {
                width: 100%;
            }

            .main {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar" id="tenantSidebar">

        <div class="sidebar-header">
            <h2>Kantin</h2>

            <button
                type="button"
                class="toggle-button"
                onclick="toggleSidebar()"
                aria-label="Toggle sidebar"
            >
                ☰
            </button>
        </div>

        <nav>
            <a href="#">Dashboard</a>
            <a href="#">Pesanan</a>
            <a href="#">Menu</a>
            <a href="#">Laporan</a>
        </nav>

    </aside>

    <main class="main">
        {{ $slot }}
    </main>

</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('tenantSidebar');

        sidebar.classList.toggle('collapsed');
    }
</script>

</body>
</html>