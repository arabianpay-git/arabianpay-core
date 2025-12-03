@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            .risk-tab-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100px;
            }

            .risk-tab-buttons {
                display: flex;
                gap: 10px;
            }

            .risk-tab-buttons a {
                padding: 8px 20px;
                border-radius: 8px;
                font-weight: 600;
                text-decoration: none;
                color: #4B5563;
                background-color: #F3F4F6;
                transition: all 0.2s ease;
                box-shadow: none;
            }

            .risk-tab-buttons a:hover {
                background-color: #E5E7EB;
                color: #1F2937;
            }

            .risk-tab-buttons a.active {
                background-color: #3B82F6;
                color: #FFFFFF;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }
        </style>
    @endpush
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex justify-between gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Risk Score Engine') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">

                    <!-- Tabs for Merchant / Supplier -->
                    <div class="card-header flex-wrap gap-2">
                        <ul class="risk-tab-buttons">
                            <li>
                                <a href="{{ route('risk.merchantScore', array_merge(request()->query(), ['type' => 'merchant'])) }}"
                                    class="{{ request('type') === 'merchant' ? 'active' : '' }}">
                                    {{ translate('Supplier') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('risk.merchantScore', array_merge(request()->query(), ['type' => 'user'])) }}"
                                    class="{{ request('type') === 'user' || !request('type') ? 'active' : '' }}">
                                    {{ translate('Merchant') }}
                                </a>
                            </li>
                        </ul>

                        <div class="flex flex-wrap gap-2 lg:gap-5 items-center mt-3">
                            <div class="flex">
                                <form id="risk-search-form" method="GET" action="{{ route('risk.merchantScore') }}"
                                    class="flex">
                                    <!-- Preserve type parameter in search -->
                                    <input type="hidden" name="type" value="{{ request('type') }}">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input name="search" type="text"
                                            placeholder="{{ translate('Search by first name, last name, email, business name, phone number, iqama number') }}"
                                            value="{{ request('search') }}" style="width: 492px;" />
                                    </label>

                                    <button type="submit" class="btn btn-sm btn-primary" style="margin-left: 5px;">
                                        {{ translate('Search') }}
                                    </button>

                                    <!-- Export button -->
                                    {{-- <button type="button" id="btn-export-risk" class="btn btn-sm btn-outline btn-primary"
                                        style="margin-left: 8px;">
                                        {{ translate('Export Excel') }}
                                    </button> --}}
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="refund_requests_table">
                            @include('admin.risk-management.components.score-table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div class="modal" data-modal="true" id="score_modal">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title">{{ translate('User Risk Management') }}</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body p-0 pb-5">
                <form action="{{ route('risk.merchantScoreUpdate') }}" method="POST" class="px-5 pt-3">
                    @csrf
                    <input type="hidden" id="user_id" name="user_id">

                    <div class="mb-4">
                        <label class="form-label" for="risk_score">{{ translate('Score') }}</label>
                        <input type="text" id="risk_score" name="risk_score" class="input"
                            value="{{ old('risk_score') }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="reason">{{ translate('Reason') }}</label>
                        <textarea id="reason" name="reason" class="textarea" required>{{ old('reason') }}</textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">{{ translate('Upgrade') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('admin.risk-management.components.weight-modal')

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const exportBtn = document.getElementById('btn-export-risk');

                async function postJson(url, body) {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    });
                    if (!res.ok) {
                        const txt = await res.text().catch(() => '');
                        throw new Error(txt || `Request failed with status ${res.status}`);
                    }
                    return res.json();
                }

                async function getJson(url) {
                    const res = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    // if not ok, try to read text for debugging then throw
                    if (!res.ok) {
                        const txt = await res.text().catch(() => '');
                        console.warn('[Export] status endpoint returned non-ok response:', res.status, txt);
                        throw new Error(txt || `Request failed with status ${res.status}`);
                    }

                    // try parse JSON (if server returned HTML you'll catch it)
                    try {
                        return await res.json();
                    } catch (e) {
                        const txt = await res.text().catch(() => '');
                        console.warn('[Export] status endpoint returned non-JSON response:', txt);
                        throw new Error('Status endpoint returned non-JSON response');
                    }
                }

                exportBtn?.addEventListener('click', async function(e) {
                    const form = document.getElementById('risk-search-form');
                    const formData = new FormData(form);
                    const payload = {};
                    for (const [k, v] of formData.entries()) payload[k] = v;

                    exportBtn.setAttribute('disabled', 'disabled');
                    exportBtn.classList.add('opacity-60');

                    const swalHtml = `
            <div style="text-align:left">
                <div id="export-progress-text" style="font-weight:600;margin-bottom:8px">Preparing export... 0%</div>
                <div style="background:#f3f4f6;border-radius:6px;margin-top:4px;height:12px;overflow:hidden;">
                    <div id="export-progress-bar" style="height:12px;width:0%;background:#3b82f6;border-radius:6px"></div>
                </div>
                <div id="export-progress-count" style="margin-top:8px;color:#6b7280;font-size:13px">0 / 0 processed</div>
                <div id="export-progress-note" style="margin-top:8px;color:#6b7280;font-size:12px">This may take a few moments.</div>
            </div>
        `;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Processing export',
                            html: swalHtml,
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {}
                        });
                    }

                    try {
                        console.info('[Export] start payload', payload);
                        const startResp = await postJson("{{ route('risk.export') }}", payload);
                        console.info('[Export] startResp', startResp);

                        if (!startResp?.export_id) throw new Error('No export_id returned from server');

                        const exportId = startResp.export_id;
                        const initialTotal = (typeof startResp.total === 'number') ? startResp.total : null;

                        // create status URL from safe placeholder
                        const statusUrlTemplate =
                            "{{ route('risk.export.status', ['id' => '__EXPORT_ID__']) }}";
                        const statusUrl = statusUrlTemplate.replace('__EXPORT_ID__', exportId);
                        console.info('[Export] statusUrl', statusUrl);

                        // put initial total into UI quickly
                        const htmlContainer = Swal.getHtmlContainer?.();
                        if (htmlContainer) {
                            const countEl = htmlContainer.querySelector('#export-progress-count');
                            const textEl = htmlContainer.querySelector('#export-progress-text');
                            if (initialTotal !== null) {
                                if (countEl) countEl.innerText = `0 / ${initialTotal} processed`;
                                if (textEl) textEl.innerText =
                                    `Processing export... 0% (0/${initialTotal})`;
                            }
                        }

                        const pollInterval = 2000;
                        const maxAttempts = 900;
                        let attempts = 0;
                        let downloadUrl = null;

                        while (attempts < maxAttempts) {
                            attempts++;
                            let st;
                            try {
                                st = await getJson(statusUrl);
                            } catch (err) {
                                // log then retry
                                console.warn('[Export] status fetch failed (attempt ' + attempts + '):', err
                                    .message || err);
                                await new Promise(r => setTimeout(r, pollInterval));
                                continue;
                            }

                            console.info('[Export] status response', st);

                            // update UI
                            const progress = Number(st.progress ?? 0);
                            const processed = Number(st.processed ?? 0);
                            const total = (st.total === null || st.total === undefined) ? null : Number(st
                                .total);

                            if (htmlContainer) {
                                const textEl = htmlContainer.querySelector('#export-progress-text');
                                const barEl = htmlContainer.querySelector('#export-progress-bar');
                                const countEl = htmlContainer.querySelector('#export-progress-count');
                                if (textEl) textEl.innerText = total === null ?
                                    `Processing export... ${progress}%` :
                                    `Processing export... ${progress}% (${processed}/${total})`;
                                if (barEl) barEl.style.width = `${progress}%`;
                                if (countEl) countEl.innerText = total === null ? `${processed} processed` :
                                    `${processed} / ${total} processed`;
                            }

                            if (st.status === 'error') {
                                throw new Error(st.message || 'Server reported error during export');
                            }

                            if (st.ready || st.status === 'ready') {
                                downloadUrl = st.url ?? st.url ?? st.download_url ?? null;
                                // fallback: if server returns filename but not URL, build download url
                                if (!downloadUrl && st.filename) {
                                    downloadUrl =
                                        "{{ route('risk.export.download', ['id' => '__EXPORT_ID__']) }}"
                                        .replace('__EXPORT_ID__', exportId);
                                }
                                if (!downloadUrl && !st.filename) {
                                    console.warn('[Export] ready but no download URL returned by server');
                                }
                                break;
                            }

                            // small delay before next poll
                            await new Promise(r => setTimeout(r, pollInterval));
                        }

                        if (!downloadUrl) throw new Error(
                            'No download URL returned (timeout or server did not provide URL). Check server logs.'
                        );

                        // close Swal + success
                        if (typeof Swal !== 'undefined') {
                            Swal.close();
                            Swal.fire({
                                icon: 'success',
                                title: 'Export ready',
                                text: 'Your export is ready and will download now.'
                            });
                        }

                        const a = document.createElement('a');
                        a.href = downloadUrl;
                        a.setAttribute('download', '');
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    } catch (err) {
                        console.error('[Export] caught error', err);
                        if (typeof Swal !== 'undefined') {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Export failed',
                                text: err.message || 'Something went wrong while exporting.'
                            });
                        } else {
                            alert(err.message || 'Export failed');
                        }
                    } finally {
                        exportBtn.removeAttribute('disabled');
                        exportBtn.classList.remove('opacity-60');
                    }
                });
            });
        </script>
    @endpush
@endsection
