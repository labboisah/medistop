<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Annex System')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
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
    <style>
        #nprogress .bar {
            background: #16A34A !important; /* accent green */
            height: 3px;
        }
    </style>
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

           {{-- DASHBOARD --}}
            <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-4 py-2 rounded-lg transition
            {{ request()->routeIs('dashboard') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                <!-- Dashboard Icon -->
                <svg class="w-5 h-5 text-accent"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 3h7v7H3V3zm11 0h7v11h-7V3zM3 14h7v7H3v-7zm11 4h7v3h-7v-3z"/>
                </svg>

                <span>Dashboard</span>
            </a>


            @if(auth()->user()->role == 'user')

            {{-- PAYMENTS --}}
            <a href="{{route('payments.index')}}"
            class="flex items-center gap-3 px-4 py-2 rounded-lg transition
            {{ request()->routeIs('payments.*') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                <!-- Payment Icon -->
                <svg class="w-5 h-5 text-accent"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 7h18M3 10h18M5 14h6"/>
                </svg>

                <span>Payments</span>
            </a>


            {{-- BILLS --}}
            <a href="{{route('bills.index')}}"
            class="flex items-center gap-3 px-4 py-2 rounded-lg transition
            {{ request()->routeIs('bills.*') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                <!-- Bill Icon -->
                <svg class="w-5 h-5 text-accent"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6M9 8h6M5 3h14a2 2 0 012 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 012-2z"/>
                </svg>

                <span>Bills</span>
            </a>


            {{-- EXPENSES --}}
            <a href="{{route('expenses.index')}}"
            class="flex items-center gap-3 px-4 py-2 rounded-lg transition
            {{ request()->routeIs('expenses.*') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                <!-- Expense Icon -->
                <svg class="w-5 h-5 text-accent"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 9V7a4 4 0 10-8 0v2m-2 0h12l1 12H4L5 9z"/>
                </svg>

                <span>Expenses</span>
            </a>

            <a href="{{ route('refunds.index') }}"
            class="flex items-center gap-3 px-4 py-2 rounded-lg transition
            {{ request()->routeIs('refunds.*') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                <!-- Refund Icon -->
                <svg class="w-5 h-5 text-accent"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m-6 5a9 9 0 110-18 9 9 0 010 18z"/>
                </svg>

                <span>Refunds</span>
            </a>

            @endif

            @if(auth()->user()->role == 'admin')
                <a href="{{route('admin.categories.index')}}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-secondary transition">
                    
                    <!-- Category Icon (Folder) -->
                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>

                    <span>Categories</span>
                </a>


                <a href="{{route('admin.services.index')}}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-secondary transition">

                    <!-- Services Icon (Clipboard List) -->
                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5h6m-6 4h6m-6 4h4m5 7H6a2 2 0 01-2-2V5a2 2 0 012-2h3l1-1h4l1 1h3a2 2 0 012 2v13a2 2 0 01-2 2z"/>
                    </svg>

                    <span>Services</span>
                </a>


                <a href="{{route('admin.users.index')}}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-secondary transition">

                    <!-- Users Icon -->
                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m8-4a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>

                    <span>Users</span>
                </a>


                <a href="{{route('admin.finances.index')}}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-secondary transition">

                    <!-- Finance Icon (Chart Bar) -->
                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 3v18h18M9 17V9m4 8V5m4 12v-6"/>
                    </svg>

                    <span>Finance</span>
                </a>
                <a href="{{ route('admin.reports.index') }}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg transition
                {{ request()->routeIs('reports.*') ? 'bg-secondary text-white border-l-4 border-accent' : 'hover:bg-secondary' }}">

                    <!-- Report / Analytics Icon -->
                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 17v-6m4 6V7m4 10v-4M3 3v18h18"/>
                    </svg>

                    <span>Reports</span>
                </a>
            @endif

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
            @if(session('success'))
            <div id="toast"
                class="fixed top-5 right-5 bg-accent text-white px-6 py-3 rounded-xl shadow-lg">
                {{ session('success') }}
            </div>

            <script>
                setTimeout(() => {
                    document.getElementById("toast").remove();
                }, 3000);
            </script>
            @endif


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



<script>
document.addEventListener("DOMContentLoaded", function () {

    /* ==========================================
       CONFIGURE NPROGRESS (TOP LOADING BAR)
    ========================================== */

    if (typeof NProgress !== 'undefined') {
        NProgress.configure({
            showSpinner: false,
            trickleSpeed: 120
        });
    }

    /* ==========================================
       SMOOTH PAGE FADE-IN EFFECT
    ========================================== */

    document.body.classList.add("opacity-0");

    setTimeout(() => {
        document.body.classList.remove("opacity-0");
        document.body.classList.add("transition-opacity", "duration-300", "opacity-100");
    }, 50);


    /* ==========================================
       INTERNAL LINK NAVIGATION HANDLER
    ========================================== */

    document.querySelectorAll("a").forEach(link => {

        const href = link.getAttribute("href");

        const isInternal =
            link.hostname === window.location.hostname &&
            href &&
            !href.startsWith("#") &&
            !link.hasAttribute("target");

        if (isInternal) {
            link.addEventListener("click", function () {
                if (typeof NProgress !== 'undefined') {
                    NProgress.start();
                }
            });
        }
    });

    window.addEventListener("load", function () {
        if (typeof NProgress !== 'undefined') {
            NProgress.done();
        }
    });


    /* ==========================================
       FORM SUBMISSION SPINNER
    ========================================== */

    document.querySelectorAll("form").forEach(form => {

        form.addEventListener("submit", function () {

            const button = form.querySelector("button[type='submit']");

            if (button && !button.disabled) {

                button.disabled = true;

                const originalText = button.innerHTML;

                button.innerHTML = `
                    <span class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24">
                            <circle class="opacity-25"
                                    cx="12" cy="12" r="10"
                                    stroke="currentColor"
                                    stroke-width="4">
                            </circle>
                            <path class="opacity-75"
                                  fill="currentColor"
                                  d="M4 12a8 8 0 018-8v8H4z">
                            </path>
                        </svg>
                        Processing...
                    </span>
                `;

                // Safety fallback (re-enable button after 5s if something fails)
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }, 5000);
            }

        });

    });

});

</script>
@yield('scripts')
</body>
</html>