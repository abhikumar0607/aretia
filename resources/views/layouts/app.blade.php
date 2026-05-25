<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aretia')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #f4f6f9;
            color: #1a1d26;
            min-height: 100vh;
        }
        .navbar {
            background: #1e293b;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 { font-size: 1.25rem; font-weight: 600; }
        .navbar .meta { font-size: 0.875rem; opacity: 0.85; }
        .navbar form { display: inline; }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-outline {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
        }
        .btn-outline:hover { background: rgba(255,255,255,0.1); }
        .container { max-width: 960px; margin: 2rem auto; padding: 0 1.5rem; }
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .card h2 { margin-bottom: 0.5rem; font-size: 1.5rem; }
        .card p { color: #64748b; line-height: 1.6; }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge-superadmin { background: #7c3aed; color: #fff; }
        .badge-admin { background: #2563eb; color: #fff; }
        .badge-client { background: #059669; color: #fff; }
        .badge-analyst { background: #d97706; color: #fff; }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .auth-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .auth-card { width: 100%; max-width: 400px; }
        label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.6rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        input:focus { outline: 2px solid #3b82f6; border-color: #3b82f6; }
        .form-group { margin-bottom: 0.5rem; }
        .checkbox { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
        .demo-users {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.8rem;
            color: #64748b;
        }
        .demo-users ul { list-style: none; margin-top: 0.5rem; }
        .demo-users li { padding: 0.2rem 0; }
    </style>
</head>
<body>
    @auth
        <header class="navbar">
            <div>
                @include('partials.brand-logo', ['class' => 'site-logo-app', 'width' => 140, 'height' => 36])
                <span class="meta">{{ auth()->user()->name }} &middot; {{ auth()->user()->role->label() }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline">Logout</button>
            </form>
        </header>
    @endauth

    @yield('content')
</body>
</html>
