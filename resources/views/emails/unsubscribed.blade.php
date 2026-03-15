<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed — BusyRealtor</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f4f8; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 16px; padding: 48px; max-width: 440px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .icon { width: 56px; height: 56px; background: #f0fdf4; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        h1 { margin: 0 0 8px; font-size: 22px; font-weight: 700; color: #111827; }
        p { margin: 0 0 24px; font-size: 15px; color: #6b7280; line-height: 1.6; }
        a { color: #f97316; text-decoration: none; font-weight: 600; font-size: 14px; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg width="28" height="28" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1>You've been unsubscribed</h1>
        <p>You won't receive any more platform emails from BusyRealtor. You can re-subscribe anytime from your account settings.</p>
        <a href="{{ url('/') }}">&larr; Back to BusyRealtor</a>
    </div>
</body>
</html>
