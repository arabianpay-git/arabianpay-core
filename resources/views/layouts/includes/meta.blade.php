@php
    $general = settings('general');
    $favicon = $general['favicon'] ?? 'assets/media/images/favicon.png';
    $siteName = $general['site_title'] ?? 'ArabianPay';
    $siteDescription = $general['site_description'] ?? '';
    $defaultLocale = $general['default_language'] ?? 'en_US';
@endphp

<meta charset="utf-8" />
<meta content="follow, index" name="robots" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
<meta content="{{ $siteDescription }}" name="description" />

{{-- Twitter Meta --}}
<meta content="@keenthemes" name="twitter:site" />
<meta content="@keenthemes" name="twitter:creator" />
<meta content="summary_large_image" name="twitter:card" />
<meta content="{{ $siteName }}" name="twitter:title" />
<meta content="{{ $siteDescription }}" name="twitter:description" />
<meta content="{{ asset($favicon) }}" name="twitter:image" />

{{-- Open Graph Meta --}}
<meta content="{{ $defaultLocale }}" property="og:locale" />
<meta content="website" property="og:type" />
<meta content="@keenthemes" property="og:site_name" />
<meta content="{{ $siteName }}" property="og:title" />
<meta content="{{ $siteDescription }}" property="og:description" />
<meta content="{{ asset($favicon) }}" property="og:image" />
