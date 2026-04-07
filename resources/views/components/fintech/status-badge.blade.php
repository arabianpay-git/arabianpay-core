@props([
    'status' => 'unknown',
    'size' => 'sm',    // sm, base
    'pulse' => false,  // animate for urgent statuses
])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $config = match($value) {
        'draft'              => ['bg' => 'badge-light',    'icon' => 'ki-note-2',       'label' => 'Draft'],
        'pending'            => ['bg' => 'badge-warning',  'icon' => 'ki-time',         'label' => 'Pending'],
        'pending_approval'   => ['bg' => 'badge-warning',  'icon' => 'ki-time',         'label' => 'Pending Approval'],
        'under_review'       => ['bg' => 'badge-warning',  'icon' => 'ki-search-list',  'label' => 'Under Review'],
        'approved'           => ['bg' => 'badge-primary',  'icon' => 'ki-check-circle', 'label' => 'Approved'],
        'current'            => ['bg' => 'badge-primary',  'icon' => 'ki-check',        'label' => 'Current'],
        'processing'         => ['bg' => 'badge-info',     'icon' => 'ki-loading',      'label' => 'Processing'],
        'paid', 'completed'  => ['bg' => 'badge-success',  'icon' => 'ki-verify',       'label' => ucfirst($value)],
        'cancelled'          => ['bg' => 'badge-danger',   'icon' => 'ki-cross-circle', 'label' => 'Cancelled'],
        'rejected'           => ['bg' => 'badge-danger',   'icon' => 'ki-cross',        'label' => 'Rejected'],
        'failed'             => ['bg' => 'badge-danger',   'icon' => 'ki-disconnect',   'label' => 'Failed'],
        'overdue', 'late'    => ['bg' => 'badge-danger',   'icon' => 'ki-notification', 'label' => ucfirst($value)],
        'unpaid'             => ['bg' => 'badge-light',    'icon' => 'ki-dollar',       'label' => 'Unpaid'],
        'due'                => ['bg' => 'badge-warning',  'icon' => 'ki-calendar',     'label' => 'Due'],
        'partially_paid'     => ['bg' => 'badge-info',     'icon' => 'ki-chart',        'label' => 'Partially Paid'],
        'refunded'           => ['bg' => 'badge-secondary','icon' => 'ki-arrow-left',   'label' => 'Refunded'],
        'seller_approved'    => ['bg' => 'badge-info',     'icon' => 'ki-check',        'label' => 'Seller Approved'],
        'revoked'            => ['bg' => 'badge-danger',   'icon' => 'ki-shield-cross', 'label' => 'Revoked'],
        'active'             => ['bg' => 'badge-success',  'icon' => 'ki-check-circle', 'label' => 'Active'],
        'suspended'          => ['bg' => 'badge-danger',   'icon' => 'ki-lock',         'label' => 'Suspended'],
        'blacklisted'        => ['bg' => 'badge-danger',   'icon' => 'ki-shield-cross', 'label' => 'Blacklisted'],
        default              => ['bg' => 'badge-secondary','icon' => 'ki-information',  'label' => ucfirst(str_replace('_', ' ', $value))],
    };

    $sizeClass = $size === 'sm' ? 'badge-sm' : '';
    $pulseClass = ($pulse || in_array($value, ['overdue', 'late', 'failed'])) ? 'animate-pulse' : '';
@endphp

<span {{ $attributes->merge(['class' => "badge badge-outline {$config['bg']} {$sizeClass} {$pulseClass}"]) }}>
    <i class="ki-filled {{ $config['icon'] }}"></i>
    {{ $config['label'] }}
</span>
