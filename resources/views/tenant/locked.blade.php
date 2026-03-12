<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance — BusyRealtor</title>
    @vite(["resources/css/app.css"])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="text-center max-w-lg px-6">
        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-3">Under Maintenance</h1>
        <p class="text-gray-600 mb-2">{{ $message }}</p>
        <p class="text-gray-400 text-sm">Thank you for your patience.</p>
    </div>
</body>
</html>
