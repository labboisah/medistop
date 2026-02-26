<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Annex System')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0F2D5C',
                        secondary: '#1E4E8C',
                        accent: '#16A34A',
                        lightbg: '#E6F0FA'
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-lightbg">

    <div class="min-h-screen grid md:grid-cols-2">

        <!-- LEFT SIDE (FORM AREA) -->
        <div class="flex items-center justify-center p-10 bg-white">

            <div class="w-full max-w-md">
                @yield('content')
            </div>

        </div>

        <!-- RIGHT SIDE (IMAGE BACKGROUND STYLE) -->
        <div class="hidden md:flex relative items-center justify-center">

            <!-- Background Image -->
            <div class="absolute inset-0 bg-cover bg-center"
                style="background-image: url('/images/hero.png');">
            </div>

            <!-- Dark Overlay -->
            <div class="absolute inset-0 bg-gradient-to-br 
                        from-primary/90 
                        via-secondary/85 
                        to-primary/95 
                        backdrop-blur-sm">
            </div>

            <!-- Content -->
            <div class="relative z-10 text-center text-white px-12 max-w-md">

                <h2 class="text-3xl font-bold">
                    Annex System
                </h2>

                <p class="mt-4 text-gray-200">
                    Transparent Diagnostic Record Management
                </p>

                <div class="mt-6 text-sm text-gray-300">
                    Automated 40% Allocation • Expense Tracking • Daily Reporting
                </div>

            </div>

        </div>

    </div>

</body>
</html>