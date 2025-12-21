<div class="card grow mt-4">
    <div class="card-header">
        <h3 class="card-title">{{ translate('Customer & Shipping Information') }}</h3>
    </div>
    <div class="card-body pt-4 pb-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Customer Info Column -->
            <div>
                <h4 class="font-semibold text-sm text-gray-700 mb-3">
                    {{ translate('Customer Details') }}</h4>
                <table class="table-auto w-full">
                    <tbody>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('Full Name') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                <a href="{{ route('customerProfile', $order->user->id) }}" class="link underline">
                                    {{ $order->user->first_name ?? '' }}
                                    {{ $order->user->last_name ?? '' }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('Email') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                @php
                                    $email = $order->user->email ?? '';
                                    $phone = $order->user->phone_number ?? '';
                                @endphp
                                <a href="mailto:{{ hasSensitivePermission('email_address') ? $email : '' }}"
                                    class="text-primary hover:underline">
                                    {{ maskedSensitiveText('email_address', $email) }}
                                </a>
                            </td>
                        </tr>
                        @if ($order->user->phone_number)
                            <tr>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    {{ translate('Phone Number') }}</td>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    <a href="tel:{{ hasSensitivePermission('phone_number') ? $phone : '' }}"
                                        class="text-primary hover:underline">
                                        {{ maskedSensitiveText('phone_number', $phone) }}
                                    </a>

                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-sm text-gray-600 min-w-36 pb-5 pe-6">
                                {{ translate('Member Since') }}</td>
                            <td class="flex items-center gap-2.5 text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ $order->user->created_at->format('d M, Y') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Shipping Info Column -->
            <div>
                <h4 class="font-semibold text-sm text-gray-700 mb-3">
                    {{ translate('Shipping Details') }}</h4>
                <table class="table-auto w-full">
                    <tbody>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('Recipient') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ $order->shipping_first_name }}
                                {{ $order->shipping_last_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('Address Line 1') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ $order->shipping_address_line1 }}</td>
                        </tr>
                        @if ($order->shipping_address_line2)
                            <tr>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    {{ translate('Address Line 2') }}</td>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    {{ $order->shipping_address_line2 }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('City/State/ZIP') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ $order->shipping_city }},
                                {{ $order->shipping_state }} {{ $order->shipping_postal_code }}</td>
                        </tr>
                        <tr>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ translate('Country') }}</td>
                            <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                {{ $order->shipping_country }}</td>
                        </tr>
                        @if ($order->shipping_email)
                            <tr>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    {{ translate('Email') }}</td>
                                <td class="text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                    {{ $order->shipping_email }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-sm text-gray-600 min-w-36 pb-5 pe-6">
                                {{ translate('Shipping Method') }}</td>
                            <td class="flex items-center gap-2.5 text-sm text-gray-600 min-w-28 pb-4 pe-4">
                                <i class="ki-filled ki-truck-fast text-primary"></i>
                                {{ $order->shipping_type ?? translate('Standard Shipping') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
