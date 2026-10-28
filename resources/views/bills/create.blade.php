@extends('layouts.app')

@section('page-title', ($requestMode ?? false) ? 'Investigation Request' : 'Record New Bill')

@section('content')

<div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl shadow">

@if($requestMode ?? false)
    <p class="mb-6 text-sm text-gray-600">Register the patient and requested investigations. Payment will be received and approved at the center desk.</p>
@endif

<form method="POST" action="{{ ($requestMode ?? false) ? route('investigation-requests.store') : route('bills.store') }}">
@csrf

<!-- Patient -->
<div class="mb-6">
    <label class="block text-sm mb-2 font-medium">Patient Name</label>
    <input type="text" name="patient_name" value="{{ old('patient_name') }}" {{ ($requestMode ?? false) ? 'required' : '' }}
           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">
    @error('patient_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
<div class="mb-6">
    <label class="block text-sm mb-2 font-medium">Gender</label>
    <select name="gender" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">
        <option value="">Select Gender</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
    </select>
</div>
<div class="mb-6">
    <div class="flex items-center justify-between gap-4 mb-2">
        <label class="block text-sm font-medium">Age</label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="less_than_year" id="lessThanYear" value="1"
                   {{ old('less_than_year') ? 'checked' : '' }}
                   class="rounded border-gray-300 text-accent focus:ring-accent">
            Less than a year
        </label>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div id="ageYearsWrap">
            <label class="block text-xs text-gray-500 mb-1">Years</label>
            <input type="number" name="age_years" id="ageYears" min="0" max="150"
                   value="{{ old('age_years') }}"
                   class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">
            @error('age_years')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Months</label>
            <input type="number" name="age_months" id="ageMonths" min="0" max="11"
                   value="{{ old('age_months') }}"
                   class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">
            @error('age_months')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
        </div>
    </div>
</div>  
<div class="mb-6">
    <label class="block text-sm mb-2 font-medium">Discount (NGN)</label>
    <input type="number" step="0.01" name="discount"
           class="w-full px-4 py-3 border rounded-xl">
</div>

<!-- SERVICE SELECT -->
<div class="mb-6">

    <label for="billServiceSearch" class="mb-2 block text-sm font-medium">Search and select investigations</label>
    <div class="relative">
        <input id="billServiceSearch" type="search" role="combobox" aria-autocomplete="list" aria-controls="billServiceSearchResults" aria-expanded="false" autocomplete="off" placeholder="Start typing an investigation name..." class="w-full rounded-xl border px-4 py-3 focus:ring-2 focus:ring-accent focus:outline-none">
        <div id="billServiceSearchResults" role="listbox" class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg" style="display:none"></div>
    </div>
    <p class="mt-2 text-xs text-gray-500">Matching investigations appear as you type. Select each service once.</p>

</div>

@error('services')
    <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
@enderror

<!-- SELECTED SERVICES SUMMARY -->
<section class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4" aria-labelledby="billSelectedServicesTitle">
    <h2 id="billSelectedServicesTitle" class="font-semibold text-primary">Selected investigations</h2>
    <div id="billSelectedServices" class="mt-3 space-y-2"></div>
    <p id="billNoSelectedServices" class="mt-2 text-sm text-gray-500">No investigations selected yet.</p>
    <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-3 font-bold">
        <span>Total amount</span>
        <span id="billTotalAmount">NGN 0.00</span>
    </div>
</section>
<div id="billSelectedServiceInputs"></div>

<button type="submit"
        class="bg-primary text-white px-8 py-3 rounded-xl hover:bg-secondary transition">
    {{ ($requestMode ?? false) ? 'Register Investigation Request' : 'Save Bill' }}
</button>

</form>

</div>


<script>

const lessThanYear = document.getElementById('lessThanYear');
const ageYears = document.getElementById('ageYears');
const ageYearsWrap = document.getElementById('ageYearsWrap');
const ageMonths = document.getElementById('ageMonths');

function syncAgeInputs() {
    if (!lessThanYear || !ageYears || !ageMonths || !ageYearsWrap) {
        return;
    }

    if (lessThanYear.checked) {
        ageYears.value = '';
        ageYears.disabled = true;
        ageYearsWrap.classList.add('hidden');
        ageMonths.max = 11;
        ageMonths.placeholder = '0 - 11 months';
    } else {
        ageYears.disabled = false;
        ageYearsWrap.classList.remove('hidden');
        ageMonths.max = 11;
        ageMonths.placeholder = 'Optional months';
    }
}

lessThanYear?.addEventListener('change', syncAgeInputs);
syncAgeInputs();

(() => {
    const services = @json($serviceOptions);
    const initialIds = @json($oldServiceIds);
    const search = document.getElementById('billServiceSearch');
    const results = document.getElementById('billServiceSearchResults');
    const selectedList = document.getElementById('billSelectedServices');
    const selectedInputs = document.getElementById('billSelectedServiceInputs');
    const emptyMessage = document.getElementById('billNoSelectedServices');
    const totalDisplay = document.getElementById('billTotalAmount');
    const formatAmount = amount => `NGN ${Number(amount).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    let selected = services.filter(service => initialIds.includes(Number(service.id)));

    function renderResults() {
        results.replaceChildren();
        const term = search.value.trim().toLocaleLowerCase();
        if (!term) {
            results.style.display = 'none';
            search.setAttribute('aria-expanded', 'false');
            return [];
        }

        const matches = services.filter(service =>
            service.name.toLocaleLowerCase().includes(term) &&
            !selected.some(item => Number(item.id) === Number(service.id))
        );

        if (matches.length === 0) {
            const message = document.createElement('p');
            message.className = 'px-4 py-3 text-sm text-gray-500';
            message.textContent = 'No matching investigations found.';
            results.appendChild(message);
        } else {
            matches.forEach(service => {
                const option = document.createElement('button');
                option.type = 'button';
                option.setAttribute('role', 'option');
                option.className = 'flex w-full items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 text-left text-sm last:border-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none';
                const name = document.createElement('span');
                name.textContent = service.name;
                const price = document.createElement('span');
                price.className = 'whitespace-nowrap font-semibold text-primary';
                price.textContent = formatAmount(service.price);
                option.append(name, price);
                option.addEventListener('click', () => {
                    selected.push(service);
                    search.value = '';
                    renderSelected();
                    renderResults();
                    search.focus();
                });
                results.appendChild(option);
            });
        }

        results.style.display = 'block';
        search.setAttribute('aria-expanded', 'true');
        return matches;
    }

    function renderSelected() {
        selectedList.replaceChildren();
        selectedInputs.replaceChildren();
        let total = 0;

        selected.forEach(service => {
            total += Number(service.price);
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-sm';
            const name = document.createElement('span');
            name.className = 'font-medium text-gray-800';
            name.textContent = service.name;
            const actions = document.createElement('div');
            actions.className = 'flex items-center gap-3';
            const price = document.createElement('span');
            price.className = 'whitespace-nowrap font-semibold text-primary';
            price.textContent = formatAmount(service.price);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'font-semibold text-red-600 hover:text-red-800';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', `Remove ${service.name}`);
            remove.addEventListener('click', () => {
                selected = selected.filter(item => Number(item.id) !== Number(service.id));
                renderSelected();
                renderResults();
            });
            actions.append(price, remove);
            row.append(name, actions);
            selectedList.appendChild(row);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'services[]';
            input.value = service.id;
            selectedInputs.appendChild(input);
        });

        emptyMessage.style.display = selected.length ? 'none' : 'block';
        totalDisplay.textContent = formatAmount(total);
    }

    search.addEventListener('input', renderResults);
    search.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            const firstOption = results.querySelector('[role="option"]');
            if (firstOption) {
                event.preventDefault();
                firstOption.click();
            }
        }
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('#billServiceSearch') && !event.target.closest('#billServiceSearchResults')) {
            results.style.display = 'none';
            search.setAttribute('aria-expanded', 'false');
        }
    });

    renderSelected();
})();

</script>

@endsection
