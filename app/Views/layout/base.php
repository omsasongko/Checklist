<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'LifeQuest') ?></title>

    <!-- Bootstrap 5.3.8 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Dark Gaming Theme CSS -->
    <style>
        :root {
            --bg-dark-navy: #0a0f1a;
            --bg-charcoal: #161b22;
            --bg-surface: #1f2329;
            --primary-electric-blue: #00d4ff;
            --secondary-purple: #a855f7;
            --accent-gold: #ffd700;
            --text-light: #e2e8f0;
            --text-muted: #94a3b8;
        }

        body {
            background-color: var(--bg-dark-navy);
            color: var(--text-light);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .navbar {
            background-color: var(--bg-charcoal);
            border-bottom: 1px solid var(--bg-surface);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-electric-blue) !important;
            letter-spacing: 0.5px;
        }

        .card {
            background-color: var(--bg-surface);
            border: 1px solid var(--bg-dark-navy);
            color: var(--text-light);
        }

        .btn-primary {
            background-color: var(--primary-electric-blue);
            border-color: var(--primary-electric-blue);
            color: #000;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #33e0ff;
            color: #000;
        }

        .btn-secondary {
            background-color: var(--secondary-purple);
            border-color: var(--secondary-purple);
            color: #fff;
        }

        .btn-secondary:hover {
            background-color: #b963e8;
            color: #fff;
        }

        .text-gold {
            color: var(--accent-gold);
        }

        .bg-navy {
            background-color: var(--bg-dark-navy);
        }

        .bg-charcoal {
            background-color: var(--bg-charcoal);
        }

        .text-electric-blue {
            color: var(--primary-electric-blue);
        }

        .text-purple {
            color: var(--secondary-purple);
        }

        .form-control, .form-select {
            background-color: var(--bg-charcoal);
            border: 1px solid var(--bg-surface);
            color: var(--text-light);
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--bg-charcoal);
            border-color: var(--primary-electric-blue);
            color: var(--text-light);
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .auth-card {
            max-width: 420px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .rank-badge {
            background: linear-gradient(135deg, var(--secondary-purple), var(--primary-electric-blue));
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .xp-bar {
            height: 8px;
            background-color: var(--bg-charcoal);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .xp-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-electric-blue), var(--accent-gold));
            width: 0%;
            transition: width 0.5s ease;
        }
    </style>
</head>
<body class="bg-navy min-vh-100 d-flex flex-column">

    <?= $this->include('layout/header') ?>

    <main class="flex-fill container py-4">
        <?= $this->renderSection('content') ?>
    </main>

    <?= $this->include('layout/footer') ?>

    <!-- Bootstrap 5.3.8 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
        // Toast auto-dismiss
        document.addEventListener('DOMContentLoaded', function() {
            var toastElList = document.querySelectorAll('.toast');
            var toastList = [...toastElList].map(function(toastEl) {
                return new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
            });
            toastList.forEach(function(toast) { toast.show(); });
        });
    </script>
</body>
</html>