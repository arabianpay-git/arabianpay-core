<div class="card grow mt-4">
    <div class="card-header">
        <h3 class="card-title">{{ translate('Supplier Information') }}</h3>
    </div>
    <div class="card-body pt-4 pb-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Supplier Basic Info Column -->
            <div>
                <h4 class="font-semibold text-sm text-gray-700 mb-3">
                    {{ translate('Supplier Details') }}</h4>
                <table class="table-auto w-full">
                    <tbody>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('Full Name') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                <a href="{{ route('supplierProfile', $order->seller->id) }}" class="link underline">
                                    {{ $order->seller->first_name ?? '' }}
                                    {{ $order->seller->last_name ?? '' }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('Company Name') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ $order->seller->business_name ?? '-' }}</td>
                        </tr>
                        @if ($order->seller->email)
                            <tr>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    {{ translate('Email') }}</td>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    <a href="mailto:{{ $order->seller->email }}" class="text-primary hover:underline">
                                        {{ $order->seller->email }}
                                    </a>
                                </td>
                            </tr>
                        @endif
                        @if ($order->seller->phone_number)
                            <tr>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    {{ translate('Phone Number') }}</td>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    <a href="tel:{{ $order->seller->phone_number }}"
                                        class="text-primary hover:underline">
                                        {{ $order->seller->phone_number }}
                                    </a>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-sm text-gray-600 min-w-36 pb-5 pe-6">
                                {{ translate('Member Since') }}</td>
                            <td class="flex items-center gap-2.5 text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ optional($order->seller->created_at)->format('d M, Y') ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Supplier Address Column -->
            <div>
                <h4 class="font-semibold text-sm text-gray-700 mb-3">
                    {{ translate('Address Details') }}</h4>
                <table class="table-auto w-full">
                    <tbody>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('City/State/ZIP') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ $order->seller->city->name ?? '-' }},
                                {{ $order->seller->state->name ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('Country') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ $order->seller->country->name ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
