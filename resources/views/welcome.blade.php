<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LazyRoom API</title>
    <meta name="description" content="LazyRoom: the Laravel backend of a multi-vendor ordering app with wallet payments. Try it live with demo accounts.">
    <style>
        :root { --bg: #fafafa; --surface: #fff; --border: #e4e4e7; --text: #52525b; --text-h: #09090b; --accent: #0e7490; --accent-bg: rgba(14, 116, 144, .08); }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0b0d12; --surface: #12151c; --border: #232733; --text: #a1a1aa; --text-h: #f4f4f5; --accent: #22d3ee; --accent-bg: rgba(34, 211, 238, .1); }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 16px/1.6 system-ui, 'Segoe UI', Roboto, sans-serif; }
        .wrap { max-width: 880px; margin: 0 auto; padding: 48px 16px 64px; }
        h1 { color: var(--text-h); font-size: 40px; margin: 0; letter-spacing: -.02em; }
        h2 { color: var(--text-h); font-size: 20px; margin: 40px 0 12px; }
        .lead { font-size: 18px; margin: 8px 0 24px; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .btn { display: inline-block; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 1px solid var(--border); color: var(--text-h); background: var(--surface); }
        .btn.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        @media (prefers-color-scheme: dark) { .btn.primary { color: #0b0d12; } }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 4px 16px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { text-align: left; padding: 10px 4px; border-bottom: 1px solid var(--border); vertical-align: top; }
        tr:last-child td { border-bottom: 0; }
        th { color: var(--text-h); font-size: 14px; }
        code { font-family: ui-monospace, Consolas, monospace; font-size: 14px; background: var(--accent-bg); color: var(--text-h); padding: 2px 6px; border-radius: 4px; }
        a { color: var(--accent); }
        ol, ul { padding-left: 20px; margin: 0; }
        li { margin: 6px 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
        .grid .card { padding: 14px 16px; }
        .grid b { color: var(--text-h); display: block; margin-bottom: 4px; }
        .note { font-size: 14px; margin-top: 12px; }
        footer { margin-top: 48px; font-size: 14px; }
        @media (max-width: 560px) { h1 { font-size: 32px; } .hide-sm { display: none; } }
    </style>
</head>
<body>
<main class="wrap">
    <h1>LazyRoom API</h1>
    <p class="lead">The Laravel backend of a multi-vendor ordering app: customers order meals and groceries from campus suppliers and pay from an in-app wallet. The mobile app was built by a teammate in Flutter; this repository is the backend.</p>
    <div class="actions">
        <a class="btn primary" href="{{ url('/docs/api') }}">Try the API in your browser →</a>
        <a class="btn" href="https://github.com/hasan123dayoub-del/LazyRoom">Source on GitHub</a>
    </div>

    <h2>Demo accounts</h2>
    <div class="card">
        <table>
            <tr><th>Role</th><th>Email</th><th>Password</th><th class="hide-sm">Can</th></tr>
            <tr><td>Customer</td><td><code>customer@lazyroom.com</code></td><td><code>password</code></td><td class="hide-sm">browse products, place, cancel and complete orders (wallet: 100 USD)</td></tr>
            <tr><td>Supplier</td><td><code>supplier@lazyroom.com</code></td><td><code>password</code></td><td class="hide-sm">manage products 1&ndash;4, accept or reject orders</td></tr>
            <tr><td>Admin</td><td><code>admin@lazyroom.com</code></td><td><code>password</code></td><td class="hide-sm">manage accounts, ban users, deposit and withdraw balances</td></tr>
        </table>
    </div>

    <h2>Try the order flow</h2>
    <div class="card" style="padding: 14px 16px">
        <ol>
            <li>In the <a href="{{ url('/docs/api') }}">interactive docs</a>, send <code>POST /api/login</code> as the customer (it is pre-filled) and copy the <code>token</code>.</li>
            <li>Paste it as the <b>Bearer token</b> and send <code>POST /api/orders</code> (pre-filled with products 1 and 4). Note the order <code>id</code>.</li>
            <li>Log in as the supplier and send <code>POST /api/orders/{id}/accept</code>.</li>
            <li>Log in as the customer again and send <code>POST /api/orders/{id}/complete</code>. Check <code>GET /api/balance</code>: the money moved from the customer to the supplier.</li>
        </ol>
    </div>

    <h2>What's inside</h2>
    <div class="grid">
        <div class="card"><b>Order state machine</b>Orders move from pending to accepted, rejected, cancelled or complete, and stock is reserved and restored along the way.</div>
        <div class="card"><b>Safe wallet payments</b>Completing an order runs in a database transaction with row locks, so a double click or a retry never charges twice.</div>
        <div class="card"><b>Measured under load</b>On MySQL, 20 parallel completions of one order charge it exactly once, and 50 parallel orders never overdraw a wallet.</div>
        <div class="card"><b>Roles</b>Sanctum tokens and role middleware for customers, suppliers and admins.</div>
        <div class="card"><b>Tested</b>Feature tests for the payment flow run on every push with GitHub Actions.</div>
        <div class="card"><b>Documented</b>OpenAPI docs generated from the code, plus a Postman collection.</div>
    </div>

    <p class="note">Free hosting: the server sleeps when idle, so the first request can take up to a minute. This demo runs on SQLite, which has no row locks; the concurrency results above were measured on MySQL. The demo database is reset on every restart.</p>

    <footer>Built by <a href="https://hasan123dayoub-del.github.io/portfolio/">Hasan Dayoub</a>.</footer>
</main>
</body>
</html>
