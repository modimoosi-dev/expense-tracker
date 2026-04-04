<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signing in...</title>
    <style>
        body { background: #4f46e5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .spinner { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="spinner"></div>
    <script>
        window.location.href = "{{ $deepLink }}";
        // Fallback: close window after 3s if deep link doesn't fire
        setTimeout(() => window.close(), 3000);
    </script>
</body>
</html>
