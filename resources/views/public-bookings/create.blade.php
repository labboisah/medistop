@extends('layouts.guest')

@section('title', 'Book an Investigation')

@section('content')
<div>
    <a href="{{ url('/') }}" class="text-sm font-semibold text-primary hover:underline">← Medistop Diagnostic Annex</a>
    <h1 class="mt-6 text-3xl font-bold text-primary">Book an Investigation</h1>
    <p class="mt-2 text-sm text-gray-600">Choose your investigations and provide your contact details. The center will confirm your request; payment is made at the center.</p>

    @if($errors->any())
        <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
            Please review the highlighted details and select at least one investigation.
        </div>
    @endif

    <form method="POST" action="{{ route('public.investigation-bookings.store') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="patient_name" class="mb-1 block text-sm font-medium">Patient name</label>
            <input id="patient_name" name="patient_name" value="{{ old('patient_name') }}" required maxlength="255" autocomplete="name" class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
            @error('patient_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="mb-1 block text-sm font-medium">Phone number</label>
            <input id="phone" name="phone" value="{{ old('phone') }}" required maxlength="30" type="tel" autocomplete="tel" class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="gender" class="mb-1 block text-sm font-medium">Gender (optional)</label>
                <select id="gender" name="gender" class="w-full rounded-lg border-gray-300 px-3 py-3 focus:border-accent focus:ring-accent">
                    <option value="">Select</option>
                    <option value="male" @selected(old('gender') === 'male')>Male</option>
                    <option value="female" @selected(old('gender') === 'female')>Female</option>
                </select>
            </div>
            <div>
                <label for="age_years" class="mb-1 block text-sm font-medium">Age (years)</label>
                <input id="age_years" name="age_years" type="number" min="0" max="150" value="{{ old('age_years') }}" class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
            </div>
        </div>
        <div>
            <label for="age_months" class="mb-1 block text-sm font-medium">Additional months (optional)</label>
            <input id="age_months" name="age_months" type="number" min="0" max="11" value="{{ old('age_months') }}" class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
        </div>

        <fieldset>
            <legend class="mb-2 text-sm font-semibold">Select investigations</legend>
            <div class="relative">
                <input id="investigationSearch" type="search" role="combobox" aria-autocomplete="list" aria-controls="investigationSearchResults" aria-expanded="false" autocomplete="off" placeholder="Type an investigation name..." class="w-full rounded-lg border-gray-300 px-4 py-3 focus:border-accent focus:ring-accent">
                <div id="investigationSearchResults" role="listbox" class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg" style="display:none"></div>
            </div>
            <p class="mt-2 text-xs text-gray-500">Search and select one or more investigations. Select each investigation only once.</p>
            @error('services')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('services.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </fieldset>

        <section aria-labelledby="selectedInvestigationsTitle" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h2 id="selectedInvestigationsTitle" class="font-semibold text-primary">Selected investigations</h2>
            <div id="selectedInvestigations" class="mt-3 space-y-2"></div>
            <p id="noSelectedInvestigations" class="mt-2 text-sm text-gray-500">No investigations selected yet.</p>
            <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-3 font-bold">
                <span>Total amount</span>
                <span id="investigationTotal">NGN 0.00</span>
            </div>
        </section>
        <div id="selectedServiceInputs"></div>

        <label class="flex items-start gap-2 text-xs text-gray-600">
            <input type="checkbox" required class="mt-0.5 rounded border-gray-300 text-accent focus:ring-accent">
            <span>I understand this is a booking request. I will visit the center to confirm the request and pay before the investigation is performed.</span>
        </label>

        <button type="submit" @disabled(empty($serviceOptions)) class="w-full rounded-lg bg-accent px-5 py-3 font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">Submit Booking Request</button>
    </form>
</div>

<script>
    (() => {
        const services = @json($serviceOptions);
        const initialIds = @json($oldServiceIds);
        const search = document.getElementById('investigationSearch');
        const results = document.getElementById('investigationSearchResults');
        const selectedList = document.getElementById('selectedInvestigations');
        const selectedInputs = document.getElementById('selectedServiceInputs');
        const emptyMessage = document.getElementById('noSelectedInvestigations');
        const totalDisplay = document.getElementById('investigationTotal');
        const formatAmount = amount => `NGN ${Number(amount).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        let selected = services.filter(service => initialIds.includes(Number(service.id)));

        function renderResults() {
            results.replaceChildren();
            const term = search.value.trim().toLocaleLowerCase();
            if (!term) {
                results.style.display = 'none';
                search.setAttribute('aria-expanded', 'false');
                return;
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
        document.addEventListener('click', event => {
            if (!event.target.closest('#investigationSearch') && !event.target.closest('#investigationSearchResults')) {
                results.style.display = 'none';
                search.setAttribute('aria-expanded', 'false');
            }
        });

        renderSelected();
    })();
</script>
@endsection
