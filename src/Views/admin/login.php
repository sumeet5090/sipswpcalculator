<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — SIP SWP Calculator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
            <div class="flex justify-center mb-6">
                <div class="group">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                        class="w-14 h-14 rounded-2xl shadow-lg shadow-emerald-500/30 transition-transform duration-300 group-hover:scale-105"
                        role="img" aria-label="SIP SWP Calculator Logo">
                        <rect width="24" height="24" rx="6" fill="url(#logo-grad-admin-login)" />
                        <defs>
                            <linearGradient id="logo-grad-admin-login" x1="0%" y1="100%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#059669" />
                                <stop offset="100%" stop-color="#2dd4bf" />
                            </linearGradient>
                        </defs>
                        <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round" d="M4 13l5-5 3.5 3.5 7.5-7.5" />
                        <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round" d="M15 4h5v5" />
                        <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round" stroke-opacity="0.5" d="M4 17l5-5 3.5 3.5 7.5-7.5" />
                        <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round" stroke-opacity="0.25" d="M4 21l5-5 3.5 3.5 7.5-7.5" />
                    </svg>
                </div>
            </div>
            <h1 class="text-xl font-bold text-gray-900 text-center">Admin Insights</h1>
            <p class="text-sm text-gray-400 text-center mt-1 mb-6">Enter your password to continue</p>

            <?php if (!empty($error)) : ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="password" class="block text-sm font-medium text-gray-600 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required autofocus placeholder="••••••••••"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-shadow">
                <button type="submit"
                    class="mt-4 w-full py-2.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-semibold rounded-xl hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all shadow-lg shadow-emerald-500/25">
                    Unlock Dashboard
                </button>
            </form>
        </div>
        <p class="text-center text-xs text-gray-300 mt-6">sipswpcalculator.com · Private Admin</p>
    </div>
</body>

</html>
