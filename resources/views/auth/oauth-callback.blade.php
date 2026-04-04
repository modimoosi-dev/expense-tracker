<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signing in...</title>
    <style>
        body { background: #4f46e5; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; color: white; font-family: sans-serif; }
        .spinner { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { opacity: 0.8; font-size: 14px; }
    </style>
</head>
<body>
    <div class="spinner"></div>
    <p>Signing you in...</p>
    <script>
        @if($deepLink)
            window.location.href = "{{ $deepLink }}";
            setTimeout(() => window.close(), 3000);
        @else
            // Polling mode: app is polling — just close this tab after a brief moment
            setTimeout(() => window.close(), 1500);
        @endif
    </script>
</body>
</html>
