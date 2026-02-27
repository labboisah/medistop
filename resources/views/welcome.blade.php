<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>Annex System | Diagnostic Record Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

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
        </div>

        <!-- Animated Stats -->
        <div data-aos="fade-left" 
             class="bg-white/10 backdrop-blur-xl p-10 rounded-2xl shadow-2xl text-white">

            <div class="grid grid-cols-2 gap-6 text-center">

                <div>
                    <h2 class="text-4xl font-bold counter" data-target="{{App\Models\User::find(1)->finance()['monthRevenue']}}">0</h2>
                    <p class="text-gray-300">This Month's Revenue</p>
                </div>

                <div>
                    <h2 class="text-4xl font-bold counter" data-target="{{App\Models\User::find(1)->finance()['monthExpenses']}}">0</h2>
                    <p class="text-gray-300">This Month's Expenses</p>
                </div>

                <div>
                    <h2 class="text-4xl font-bold counter" data-target="{{App\Models\User::find(1)->finance()['monthPayments']}}">0</h2>
                    <p class="text-gray-300">This Month Payments</p>
                </div>

                <div>
                    <h2 class="text-4xl font-bold counter" data-target="{{App\Models\User::find(1)->finance()['monthProfit']}}">0</h2>
                    <p class="text-gray-300">This Month's Profits</p>
                </div>

            </div>

        </div>

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
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init();

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
</script>

</body>
</html>