<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Annex System')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    <aside id="appSidebar" class="fixed inset-y-0 left-0 z-50 flex h-screen w-64 -translate-x-full flex-col overflow-y-auto bg-primary text-white transition-transform duration-200 ease-in-out md:static md:h-auto md:translate-x-0 md:shrink-0" aria-label="Main navigation" aria-hidden="true">

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

            @if(auth()->user()->role == 'staff')
                <a href="{{ route('staff.results.index') }}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg transition
                {{ request()->routeIs('staff.results.index') || request()->routeIs('staff.results.entry') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/>
                    </svg>

                    <span>{{ (auth()->user()->staff_type ?? '') === 'radiologist' ? 'Imaging Worklist' : 'Result Entry' }}</span>
                </a>

                <a href="{{ route('staff.results.reports') }}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg transition
                {{ request()->routeIs('staff.results.reports') || request()->routeIs('staff.results.print') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 17v-6m4 6V7m4 10v-4M3 3v18h18"/>
                    </svg>

                    <span>Report</span>
                </a>

                <a href="{{ route('staff.results.commission') }}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg transition
                {{ request()->routeIs('staff.results.commission') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z"/>
                    </svg>

                    <span>Commission</span>
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

                <a href="{{ route('admin.expenses.index') }}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg transition
                {{ request()->routeIs('admin.expenses.*') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

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

                <a href="{{ route('admin.salaries.index') }}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg transition
                {{ request()->routeIs('admin.salaries.*') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z"/>
                    </svg>

                    <span>Salary</span>
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

                <a href="{{ route('admin.staff-reports.index') }}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg transition
                {{ request()->routeIs('admin.staff-reports.*') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 17v-6m4 6V7m4 10v-4M5 20h14M5 4h14"/>
                    </svg>

                    <span>Staff Reports</span>
                </a>

                <a href="{{ route('admin.staff-performance.index') }}"
                class="flex items-center gap-3 px-4 py-2 rounded-lg transition
                {{ request()->routeIs('admin.staff-performance.*') ? 'bg-secondary text-white' : 'hover:bg-secondary' }}">

                    <svg class="w-5 h-5 text-accent"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 17v-6m4 6V7m4 10v-4M5 20h14M5 4h14"/>
                    </svg>

                    <span>Performance Audit</span>
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

    <button id="sidebarBackdrop" type="button" class="fixed inset-0 z-40 hidden bg-black/50 md:hidden" aria-label="Close navigation menu" tabindex="-1"></button>


    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col">

        <!-- TOP NAVBAR -->
        <header class="bg-white dark:bg-secondary shadow-md px-6 py-4 flex justify-between items-center">

            <div class="flex items-center gap-3">
                <button id="sidebarToggle" type="button" class="rounded-lg p-2 text-primary hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-accent dark:text-white dark:hover:bg-gray-700" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-xl font-bold text-primary dark:text-white">
                    @yield('page-title', 'Dashboard')
                </h1>
            </div>

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
            @if(session('success') || session('warning'))
            <div id="toast"
                class="fixed top-5 right-5 {{ session('warning') ? 'bg-yellow-500' : 'bg-accent' }} text-white px-6 py-3 rounded-xl shadow-lg max-w-md">
                {{ session('success') ?? session('warning') }}
            </div>

            <script>
                setTimeout(() => {
                    const toast = document.getElementById("toast");
                    if (toast) {
                        toast.remove();
                    }
                }, 4000);
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

    const sidebar = document.getElementById('appSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    if (sidebar && sidebarToggle && sidebarBackdrop) {
        const isDesktop = () => window.innerWidth >= 768;
        let sidebarOpen = isDesktop();

        const updateSidebar = () => {
            if (isDesktop()) {
                sidebar.classList.remove('-translate-x-full');
                sidebarBackdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                sidebar.setAttribute('aria-hidden', sidebarOpen ? 'false' : 'true');
            } else {
                sidebar.classList.remove('md:hidden');
                sidebar.classList.toggle('-translate-x-full', !sidebarOpen);
                sidebarBackdrop.classList.toggle('hidden', !sidebarOpen);
                document.body.classList.toggle('overflow-hidden', sidebarOpen);
                sidebar.setAttribute('aria-hidden', sidebarOpen ? 'false' : 'true');
            }

            sidebarToggle.setAttribute('aria-expanded', sidebarOpen ? 'true' : 'false');
            sidebarToggle.setAttribute('aria-label', sidebarOpen ? 'Close navigation menu' : 'Open navigation menu');
        };

        const closeSidebar = () => {
            sidebarOpen = false;
            if (isDesktop()) {
                sidebar.classList.add('md:hidden');
            }
            updateSidebar();
        };

        const toggleSidebar = () => {
            sidebarOpen = !sidebarOpen;
            sidebar.classList.add('-translate-x-full');
            if (sidebarOpen) sidebar.classList.remove('md:hidden');
            else if (isDesktop()) sidebar.classList.add('md:hidden');
            updateSidebar();
        };

        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarBackdrop.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
            if (!isDesktop()) closeSidebar();
        }));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && sidebarOpen && !isDesktop()) closeSidebar();
        });
        window.addEventListener('resize', updateSidebar);
        updateSidebar();
    }

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
