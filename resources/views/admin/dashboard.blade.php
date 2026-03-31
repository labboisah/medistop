

<div class="grid md:grid-cols-4 gap-6 mb-10">

    <!-- Categories -->
    <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-sm text-gray-500">Categories</h3>
        <p class="text-3xl font-bold text-primary mt-2">
            {{ App\Models\Category::all()->count() }}
        </p>
    </div>

    <!-- Services -->
    <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-sm text-gray-500">Services</h3>
        <p class="text-3xl font-bold text-secondary mt-2">
            {{ App\Models\Service::all()->count() }}
        </p>
    </div>

    <!-- Revenue -->
    <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-sm text-gray-500">Today's Gross Revenue</h3>
        <p class="text-3xl font-bold text-accent mt-2">
            ₦{{ number_format(auth()->user()->finacialCalculation()['todayGross'], 2) }}
        </p>
    </div>

    <!-- Net -->
    <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-sm text-gray-500">Net Balance</h3>
        <p class="text-3xl font-bold text-blue-700 mt-2">
            ₦{{ number_format(auth()->user()->finacialCalculation()['todayNetRevenue'], 2) }}
        </p>
    </div>

</div>

<!-- Quick Actions -->
<div class="grid md:grid-cols-3 gap-6">

    <a href="{{ route('admin.categories.index') }}"
       class="bg-primary text-white p-6 rounded-xl shadow hover:bg-secondary transition">
        <h3 class="font-bold text-lg">Manage Categories</h3>
        <p class="text-sm mt-2 text-gray-200">
            Create and organize diagnostic categories.
        </p>
    </a>

    <a href="{{ route('admin.services.index') }}"
       class="bg-secondary text-white p-6 rounded-xl shadow hover:bg-primary transition">
        <h3 class="font-bold text-lg">Manage Services</h3>
        <p class="text-sm mt-2 text-gray-200">
            Configure diagnostic services and pricing.
        </p>
    </a>

    <a href="{{route('admin.users.index')}}"
       class="bg-accent text-primary p-6 rounded-xl shadow hover:bg-green-600 transition">
        <h3 class="font-bold text-lg">Manage Users</h3>
        <p class="text-sm mt-2">
            Manage Users Information.
        </p>
    </a>

</div>


