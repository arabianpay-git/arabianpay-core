@php
    $currentLocale = app()->getLocale();
    $locales = [
        'en' => ['name' => translate('English'), 'flag' => 'assets/media/flags/united-states.svg'],
        'ar' => ['name' => translate('Arabic'), 'flag' => 'assets/media/flags/saudi-arabia.svg'],
    ];
@endphp

<div class="menu-item" data-menu-item-offset="-10px, 0" data-menu-item-placement="left-start" data-menu-item-toggle="dropdown" data-menu-item-trigger="click|lg:hover">
    <div class="menu-link">
        <span class="menu-icon">
            <i class="ki-filled ki-icon"> </i>
        </span>
        <span class="menu-title">{{ translate('Language') }}</span>
        <div class="flex items-center gap-1.5 rounded-md border border-gray-300 text-gray-600 p-1.5 text-2xs font-medium shrink-0">
            {{ $locales[$currentLocale]['name'] }}
            <img alt="" class="inline-block size-3.5 rounded-full" src="{{ asset($locales[$currentLocale]['flag']) }}" />
        </div>
    </div>

    <div class="menu-dropdown menu-default light:border-gray-300 w-full max-w-[170px]">
        @foreach ($locales as $localeCode => $data)
            <div class="menu-item {{ $localeCode === $currentLocale ? 'active' : '' }}">
                <a class="menu-link h-10" hreflang="{{ $localeCode }}" 
                href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                    <span class="menu-icon">
                        <img alt="flag" class="inline-block size-4 rounded-full" src="{{ asset($data['flag']) }}" />
                    </span>
                    <span class="menu-title">{{ $data['name'] }}</span>
                    @if ($localeCode === $currentLocale)
                        <span class="menu-badge">
                            <i class="ki-solid ki-check-circle text-success text-base"></i>
                        </span>
                    @endif
                </a>
            </div>
        @endforeach
    </div>
</div>