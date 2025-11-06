@extends('layouts.base')

@push('styles')
<style>
    /* Bootstrap Timeline Styles */
    .pools-timeline {
        min-height: 200px;
        padding: 10px 0;
    }
    
    .timeline-months-header {
        margin-bottom: 15px;
        font-weight: bold;
        border-bottom: 2px solid #e4e6ea;
        padding-bottom: 8px;
    }
    
    .timeline-month {
        text-align: center;
        font-size: 11px;
        color: #7e8299;
        padding: 8px 2px;
        border-right: 1px solid #f1f1f4;
        white-space: nowrap;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
    }
    
    .timeline-month:last-child {
        border-right: none;
    }
    
    .timeline-pool-row {
        margin-bottom: 8px;
        min-height: 35px;
        position: relative;
        padding: 2px 0;
    }
    
    .pool-badge {
        position: absolute;
        height: 30px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        min-width: 60px;
    }
    
    .pool-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10;
    }
    
    .pool-badge-high {
        background: linear-gradient(135deg, #50cd89, #3ac47d);
    }
    
    .pool-badge-medium {
        background: linear-gradient(135deg, #ffc700, #f1bc00);
    }
    
    .pool-badge-low {
        background: linear-gradient(135deg, #f1416c, #e02454);
    }
    
    .pool-badge-closed {
        background: linear-gradient(135deg, #7e8299, #6c727f);
    }
    
    .timeline-grid-lines {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        z-index: 1;
    }
    
    .grid-line {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 1px;
        background-color: #f1f1f4;
    }
</style>
@endpush

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed">
        <!-- Page Header -->
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ translate('Investment Pools Calendar') }}
                </h1>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span>{{ translate('Timeline view of your investment pools and their performance') }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('financial.dashboard') }}" class="btn btn-light btn-sm">
                    <i class="ki-filled ki-arrow-left mr-1"></i>
                    {{ translate('Back to Dashboard') }}
                </a>
                <button id="add-pool-btn" class="btn btn-primary btn-sm">
                    <i class="ki-filled ki-plus mr-1"></i>
                    {{ translate('Add Pool') }}
                </button>
            </div>
        </div>

        <!-- Legend -->
        <div class="card mb-5">
            <div class="card-body py-4">
                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-success rounded-full"></div>
                        <span class="text-sm text-gray-700">{{ translate('High Performance') }} (≥90%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-warning rounded-full"></div>
                        <span class="text-sm text-gray-700">{{ translate('Medium Performance') }} (70-89%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-danger rounded-full"></div>
                        <span class="text-sm text-gray-700">{{ translate('Low Performance') }} (<70%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                        <span class="text-sm text-gray-700">{{ translate('Closed Pools') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap Timeline View -->
        <div class="card">
            <div class="card-header">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h3 class="card-title">
                        <i class="ki-filled ki-timeline text-primary mr-2"></i>
                        {{ translate('Investment Pools Timeline') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <button id="timeline-year-prev" class="btn btn-sm btn-light">
                            <i class="ki-filled ki-left"></i>
                        </button>
                        <span id="timeline-year" class="fw-bold px-3">2025</span>
                        <button id="timeline-year-next" class="btn btn-sm btn-light">
                            <i class="ki-filled ki-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Timeline Container -->
                <div id="pools-timeline" class="pools-timeline">
                    <!-- Timeline will be generated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Pool Details Modal -->
    <div class="modal fade" id="poolDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Investment Pool Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="poolDetailsContent">
                    <!-- Pool details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="button" class="btn btn-primary">{{ translate('Edit Pool') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Pool Modal -->
    <div class="modal fade" id="addPoolModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Add New Investment Pool') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPoolForm">
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label required">{{ translate('Pool Name') }}</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label required">{{ translate('Start Date') }}</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label required">{{ translate('End Date') }}</label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">{{ translate('Total Amount') }}</label>
                            <input type="number" class="form-control" name="total_disbursed" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Create Pool') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Timeline View Variables
    let timelineYear = new Date().getFullYear();

    // Timeline Functions
    function renderTimeline() {
        const timelineContainer = document.getElementById('pools-timeline');
        const yearSpan = document.getElementById('timeline-year');
        
        yearSpan.textContent = timelineYear;
        
        // Load pools for timeline
        const startDate = `${timelineYear}-01-01`;
        const endDate = `${timelineYear}-12-31`;
        
        fetch(`/admin/investment-pools/calendar-events?start=${startDate}&end=${endDate}`)
            .then(response => response.json())
            .then(pools => {
                generateTimeline(pools);
            })
            .catch(error => {
                console.error('Error loading pools:', error);
                // Use sample data as fallback
                generateTimeline(getSamplePools());
            });
    }

    function getSamplePools() {
        return [
            {
                title: 'Pool A',
                start: `${timelineYear}-01-15`,
                end: `${timelineYear}-03-15`,
                extendedProps: {
                    poolData: {
                        name: 'Pool A',
                        collection_rate: 95,
                        total_disbursed: 250000,
                        total_collected: 237500,
                        total_checkouts: 150,
                        status: 'active'
                    }
                }
            },
            {
                title: 'Pool B',
                start: `${timelineYear}-02-01`,
                end: `${timelineYear}-04-01`,
                extendedProps: {
                    poolData: {
                        name: 'Pool B',
                        collection_rate: 78,
                        total_disbursed: 180000,
                        total_collected: 140400,
                        total_checkouts: 98,
                        status: 'active'
                    }
                }
            },
            {
                title: 'Pool C',
                start: `${timelineYear}-03-10`,
                end: `${timelineYear}-05-10`,
                extendedProps: {
                    poolData: {
                        name: 'Pool C',
                        collection_rate: 88,
                        total_disbursed: 320000,
                        total_collected: 281600,
                        total_checkouts: 220,
                        status: 'active'
                    }
                }
            }
        ];
    }

    function generateTimeline(pools) {
        const container = document.getElementById('pools-timeline');
        container.innerHTML = '';

        // Month names
        const monthNames = [
            '{{ translate("Jan") }}', '{{ translate("Feb") }}', '{{ translate("Mar") }}',
            '{{ translate("Apr") }}', '{{ translate("May") }}', '{{ translate("Jun") }}',
            '{{ translate("Jul") }}', '{{ translate("Aug") }}', '{{ translate("Sep") }}',
            '{{ translate("Oct") }}', '{{ translate("Nov") }}', '{{ translate("Dec") }}'
        ];

        // Create months header row
        const headerRow = document.createElement('div');
        headerRow.className = 'row timeline-months-header g-0';
        
        monthNames.forEach(month => {
            const monthCol = document.createElement('div');
            monthCol.className = 'col timeline-month';
            monthCol.textContent = month;
            headerRow.appendChild(monthCol);
        });
        
        container.appendChild(headerRow);

        // Create timeline container with relative positioning
        const timelineBody = document.createElement('div');
        timelineBody.style.position = 'relative';
        timelineBody.style.minHeight = '200px';

        // Add grid lines
        const gridLines = document.createElement('div');
        gridLines.className = 'timeline-grid-lines';
        
        for (let i = 1; i < 12; i++) {
            const line = document.createElement('div');
            line.className = 'grid-line';
            line.style.left = `${(i / 12) * 100}%`;
            gridLines.appendChild(line);
        }
        timelineBody.appendChild(gridLines);

        // Process each pool
        pools.forEach((pool, index) => {
            const poolElement = createTimelinePoolBadge(pool, index);
            timelineBody.appendChild(poolElement);
        });

        container.appendChild(timelineBody);
    }

    function createTimelinePoolBadge(pool, rowIndex) {
        const poolData = pool.extendedProps.poolData;
        const startDate = new Date(pool.start);
        const endDate = new Date(pool.end);
        
        // Calculate position and width
        const startMonth = startDate.getMonth(); // 0-11
        const endMonth = endDate.getMonth(); // 0-11
        
        // Handle year spanning
        let monthSpan;
        if (endDate.getFullYear() > startDate.getFullYear()) {
            monthSpan = (12 - startMonth);
        } else {
            monthSpan = (endMonth - startMonth) + 1;
        }
        
        // Ensure minimum width and maximum span
        monthSpan = Math.max(1, Math.min(12, monthSpan));
        
        // Calculate position as percentage
        const leftPosition = (startMonth / 12) * 100;
        const width = (monthSpan / 12) * 100;

        // Create badge element
        const badge = document.createElement('div');
        badge.className = 'pool-badge';
        
        // Position the badge
        badge.style.left = leftPosition + '%';
        badge.style.width = width + '%';
        badge.style.top = (rowIndex * 40) + 'px';
        
        // Add performance styling
        const rate = parseFloat(poolData.collection_rate || 0);
        if (poolData.status !== 'active') {
            badge.classList.add('pool-badge-closed');
        } else if (rate >= 90) {
            badge.classList.add('pool-badge-high');
        } else if (rate >= 70) {
            badge.classList.add('pool-badge-medium');
        } else {
            badge.classList.add('pool-badge-low');
        }

        // Badge content
        badge.textContent = pool.title;
        badge.title = `${pool.title}\n{{ translate('Period') }}: ${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()}\n{{ translate('Collection Rate') }}: ${rate}%\n{{ translate('Amount') }}: ${parseFloat(poolData.total_disbursed || 0).toLocaleString()} SR`;

        // Click handler
        badge.addEventListener('click', () => {
            showPoolDetails(poolData);
        });

        return badge;
    }

    // Show pool details modal
    function showPoolDetails(pool) {
        const modal = new bootstrap.Modal(document.getElementById('poolDetailsModal'));
        const content = document.getElementById('poolDetailsContent');
        
        const statusClass = pool.status === 'active' ? 'badge-light-success' : 
                           pool.status === 'closed' ? 'badge-light-secondary' : 'badge-light-danger';
        
        const rateClass = pool.collection_rate >= 90 ? 'text-success' : 
                         pool.collection_rate >= 70 ? 'text-warning' : 'text-danger';

        content.innerHTML = `
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="text-gray-600 mb-3">{{ translate('Pool Information') }}</h6>
                    <div class="mb-3">
                        <label class="form-label fs-7 fw-bold text-gray-600">{{ translate('Pool Name') }}</label>
                        <div class="fs-6 fw-bold text-gray-800">${pool.name}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-7 fw-bold text-gray-600">{{ translate('Status') }}</label>
                        <div><span class="badge ${statusClass} fs-7">${pool.status.charAt(0).toUpperCase() + pool.status.slice(1)}</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-7 fw-bold text-gray-600">{{ translate('Period') }}</label>
                        <div class="fs-6 text-gray-800">
                            ${new Date(pool.start_date).toLocaleDateString()} - ${new Date(pool.end_date).toLocaleDateString()}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-gray-600 mb-3">{{ translate('Financial Metrics') }}</h6>
                    <div class="mb-3">
                        <label class="form-label fs-7 fw-bold text-gray-600">{{ translate('Total Disbursed') }}</label>
                        <div class="fs-4 fw-bold text-gray-800">${parseFloat(pool.total_disbursed || 0).toLocaleString()} SR</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-7 fw-bold text-gray-600">{{ translate('Total Collected') }}</label>
                        <div class="fs-6 fw-bold text-success">${parseFloat(pool.total_collected || 0).toLocaleString()} SR</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-7 fw-bold text-gray-600">{{ translate('Collection Rate') }}</label>
                        <div class="fs-2 fw-bolder ${rateClass}">${pool.collection_rate || 0}%</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fs-7 fw-bold text-gray-600">{{ translate('Total Checkouts') }}</label>
                        <div class="fs-6 fw-bold text-gray-800">${pool.total_checkouts || 0}</div>
                    </div>
                </div>
            </div>
        `;
        
        modal.show();
    }

    // Open add pool modal
    function openAddPoolModal(dateStr) {
        const modal = new bootstrap.Modal(document.getElementById('addPoolModal'));
        const form = document.getElementById('addPoolForm');
        
        // Pre-fill start date if provided
        if (dateStr) {
            form.querySelector('[name="start_date"]').value = dateStr;
        }
        
        modal.show();
    }

    // Timeline navigation handlers
    document.getElementById('timeline-year-prev').addEventListener('click', () => {
        timelineYear--;
        renderTimeline();
    });

    document.getElementById('timeline-year-next').addEventListener('click', () => {
        timelineYear++;
        renderTimeline();
    });

    // Handle add pool button
    document.getElementById('add-pool-btn').addEventListener('click', function() {
        openAddPoolModal();
    });

    // Handle add pool form submission
    document.getElementById('addPoolForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        fetch('/admin/investment-pools', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Close modal and refresh timeline
                bootstrap.Modal.getInstance(document.getElementById('addPoolModal')).hide();
                renderTimeline();
                
                // Show success message
                if (typeof toastr !== 'undefined') {
                    toastr.success('{{ translate("Pool created successfully") }}');
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(result.message || '{{ translate("Error creating pool") }}');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('{{ translate("Error creating pool") }}');
            }
        });
    });

    // Initialize timeline on page load
    renderTimeline();
});
</script>
@endpush