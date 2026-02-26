<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Annex System')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
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

<body class="bg-lightbg dark:bg-primary transition-all duration-300">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-primary text-white hidden md:flex flex-col">

        <!-- Logo -->
        <div class="p-6 text-2xl font-extrabold border-b border-secondary">
            ANNEX <span class="text-accent">SYSTEM</span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-6 space-y-4 text-sm">

            <a href="/dashboard"
               class="block px-4 py-2 rounded-lg hover:bg-secondary transition">
                🏠 Dashboard
            </a>

            <a href=""
               class="block px-4 py-2 rounded-lg hover:bg-secondary transition">
                🩻 Services
            </a>

            <a href="/records"
               class="block px-4 py-2 rounded-lg hover:bg-secondary transition">
                📄 Service Records
            </a>

            <a href="/expenses"
               class="block px-4 py-2 rounded-lg hover:bg-secondary transition">
                💸 Expenses
            </a>

        </nav>

        <!-- Bottom User Info -->
        <div class="p-6 border-t border-secondary">

            <div class="text-sm">
                <div class="font-semibold">
                    {{ auth()->user()->name }}
                </div>

                <div class="text-xs text-gray-300 mt-1">
                    Role: 
                    <span class="text-accent font-bold uppercase">
                        {{ auth()->user()->role }}
                    </span>
                </div>
            </div>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button class="w-full bg-accent text-primary py-2 rounded-lg font-semibold hover:bg-green-500 transition">
                    Logout
                </button>
            </form>

        </div>
    </aside>


    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col">

        <!-- TOP NAVBAR -->
        <header class="bg-white dark:bg-secondary shadow-md px-6 py-4 flex justify-between items-center">

            <h1 class="text-xl font-bold text-primary dark:text-white">
                @yield('page-title', 'Dashboard')
            </h1>

            <div class="flex items-center gap-4">

                <!-- Dark Mode Toggle -->
                <button onclick="toggleDarkMode()" 
                    class="bg-gray-200 dark:bg-gray-700 px-3 py-1 rounded-lg">
                    🌙
                </button>

            </div>

        </header>

        <!-- PAGE CONTENT -->
        <main class="flex-1 p-6">

            @yield('content')

        </main>

    </div>

</div>


<!-- DARK MODE SCRIPT -->
<script>
    function toggleDarkMode() {
        document.documentElement.classList.toggle('dark');
    }
</script>

</body>
</html>