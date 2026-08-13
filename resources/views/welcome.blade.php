<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>Annex System | Diagnostic Record Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .hero-bg {
            background-image: url('/images/hero.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: right center;
        }

        .gradient-overlay {
            background: linear-gradient(
                90deg,
                rgba(15,45,92,0.95) 0%,
                rgba(15,45,92,0.85) 40%,
                rgba(15,45,92,0.6) 70%,
                rgba(15,45,92,0.1) 100%
            );
        }

        #nprogress .bar {
            background: #16A34A !important;
            height: 3px;
        }
    </style>
</head>

<body class="bg-lightbg dark:bg-primary transition-all duration-500">

<!-- NAVBAR -->
<nav class="bg-white dark:bg-secondary shadow-lg fixed w-full z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <h1 class="text-2xl font-extrabold text-primary dark:text-white">
            MEDISTOP CLINICAL <span class="text-accent">DIAGNOSTIC ANNEX</span>
        </h1>

        <div class="flex items-center gap-6">
            <!-- Dark Mode Toggle -->
            <button onclick="toggleDarkMode()" 
                class="bg-gray-200 dark:bg-gray-700 p-2 rounded-lg">
                🌙
            </button>

            <!-- Login Button -->
            <button onclick="openModal()" 
                class="bg-accent text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-600 transition">
                Login
            </button>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="min-h-screen flex items-center hero-bg relative pt-20">

    <div class="absolute inset-0 gradient-overlay"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-10 items-center">

        <div data-aos="fade-right" class="text-white">
            <h1 class="text-5xl md:text-6xl font-extrabold leading-tight">
                Diagnostic  
                <span class="text-accent">Record Management</span>
            </h1>

            <p class="mt-6 text-lg text-gray-200">
                A secure and automated system for managing daily diagnostic services,
                    staff allocation, expense tracking, and financial transparency.
            </p>

            <div class="mt-8 flex gap-4">
                <button onclick="openModal()" 
                    class="bg-accent px-8 py-4 rounded-xl font-bold hover:scale-105 transition">
                    Access System
                </button>

                <a href="#features" 
                    class="border border-white px-8 py-4 rounded-xl hover:bg-white hover:text-primary transition">
                    Explore Features
                </a>
            </div>

            @if($showServerControl ?? false)
            <div class="mt-8 inline-flex flex-col items-start gap-2 rounded-xl border border-white/30 bg-white/10 px-5 py-4 text-white shadow-xl backdrop-blur">
                <a id="serverControlUrl"
                    href="#"
                    target="_blank"
                    class="hidden text-sm font-semibold text-accent hover:underline">
                </a>

                <span id="serverControlMessage"
                    class="hidden max-w-md text-sm font-medium">
                </span>

                <button id="serverControlButton"
                    type="button"
                    onclick="toggleServerConnection()"
                    class="inline-flex items-center gap-2 bg-accent text-white px-5 py-3 rounded-lg font-semibold hover:bg-green-600 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg id="serverControlIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M4.93 12.93a10 10 0 0114.14 0M1.394 9.393a15 15 0 0121.212 0" />
                    </svg>
                    <span id="serverControlLabel">Connect</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Animated Stats -->
        

    </div>
</section>

<!-- SERVICES SECTION -->
<section id="services" class="py-24 bg-lightbg">

    <div class="max-w-7xl mx-auto px-6 text-center">

        <!-- Section Title -->
        <h2 class="text-4xl font-extrabold text-primary mb-4">
            Diagnostic Services
        </h2>

        <p class="text-gray-600 max-w-2xl mx-auto mb-16">
            Our system supports structured recording and financial tracking
            across all core diagnostic departments.
        </p>

        <!-- Services Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-10">

            <!-- Radiological -->
            <div data-aos="fade-up"
                class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-2 border-t-4 border-secondary">

                <div class="text-5xl mb-4 text-secondary">🩻</div>

                <h3 class="text-xl font-bold text-primary mb-3">
                    Radiological
                </h3>

                <p class="text-gray-600 mb-6 text-sm">
                    Ultrasound, X-ray, Doppler and advanced imaging services
                    with automated revenue allocation.
                </p>

               
            </div>

            <!-- Echo -->
            <div data-aos="fade-up" data-aos-delay="100"
                 class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-2 border-t-4 border-accent">

                <div class="text-5xl mb-4 text-accent">🫀</div>

                <h3 class="text-xl font-bold text-primary mb-3">
                    Echocardiography (ECHO)
                </h3>

                <p class="text-gray-600 mb-6 text-sm">
                    Cardiac imaging procedures with automated 40% staff
                    share calculation and daily reporting.
                </p>

                
            </div>

            <!-- ECG -->
            <div data-aos="fade-up" data-aos-delay="200"
                 class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-2 border-t-4 border-primary">

                <div class="text-5xl mb-4 text-primary">📈</div>

                <h3 class="text-xl font-bold text-primary mb-3">
                    Electrocardiography (ECG)
                </h3>

                <p class="text-gray-600 mb-6 text-sm">
                    Electrical heart monitoring integrated with real-time
                    financial computation.
                </p>

            </div>

            <!-- Laboratory -->
            <div data-aos="fade-up" data-aos-delay="300"
                 class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-2 border-t-4 border-secondary">

                <div class="text-5xl mb-4 text-secondary">🧪</div>

                <h3 class="text-xl font-bold text-primary mb-3">
                    Laboratory Services
                </h3>

                <p class="text-gray-600 mb-6 text-sm">
                    Comprehensive lab test tracking with expense recording
                    and automated daily financial summaries.
                </p>

                
            </div>

        </div>

    </div>

</section>

<!-- FEATURES SECTION -->
<section id="features" class="py-24 bg-white dark:bg-secondary transition">

    <div class="max-w-7xl mx-auto px-6 text-center">

        <h2 class="text-4xl font-bold text-primary dark:text-white mb-16">
            Core Capabilities
        </h2>

        <div class="grid md:grid-cols-3 gap-10">

            <div data-aos="zoom-in"
                class="bg-lightbg dark:bg-primary p-10 rounded-2xl shadow hover:shadow-2xl transition">

                <div class="text-4xl text-accent mb-4">💰</div>
                <h3 class="text-xl font-bold mb-3">Automatic Allocation</h3>
                <p>Every service automatically calculates 40% staff share.</p>
            </div>

            <div data-aos="zoom-in" data-aos-delay="150"
                class="bg-lightbg dark:bg-primary p-10 rounded-2xl shadow hover:shadow-2xl transition">

                <div class="text-4xl text-accent mb-4">📊</div>
                <h3 class="text-xl font-bold mb-3">Daily Reports</h3>
                <p>Automated end-of-day email financial reporting.</p>
            </div>

            <div data-aos="zoom-in" data-aos-delay="300"
                class="bg-lightbg dark:bg-primary p-10 rounded-2xl shadow hover:shadow-2xl transition">

                <div class="text-4xl text-accent mb-4">🔒</div>
                <h3 class="text-xl font-bold mb-3">Full Audit Trail</h3>
                <p>Track who entered every transaction with timestamps.</p>
            </div>

        </div>

    </div>
</section>

<!-- LOGIN MODAL -->
<div id="loginModal" 
     class="fixed inset-0 z-[9999] hidden flex items-center justify-center">

    <!-- Background Overlay -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
         onclick="closeModal()"></div>

    <!-- Modal Box -->
    <div class="relative bg-white p-10 rounded-2xl w-full max-w-md shadow-2xl z-10">

        <button onclick="closeModal()" 
                class="absolute top-4 right-5 text-xl font-bold text-gray-500 hover:text-red-500">
            ×
        </button>

        <h2 class="text-2xl font-bold mb-6 text-primary">
            System Login
        </h2>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <input type="email" name="email" placeholder="Email"
                class="w-full mb-4 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">

            <input type="password" name="password" placeholder="Password"
                class="w-full mb-6 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">

            <button type="submit"
                class="w-full bg-accent text-white py-3 rounded-lg font-bold hover:bg-green-600 transition">
                Login
            </button>
        </form>

    </div>
</div>

<!-- FOOTER -->
<footer class="bg-primary text-white text-center py-6">
    © {{ date('Y') }} Annex System Record Management
</footer>

<!-- SCRIPTS -->
<script>
    @if($showServerControl ?? false)
    const serverControl = {
        connected: false,
        busy: false,
        statusUrl: "{{ route('server-control.status') }}",
        connectUrl: "{{ route('server-control.connect') }}",
        disconnectUrl: "{{ route('server-control.disconnect') }}",
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    };

    function updateServerButton(payload = {}) {
        serverControl.connected = Boolean(payload.connected);

        const button = document.getElementById('serverControlButton');
        const label = document.getElementById('serverControlLabel');
        const icon = document.getElementById('serverControlIcon');
        const url = document.getElementById('serverControlUrl');
        const message = document.getElementById('serverControlMessage');

        button.disabled = serverControl.busy;
        label.textContent = serverControl.busy
            ? 'Please wait...'
            : (serverControl.connected ? 'Disconnect' : 'Connect');

        if (serverControl.connected && payload.url) {
            url.href = payload.url;
            url.textContent = payload.url;
            url.classList.remove('hidden');
        } else {
            url.href = '#';
            url.textContent = '';
            url.classList.add('hidden');
        }

        if (payload.message) {
            message.textContent = payload.message;
            message.classList.remove('hidden', 'text-red-600', 'text-gray-600', 'text-accent');
            message.classList.add(serverControl.connected ? 'text-accent' : 'text-red-600');
        } else {
            message.textContent = '';
            message.classList.add('hidden');
        }

        button.classList.toggle('bg-accent', serverControl.connected);
        button.classList.toggle('hover:bg-green-600', serverControl.connected);
        button.classList.toggle('bg-primary', !serverControl.connected);
        button.classList.toggle('hover:bg-secondary', !serverControl.connected);
        icon.classList.toggle('text-white', serverControl.connected);
    }

    async function refreshServerStatus() {
        try {
            const response = await fetch(serverControl.statusUrl, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            updateServerButton(payload);
        } catch (error) {
            updateServerButton({
                connected: false,
                message: 'Unable to read server status.',
            });
        }
    }

    async function waitForServerConnection(attempts = 8) {
        for (let index = 0; index < attempts; index++) {
            await new Promise(resolve => setTimeout(resolve, 700));

            try {
                const response = await fetch(serverControl.statusUrl, {
                    headers: { 'Accept': 'application/json' },
                });
                const payload = await response.json();
                updateServerButton(payload);

                if (payload.connected) {
                    return true;
                }
            } catch (error) {
                // Keep polling briefly; the server may still be starting.
            }
        }

        return false;
    }

    async function toggleServerConnection() {
        const requestedConnect = !serverControl.connected;
        serverControl.busy = true;
        updateServerButton({ connected: serverControl.connected });
        let shouldRefresh = false;
        let latestPayload = { connected: serverControl.connected };

        try {
            const response = await fetch(serverControl.connected ? serverControl.disconnectUrl : serverControl.connectUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': serverControl.csrf,
                },
            });

            const payload = await response.json();
            latestPayload = payload;
            updateServerButton(payload);
            shouldRefresh = response.ok;
        } catch (error) {
            latestPayload = {
                connected: serverControl.connected,
                message: 'Unable to change server connection. Please check the system console.',
            };
            updateServerButton(latestPayload);
        } finally {
            serverControl.busy = false;
            if (requestedConnect && !latestPayload.connected) {
                const connected = await waitForServerConnection();

                if (! connected) {
                    updateServerButton(latestPayload);
                }
            } else if (shouldRefresh) {
                if (latestPayload.connected) {
                    updateServerButton(latestPayload);
                } else {
                    refreshServerStatus();
                }
            } else {
                updateServerButton(latestPayload);
            }
        }
    }
    @endif

    function toggleDarkMode() {
        document.documentElement.classList.toggle('dark');
    }

    function openModal() {
        document.getElementById('loginModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('loginModal').classList.add('hidden');
    }

    // Animated Counters
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const speed = 50;
            const increment = target / speed;

            if(count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(updateCount, 40);
            } else {
                counter.innerText = target;
            }
        };
        updateCount();
    });

    @if($showServerControl ?? false)
    refreshServerStatus();
    @endif
</script>

</body>
</html>
