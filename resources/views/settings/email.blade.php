@extends('layouts.base')

@section('content')
    @php
        $email = settings('email');
    @endphp

    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">

            {{-- Breadcrumb and Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ translate('Email Settings') }}</h1>
                <p class="text-gray-600">
                    {{ translate('Configure your email server settings, templates, and notification preferences.') }}
                </p>
            </div>

            <form action="{{ route('settings.email.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-7.5 mt-5">
                    {{-- Left Column (2/3 width on large screens) --}}
                    <div class="col-span-2 gap-5 lg:gap-7.5 flex flex-col">

                        {{-- SMTP Configuration Card --}}
                        <div class="card pb-2.5">
                            <div class="card-header" id="smtp_configuration">
                                <h3 class="card-title">{{ translate('SMTP Configuration') }}</h3>
                            </div>
                            <div class="card-body grid gap-5">
                                {{-- Email Enable/Disable Switch --}}
                                <div class="w-full flex items-center justify-between">
                                    <div>
                                        <label class="form-label block mb-2">{{ translate('Enable Email System') }}</label>
                                        <p class="text-sm text-gray-500">
                                            {{ translate('Turn on/off the entire email notification system') }}
                                        </p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="email_enabled" value="1"
                                            {{ $email['email_enabled'] ?? 1 ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">
                                        {{ translate('Mail Driver') }}
                                    </label>
                                    <select class="select @error('mail_driver') border-red-500 @enderror" name="mail_driver"
                                        id="mail_driver">
                                        <option value="smtp"
                                            {{ ($email['mail_driver'] ?? 'smtp') == 'smtp' ? 'selected' : '' }}>
                                            SMTP
                                        </option>
                                        <option value="sendmail"
                                            {{ ($email['mail_driver'] ?? 'smtp') == 'sendmail' ? 'selected' : '' }}>
                                            Sendmail
                                        </option>
                                        <option value="mailgun"
                                            {{ ($email['mail_driver'] ?? 'smtp') == 'mailgun' ? 'selected' : '' }}>
                                            Mailgun
                                        </option>
                                        <option value="ses"
                                            {{ ($email['mail_driver'] ?? 'smtp') == 'ses' ? 'selected' : '' }}>
                                            Amazon SES
                                        </option>
                                        <option value="postmark"
                                            {{ ($email['mail_driver'] ?? 'smtp') == 'postmark' ? 'selected' : '' }}>
                                            Postmark
                                        </option>
                                    </select>
                                    @error('mail_driver')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('SMTP Host') }}
                                        </label>
                                        <input class="input @error('mail_host') border-red-500 @enderror" name="mail_host"
                                            type="text"
                                            value="{{ old('mail_host', $email['mail_host'] ?? config('mail.mailers.smtp.host', 'smtp.gmail.com')) }}" />
                                        @error('mail_host')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('SMTP Port') }}
                                        </label>
                                        <input class="input @error('mail_port') border-red-500 @enderror" name="mail_port"
                                            type="number"
                                            value="{{ old('mail_port', $email['mail_port'] ?? config('mail.mailers.smtp.port', 587)) }}" />
                                        @error('mail_port')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">
                                        {{ translate('Mail Encryption') }}
                                    </label>
                                    <select class="select @error('mail_encryption') border-red-500 @enderror"
                                        name="mail_encryption">
                                        <option value="tls"
                                            {{ ($email['mail_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>
                                            TLS
                                        </option>
                                        <option value="ssl"
                                            {{ ($email['mail_encryption'] ?? 'tls') == 'ssl' ? 'selected' : '' }}>
                                            SSL
                                        </option>
                                        <option value=""
                                            {{ empty($email['mail_encryption'] ?? 'tls') ? 'selected' : '' }}>
                                            None
                                        </option>
                                    </select>
                                    @error('mail_encryption')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('SMTP Username') }}
                                        </label>
                                        <input class="input @error('mail_username') border-red-500 @enderror"
                                            name="mail_username" type="text"
                                            value="{{ old('mail_username', $email['mail_username'] ?? '') }}" />
                                        @error('mail_username')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('SMTP Password') }}
                                        </label>
                                        <div class="relative">
                                            <input class="input @error('mail_password') border-red-500 @enderror"
                                                name="mail_password" type="password"
                                                value="{{ old('mail_password', $email['mail_password'] ?? '') }}" />
                                            <button type="button"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                                style="right: 10px;" onclick="togglePasswordVisibility(this)">
                                                <i class="ki-filled ki-eye"></i>
                                            </button>
                                        </div>
                                        @error('mail_password')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Email Templates & Rules Card --}}
                        <div class="card pb-2.5">
                            <div class="card-header" id="email_templates">
                                <h3 class="card-title">{{ translate('Email Templates & Rules') }}</h3>
                            </div>
                            <div class="card-body grid gap-5">
                                {{-- From Address & Name --}}
                                <div class="grid gap-5 md:grid-cols-2">
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('From Address') }}
                                        </label>
                                        <input class="input @error('from_address') border-red-500 @enderror"
                                            name="from_address" type="email"
                                            value="{{ old('from_address', $email['from_address'] ?? config('mail.from.address', 'noreply@example.com')) }}" />
                                        @error('from_address')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('From Name') }}
                                        </label>
                                        <input class="input @error('from_name') border-red-500 @enderror" name="from_name"
                                            type="text"
                                            value="{{ old('from_name', $email['from_name'] ?? config('mail.from.name', config('app.name'))) }}" />
                                        @error('from_name')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Notification Rules --}}
                                <div class="space-y-4">
                                    <h4 class="font-medium text-gray-700">{{ translate('Notification Rules') }}</h4>

                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <label class="form-label">{{ translate('New User Registration') }}</label>
                                            <p class="text-sm text-gray-500">
                                                {{ translate('Send email when new user registers') }}
                                            </p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="notify_new_user" value="1"
                                                {{ $email['notify_new_user'] ?? 1 ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <label class="form-label">{{ translate('Password Reset') }}</label>
                                            <p class="text-sm text-gray-500">
                                                {{ translate('Send password reset instructions') }}
                                            </p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="notify_password_reset" value="1"
                                                {{ $email['notify_password_reset'] ?? 1 ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <label class="form-label">{{ translate('Order Confirmation') }}</label>
                                            <p class="text-sm text-gray-500">
                                                {{ translate('Send order confirmation emails') }}
                                            </p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="notify_order_confirmation" value="1"
                                                {{ $email['notify_order_confirmation'] ?? 1 ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <label class="form-label">{{ translate('Contact Form Submissions') }}</label>
                                            <p class="text-sm text-gray-500">
                                                {{ translate('Send email when contact form is submitted') }}
                                            </p>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="notify_contact_form" value="1"
                                                {{ $email['notify_contact_form'] ?? 1 ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Advanced Settings Card --}}
                        <div class="card pb-2.5">
                            <div class="card-header" id="advanced_settings">
                                <h3 class="card-title">{{ translate('Advanced Settings') }}</h3>
                            </div>
                            <div class="card-body grid gap-5">
                                <div class="grid gap-5 md:grid-cols-2">
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('Timeout (seconds)') }}
                                        </label>
                                        <input class="input @error('timeout') border-red-500 @enderror" name="timeout"
                                            type="number" value="{{ old('timeout', $email['timeout'] ?? 30) }}"
                                            min="5" max="300" />
                                        @error('timeout')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('Retry Attempts') }}
                                        </label>
                                        <input class="input @error('retry_attempts') border-red-500 @enderror"
                                            name="retry_attempts" type="number"
                                            value="{{ old('retry_attempts', $email['retry_attempts'] ?? 3) }}"
                                            min="1" max="10" />
                                        @error('retry_attempts')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">
                                        {{ translate('Email Queue') }}
                                    </label>
                                    <select class="select @error('use_queue') border-red-500 @enderror" name="use_queue">
                                        <option value="0" {{ ($email['use_queue'] ?? 0) == 0 ? 'selected' : '' }}>
                                            {{ translate('Send immediately') }}
                                        </option>
                                        <option value="1" {{ ($email['use_queue'] ?? 0) == 1 ? 'selected' : '' }}>
                                            {{ translate('Queue emails') }}
                                        </option>
                                    </select>
                                    <p class="text-sm text-gray-500 mt-2">
                                        {{ translate('Queue emails for better performance (requires queue worker running)') }}
                                    </p>
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">
                                        {{ translate('Email Charset') }}
                                    </label>
                                    <select class="select @error('charset') border-red-500 @enderror" name="charset">
                                        <option value="utf-8"
                                            {{ ($email['charset'] ?? 'utf-8') == 'utf-8' ? 'selected' : '' }}>
                                            UTF-8
                                        </option>
                                        <option value="iso-8859-1"
                                            {{ ($email['charset'] ?? 'utf-8') == 'iso-8859-1' ? 'selected' : '' }}>
                                            ISO-8859-1
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column (1/3 width on large screens) --}}
                    <div class="col-span-1 gap-5 lg:gap-7.5 flex flex-col">
                        {{-- Quick Help Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Quick Help') }}</h3>
                            </div>
                            <div class="card-body space-y-3">
                                <div class="flex items-start gap-2">
                                    <i class="ki-filled ki-information text-blue-500 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium">{{ translate('Common SMTP Ports') }}</p>
                                        <p class="text-xs text-gray-500">TLS: 587, SSL: 465, Default: 25</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="ki-filled ki-information text-blue-500 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium">{{ translate('Gmail SMTP') }}</p>
                                        <p class="text-xs text-gray-500">Host: smtp.gmail.com, Port: 587</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="ki-filled ki-information text-blue-500 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium">{{ translate('Outlook/Hotmail') }}</p>
                                        <p class="text-xs text-gray-500">Host: smtp-mail.outlook.com, Port: 587</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="ki-filled ki-information text-blue-500 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium">{{ translate('Yahoo Mail') }}</p>
                                        <p class="text-xs text-gray-500">Host: smtp.mail.yahoo.com, Port: 465</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="ki-filled ki-information text-blue-500 mt-1"></i>
                                    <div>
                                        <p class="text-sm font-medium">{{ translate('Security Note') }}</p>
                                        <p class="text-xs text-gray-500">Use TLS encryption for better security</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Test Configuration Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Test Configuration') }}</h3>
                            </div>
                            <div class="card-body">
                                <p class="text-sm text-gray-600 mb-3">
                                    {{ translate('Test your email configuration by sending a test email to verify everything is working properly.') }}
                                </p>
                                <div class="space-y-3">
                                    <input type="email" id="testEmailInput" class="input w-full"
                                        placeholder="{{ translate('Enter test email address') }}" />
                                    <button type="button" onclick="testEmailConfiguration()"
                                        class="btn btn-secondary whitespace-nowrap" style="margin-top: 0.725rem">
                                        {{ translate('Send Test Email') }}
                                    </button>
                                </div>
                                <div id="testResult" class="mt-3 hidden">
                                    <div class="flex items-center gap-2 text-sm">
                                        <i class="ki-filled ki-check text-green-500"></i>
                                        <span class="text-green-600" id="testResultMessage"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Actions Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Actions') }}</h3>
                            </div>
                            <div class="card-body space-y-3">

                                <div class="flex gap-4">

                                    {{-- Cancel --}}
                                    <a href="{{ route('settings.index') }}" type="button"
                                        class="btn btn-outline btn-secondary w-full d-flex align-items-center justify-content-center">
                                        <i class="ki-filled ki-arrow-left me-2"></i>
                                        {{ translate('Cancel') }}
                                    </a>

                                    {{-- Save Changes --}}
                                    <button type="submit"
                                        class="btn btn-primary w-full d-flex align-items-center justify-content-center">
                                        <i class="ki-filled ki-check-circle me-2"></i>
                                        {{ translate('Save Changes') }}
                                    </button>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        // Show/hide SMTP fields based on mail driver
        function toggleSMTPFields() {
            const driver = document.getElementById('mail_driver').value;
            const smtpFields = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption'];

            smtpFields.forEach(field => {
                const element = document.querySelector(`[name="${field}"]`);
                if (element) {
                    const parent = element.closest('.w-full');
                    if (parent) {
                        parent.style.display = driver === 'smtp' ? 'block' : 'none';
                    }
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleSMTPFields();

            // Add change event listener to mail driver select
            const mailDriverSelect = document.getElementById('mail_driver');
            if (mailDriverSelect) {
                mailDriverSelect.addEventListener('change', toggleSMTPFields);
            }
        });

        function togglePasswordVisibility(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ki-filled ki-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'ki-filled ki-eye';
            }
        }

        function testEmailConfiguration() {
            const testEmailInput = document.getElementById('testEmailInput') || document.querySelector(
                'input[name="test_email"]');
            const testEmail = testEmailInput.value;

            if (!testEmail) {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ translate('Please enter a test email address') }}',
                });
                return;
            }

            if (!validateEmail(testEmail)) {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ translate('Please enter a valid email address') }}',
                });
                return;
            }

            // Show loading state
            const button = document.querySelector('button[onclick="testEmailConfiguration()"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="ki-filled ki-loader animate-spin"></i> {{ translate('Sending...') }}';
            button.disabled = true;

            // Send test email via AJAX
            fetch('{{ route('settings.email.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        email: testEmail
                    })
                })
                .then(response => response.json())
                .then(data => {
                    button.innerHTML = originalText;
                    button.disabled = false;

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ translate('Test email sent successfully!') }}',
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Failed to send test email') }}',
                            text: data.message || '{{ translate('Unknown error') }}'
                        });
                    }
                })
                .catch(error => {
                    button.innerHTML = originalText;
                    button.disabled = false;

                    Swal.fire({
                        icon: 'error',
                        title: '{{ translate('An error occurred while sending test email') }}',
                        text: error.message || '{{ translate('Unknown error') }}'
                    });

                    console.error('Error:', error);
                });
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    </script>
@endpush
