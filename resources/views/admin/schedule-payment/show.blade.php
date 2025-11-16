@extends('layouts.base')

@push('styles')
<style>
    .metric-card { transition: all 0.3s ease; border: 1px solid #e4e6ea; }
    .metric-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
</style>
@endpush

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
      
      

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ translate('Schedule Payment Details') }}
                </h1>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                    <span>{{ translate('Checkout') }}: #{{ str_pad($schedulePayment->checkout->id ?? 0, 6, '0', STR_PAD_LEFT) }}</span>
                    <span class="text-gray-400">•</span>
                    <span>{{ translate('Due') }}: {{ optional($schedulePayment->due_date)->format('M d, Y') }}</span>
                    <span class="text-gray-400">•</span>
                    <span>{{ translate('Status') }}:
                        <span class="badge badge-sm {{ $schedulePayment->payment_status === 'paid' ? 'badge-success' : ($schedulePayment->payment_status === 'late' ? 'badge-danger' : 'badge-warning') }}">
                            {{ ucfirst($schedulePayment->payment_status) }}
                        </span>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                @if($schedulePayment->checkout)
                <a class="btn btn-sm btn-light" href="{{ route('checkouts.show', $schedulePayment->checkout) }}">
                    <i class="ki-filled ki-package"></i>
                    {{ translate('View Checkout') }}
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="container-fixed">
        <!-- Top metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-7.5 mb-5">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="text-sm text-gray-600">{{ translate('Instalment Amount') }}</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($schedulePayment->instalment_amount, 2) }} {{ translate('SAR') }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ translate('Principal') }}: {{ number_format($schedulePayment->principle_amount, 2) }}
                    </div>
                </div>
            </div>
            <div class="card metric-card">
                <div class="card-body">
                    <div class="text-sm text-gray-600">{{ translate('Late Fee') }}</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($schedulePayment->late_fee ?? 0, 2) }} {{ translate('SAR') }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ translate('Is Late') }}: {{ $schedulePayment->is_late ? translate('Yes') : translate('No') }}</div>
                </div>
            </div>
            <div class="card metric-card">
                <div class="card-body">
                    <div class="text-sm text-gray-600">{{ translate('Additional Amount') }}</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($schedulePayment->additional_amount ?? 0, 2) }} {{ translate('SAR') }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ translate('Subscription Fee') }}: {{ number_format($schedulePayment->subscription_fee ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="card metric-card">
                <div class="card-body">
                    <div class="text-sm text-gray-600">{{ translate('Status') }}</div>
                    <div>
                        <span class="badge {{ $schedulePayment->payment_status === 'paid' ? 'badge-success' : ($schedulePayment->payment_status === 'late' ? 'badge-danger' : 'badge-warning') }}">
                            {{ ucfirst($schedulePayment->payment_status) }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ translate('Assigned') }}: {{ $schedulePayment->assigned?->first_name }} {{ $schedulePayment->assigned?->last_name }}</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="grid grid-cols-1 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="tabs" data-tabs="true">
                    <div class="flex items-center gap-5 border-b border-gray-200" role="tablist">
                        <button class="tab active" data-tab-toggle="#overview_tab" role="tab" aria-controls="overview_tab" aria-selected="true">
                            <i class="ki-filled ki-information text-base me-2"></i>{{ translate('Overview') }}
                        </button>
                        <button class="tab" data-tab-toggle="#payment_tab" role="tab" aria-controls="payment_tab" aria-selected="false">
                            <i class="ki-filled ki-credit-cart text-base me-2"></i>{{ translate('Payment') }}
                        </button>
                        <button class="tab" data-tab-toggle="#claims_tab" role="tab" aria-controls="claims_tab" aria-selected="false">
                            <i class="ki-filled ki-phone text-base me-2"></i>{{ translate('Claims') }}
                        </button>
                    </div>

                    <div class="mt-5">
                        <div class="tab-pane active" id="overview_tab" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-sm text-gray-600">{{ translate('Customer') }}</div>
                                            <div class="font-semibold">{{ $schedulePayment->user?->full_name ?? translate('N/A') }}</div>
                                            <div class="text-sm text-gray-600 mt-3">{{ translate('Customer ID') }}</div>
                                            <div class="font-semibold">#{{ str_pad($schedulePayment->user?->id ?? 0, 6, '0', STR_PAD_LEFT) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">{{ translate('Checkout') }}</div>
                                            <div class="font-semibold">#{{ str_pad($schedulePayment->checkout?->id ?? 0, 6, '0', STR_PAD_LEFT) }}</div>
                                            @if($schedulePayment->checkout)
                                            <a href="{{ route('checkouts.show', $schedulePayment->checkout) }}" class="text-primary text-sm hover:underline">{{ translate('Open Checkout') }}</a>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">{{ translate('Due Date') }}</div>
                                            <div class="font-semibold">{{ optional($schedulePayment->due_date)->format('M d, Y') }}</div>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600">{{ translate('UUID') }}</div>
                                            <div class="font-mono text-gray-800">{{ $schedulePayment->uuid }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane hidden" id="payment_tab" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    @if($schedulePayment->payment)
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <div class="text-sm text-gray-600">{{ translate('Payment ID') }}</div>
                                                <div class="font-semibold">#{{ str_pad($schedulePayment->payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-600">{{ translate('Amount') }}</div>
                                                <div class="font-semibold">{{ number_format($schedulePayment->payment->amount ?? $schedulePayment->instalment_amount, 2) }} {{ translate('SAR') }}</div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-600">{{ translate('Status') }}</div>
                                                <div class="font-semibold">{{ ucfirst($schedulePayment->payment->status ?? 'paid') }}</div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-600">{{ translate('Date') }}</div>
                                                <div class="font-semibold">{{ optional($schedulePayment->payment->created_at)->format('M d, Y H:i') }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center py-10">
                                            <i class="ki-filled ki-credit-cart text-4xl text-gray-400 mb-3"></i>
                                            <h4 class="text-lg font-semibold text-gray-600 mb-2">{{ translate('No Payment Recorded') }}</h4>
                                            <p class="text-gray-500">{{ translate('This instalment has not been paid yet.') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane hidden" id="claims_tab" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    @if($schedulePayment->claims->count())
                                        <div class="table-responsive">
                                            <table class="table table-auto table-border">
                                                <thead>
                                                    <tr>
                                                        <th>{{ translate('ID') }}</th>
                                                        <th>{{ translate('Type') }}</th>
                                                        <th>{{ translate('Status') }}</th>
                                                        <th>{{ translate('Created') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($schedulePayment->claims as $claim)
                                                        <tr>
                                                            <td>#{{ str_pad($claim->id, 6, '0', STR_PAD_LEFT) }}</td>
                                                            <td>{{ ucfirst($claim->type ?? 'claim') }}</td>
                                                            <td>
                                                                <span class="badge badge-sm {{ $claim->status === 'resolved' ? 'badge-success' : ($claim->status === 'escalated' ? 'badge-danger' : 'badge-warning') }}">
                                                                    {{ ucfirst($claim->status ?? 'open') }}
                                                                </span>
                                                            </td>
                                                            <td>{{ optional($claim->created_at)->format('M d, Y H:i') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-10">
                                            <i class="ki-filled ki-phone text-4xl text-gray-400 mb-3"></i>
                                            <h4 class="text-lg font-semibold text-gray-600 mb-2">{{ translate('No Claims') }}</h4>
                                            <p class="text-gray-500">{{ translate('No collection activity recorded for this instalment.') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
// Initialize tabs when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  const tabsElement = document.querySelector('[data-tabs="true"]');
  if (tabsElement && typeof KTTabs !== 'undefined') {
    new KTTabs(tabsElement, { hiddenClass: 'hidden' });
  } else if (tabsElement) {
    const buttons = tabsElement.querySelectorAll('[role="tab"][data-tab-toggle]');
    const panes = tabsElement.querySelectorAll('.tab-pane');
    const activate = (targetSelector) => {
      const targetId = targetSelector.startsWith('#') ? targetSelector : `#${targetSelector}`;
      const targetPane = tabsElement.querySelector(targetId);
      if (!targetPane) return;
      panes.forEach(p => { p.classList.add('hidden'); p.classList.remove('active'); });
      targetPane.classList.remove('hidden');
      targetPane.classList.add('active');
      buttons.forEach(btn => {
        const isActive = btn.getAttribute('data-tab-toggle') === targetId;
        btn.classList.toggle('active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
    };
    buttons.forEach(btn => btn.addEventListener('click', (e) => { e.preventDefault(); activate(btn.getAttribute('data-tab-toggle')); }));
    const initiallyActiveBtn = tabsElement.querySelector('[role="tab"].active[data-tab-toggle]') || buttons[0];
    if (initiallyActiveBtn) activate(initiallyActiveBtn.getAttribute('data-tab-toggle'));
  }
});
</script>
@endpush
@endsection
