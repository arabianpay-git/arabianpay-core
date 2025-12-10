@extends('layouts.base')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@endpush

@section('content')
    @php
        $groups = [
            'core' => ['icon' => 'ki-setting-2', 'title' => 'Web Application', 'color' => 'indigo'],
            'financial' => ['icon' => 'ki-wallet', 'title' => 'Financial Management', 'color' => 'emerald'],
            'risk_management' => ['icon' => 'ki-shield-tick', 'title' => 'Risk Management', 'color' => 'red'],
            'credit_collections' => ['icon' => 'ki-chart-line', 'title' => 'Credit & Collections', 'color' => 'blue'],
            'orders_shipping' => ['icon' => 'ki-delivery', 'title' => 'Orders & Shipping', 'color' => 'purple'],
            'products' => ['icon' => 'ki-box', 'title' => 'Products', 'color' => 'amber'],
            'supplier' => ['icon' => 'ki-people', 'title' => 'Supplier Management', 'color' => 'teal'],
            'customer' => ['icon' => 'ki-user', 'title' => 'Customer Management', 'color' => 'cyan'],
            'marketing' => ['icon' => 'ki-megaphone', 'title' => 'Marketing', 'color' => 'pink'],
            'support' => ['icon' => 'ki-support', 'title' => 'Support & Tickets', 'color' => 'orange'],
            'employee_roles' => ['icon' => 'ki-profile-user', 'title' => 'Employee & Roles', 'color' => 'gray'],
            'system' => ['icon' => 'ki-monitor', 'title' => 'System', 'color' => 'slate'],
            'third_party' => ['icon' => 'ki-puzzle', 'title' => 'Third Party Services', 'color' => 'violet'],
        ];
    @endphp

    <main class="grow content pt-5">
        <div class="container-fixed">

            {{-- Page Title --}}
            <div class="pb-6">
                <h1 class="text-2xl font-bold text-gray-900">{{ translate('System Settings') }}</h1>
                <p class="text-gray-600 mt-2">Configure and manage all system settings from one place</p>
            </div>

            {{-- Settings Navigation Tabs --}}
            <div class="mb-8">
                <div class="flex flex-wrap gap-2 border-b border-gray-200">
                    @foreach (array_keys($settings) as $groupKey)
                        <button type="button" onclick="scrollToSection('{{ $groupKey }}')"
                            class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors link underline
                                       {{ $loop->first ? 'bg-white border border-b-0 border-gray-200 text-primary' : 'text-gray-500 hover:text-primary hover:bg-gray-50' }}">
                            {{ translate($groups[$groupKey]['title'] ?? ucfirst(str_replace('_', ' ', $groupKey))) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Render Each Group --}}
            @foreach ($settings as $groupKey => $groupSettings)
                <div id="{{ $groupKey }}" class="mb-10 scroll-mt-20">

                    {{-- Group Header --}}
                    <div class="flex items-center gap-3 mb-6">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-lg 
                                    bg-{{ $groups[$groupKey]['color'] ?? 'indigo' }}-100 text-{{ $groups[$groupKey]['color'] ?? 'indigo' }}-600">
                            <i class="ki-duotone {{ $groups[$groupKey]['icon'] ?? 'ki-setting-2' }} text-2xl">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">
                                {{ translate($groups[$groupKey]['title'] ?? ucfirst(str_replace('_', ' ', $groupKey))) }}
                            </h2>
                            <p class="text-gray-600 text-sm mt-1">
                                {{ translate('Configure ' . str_replace('_', ' ', $groupKey) . ' settings') }}
                            </p>
                        </div>
                    </div>

                    {{-- Cards Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach ($groupSettings as $set)
                            <a href="{{ $set['route'] ?? '#' }}"
                                class="group p-4 bg-white rounded-xl border border-gray-200 shadow-sm 
                                      hover:shadow-md hover:border-{{ $groups[$groupKey]['color'] ?? 'indigo' }}-400 hover:bg-{{ $groups[$groupKey]['color'] ?? 'indigo' }}-50/60 
                                      transition-all duration-200 flex flex-col h-full">

                                {{-- Icon --}}
                                <div
                                    class="flex items-center justify-center w-10 h-10 rounded-lg 
                                            bg-{{ $groups[$groupKey]['color'] ?? 'indigo' }}-100 text-{{ $groups[$groupKey]['color'] ?? 'indigo' }}-600 
                                            mb-3 text-xl group-hover:scale-110 transition-transform duration-200">
                                    <i class="ki-duotone {{ $set['icon'] ?? 'ki-setting-2' }}">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                </div>

                                {{-- Title --}}
                                <h3
                                    class="font-semibold text-gray-900 text-sm mb-2 group-hover:text-{{ $groups[$groupKey]['color'] ?? 'indigo' }}-600">
                                    {{ translate($set['title']) }}
                                </h3>

                                {{-- Description --}}
                                <p class="text-xs text-gray-500 leading-relaxed flex-grow">
                                    {{ translate($set['description']) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </main>

    @push('scripts')
        <script>
            function scrollToSection(sectionId) {
                const element = document.getElementById(sectionId);
                if (element) {
                    element.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }

            // Highlight active section on scroll
            document.addEventListener('DOMContentLoaded', function() {
                const sections = document.querySelectorAll('[id]');
                const navButtons = document.querySelectorAll('[onclick^="scrollToSection"]');

                window.addEventListener('scroll', function() {
                    let current = '';
                    sections.forEach(section => {
                        const sectionTop = section.offsetTop;
                        const sectionHeight = section.clientHeight;
                        if (scrollY >= (sectionTop - 100)) {
                            current = section.getAttribute('id');
                        }
                    });

                    navButtons.forEach(button => {
                        button.classList.remove('bg-white', 'border', 'border-b-0', 'border-gray-200',
                            'text-primary');
                        button.classList.add('text-gray-500', 'hover:text-primary', 'hover:bg-gray-50');

                        if (button.getAttribute('onclick').includes(current)) {
                            button.classList.remove('text-gray-500', 'hover:text-primary',
                                'hover:bg-gray-50');
                            button.classList.add('bg-white', 'border', 'border-b-0', 'border-gray-200',
                                'text-primary');
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
