 <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
     <div class="flex justify-between items-center mb-6">
         <h3 class="font-bold text-slate-800">{{ translate('Business Summary') }}</h3>
         <span class="text-[10px] text-slate-400">
             {{ translate('Updated') }} {{ $merchant->updated_at?->format('M Y') ?? now()->format('M Y') }}
         </span>
     </div>

     <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
         <div class="md:col-span-7 space-y-4 border-r border-slate-50 pr-4">
             <div class="flex items-center gap-3 text-sm">
                 <i class="ki-outline ki-check-circle text-emerald-500 text-lg"></i>
                 <span class="text-slate-500 w-28">{{ translate('Status') }}:</span>
                 <span class="font-bold text-slate-800">{{ ucfirst(str_replace('_', ' ', $merchant->status)) }}</span>
             </div>

             @php
                 $crData = is_string($merchant->goverment_data)
                     ? json_decode($merchant->goverment_data, true)
                     : $merchant->goverment_data ?? [];
                 $establishmentYear = !empty($crData['issueDateGregorian'])
                     ? \Carbon\Carbon::parse($crData['issueDateGregorian'])->format('Y')
                     : null;
                 $yearsInBusiness = $establishmentYear ? now()->year - $establishmentYear : null;
             @endphp

             @if ($establishmentYear)
                 <div class="flex items-center gap-3 text-sm">
                     <i class="ki-outline ki-calendar text-blue-500 text-lg"></i>
                     <span class="text-slate-500 w-28">{{ translate('Established') }}:</span>
                     <span class="font-bold text-slate-800">
                         {{ translate('Since') }} {{ $establishmentYear }}
                         <span class="text-slate-400 font-medium ml-1">({{ $yearsInBusiness }}
                             {{ translate('years') }})</span>
                     </span>
                 </div>
             @endif

             @if ($merchant->cr_number)
                 <div class="flex items-center gap-3 text-sm">
                     <i class="ki-outline ki-document text-blue-500 text-lg"></i>
                     <span class="text-slate-500 w-28">{{ translate('CR Number') }}:</span>
                     <span class="font-bold text-slate-800">{{ $merchant->cr_number }}</span>
                 </div>
             @endif

             @if (!empty($crData['crCapital']))
                 <div class="flex items-center gap-3 text-sm">
                     <span class="icon-saudi_riyal"></span>
                     <span class="text-slate-500 w-28">{{ translate('Capital') }}:</span>
                     <span class="font-bold text-slate-800">SAR
                         {{ number_format($crData['crCapital']) }}</span>
                 </div>
             @endif

             <div class="flex items-center gap-3 text-sm">
                 <i class="ki-outline ki-verify text-blue-500 text-lg"></i>
                 <span class="text-slate-500 w-28">{{ translate('VAT Registered') }}:</span>
                 <span
                     class="font-bold text-slate-800">{{ $merchant->vat_register ? translate('Yes') : translate('No') }}</span>
             </div>

             <div class="flex items-center gap-3 text-sm">
                 <i class="ki-outline ki-files text-blue-500 text-lg"></i>
                 <span class="text-slate-500 w-28">{{ translate('Balady Registered') }}:</span>
                 <span
                     class="font-bold text-slate-800">{{ $merchant->balady_certificate ? translate('Yes') : translate('No') }}</span>
             </div>
         </div>

         <div class="md:col-span-5 flex flex-col justify-between space-y-6">
             <div class="bg-slate-50/50 p-4 rounded-lg border border-slate-100">
                 <p class="text-xs text-slate-600 leading-relaxed italic">
                     @if (!empty($crData['name']))
                         "{{ $crData['name'] }}"
                         {{ !empty($crData['entityType']['name']) ? 'is a ' . $crData['entityType']['name'] : '' }}
                         {{ $establishmentYear ? 'established in ' . $establishmentYear : '' }}
                         {{ !empty($crData['headquarterCityName']) ? 'based in ' . $crData['headquarterCityName'] . '.' : '' }}
                         {{ $merchant->user?->description ?? '' }}
                     @else
                         {{ translate('No business description available.') }}
                     @endif
                 </p>
             </div>

             @if (!empty($crData))
                 <div class="grid grid-cols-2 gap-y-4 gap-x-6 pt-2">
                     @if (!empty($crData['crNationalNumber']))
                         <div>
                             <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">
                                 {{ translate('CR National No') }}
                             </div>
                             <div class="text-xs font-bold text-slate-700">{{ $crData['crNationalNumber'] }}
                             </div>
                         </div>
                     @endif

                     @if (!empty($crData['issueDateGregorian']))
                         <div>
                             <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">
                                 {{ translate('Issue Date') }}
                             </div>
                             <div class="text-xs font-bold text-slate-700">
                                 {{ \Carbon\Carbon::parse($crData['issueDateGregorian'])->format('d M Y') }}
                             </div>
                         </div>
                     @endif

                     @if (!empty($crData['headquarterCityName']))
                         <div>
                             <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">
                                 {{ translate('Headquarter City') }}
                             </div>
                             <div class="text-xs font-bold text-slate-700">
                                 {{ $crData['headquarterCityName'] }}
                             </div>
                         </div>
                     @endif

                     @if (!empty($crData['activities']))
                         <div>
                             <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">
                                 {{ translate('Activities') }}
                             </div>
                             <div class="text-xs font-bold text-slate-700">
                                 {{ count($crData['activities']) }} {{ translate('Registered') }}
                             </div>
                         </div>
                     @endif
                 </div>

                 <div class="pt-2">
                     <button class="text-blue-600 text-xs font-bold flex items-center gap-1 hover:underline"
                         data-modal-toggle="#cr_data_modal">
                         {{ translate('View Full CR Data') }} <i class="ki-outline ki-arrow-right"></i>
                     </button>
                 </div>
             @endif
         </div>
     </div>
 </div>

 @include('admin.accounts.components.cr-data-modal')
