<link href="{{ asset('assets/media/images/favicon.png') }}" rel="apple-touch-icon" sizes="180x180" />
<link href="{{ asset('assets/media/images/favicon.png') }}" rel="icon" sizes="32x32" type="image/png" />
<link href="{{ asset('assets/media/images/favicon.png') }}" rel="icon" sizes="16x16" type="image/png" />
<link href="{{ asset('assets/media/images/favicon.png') }}" rel="shortcut icon" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
<link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" />

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@emran-alhaddad/saudi-riyal-font/index.css">

<style>
    @font-face {
        font-family: 'IBMPlexSansArabic';
        src: url('/assets/css/IBMPlexSansArabic-Regular.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }

    :lang(ar) {
        font-family: 'IBMPlexSansArabic', sans-serif;
    }
</style>
@stack('styles')

<style>
    .border-7 {
        border-radius: 7px;
    }

    .tab-btn {
        padding: 0.5rem 1rem;
        border-bottom: 2px solid transparent;
        font-weight: 500;
        color: #6B7280;
    }

    .tab-btn.active {
        border-color: #3B82F6;
        color: #111827;
        background-color: #F3F4F6;
    }

    .d-none {
        display: none;
    }

    .media-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        transition: box-shadow 0.2s ease;
        cursor: pointer;
        background-color: #fff;
    }

    .media-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .media-thumb {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }

    .media-info {
        padding: 8px 10px;
        font-size: 14px;
    }

    .media-info .name {
        font-weight: 500;
        color: #333;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .media-info .size {
        font-size: 12px;
        color: #888;
    }

    .media-grid {
        padding: 1rem;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
        max-height: 400px;
        overflow-y: auto;
    }

    .media-card.selected {
        border: 2px solid #28a745;
    }

    .media-card .overlay-check {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #28a745;
        color: white;
        border-radius: 50%;
        padding: 4px;
        font-size: 14px;
        display: none;
    }

    .media-card.selected .overlay-check {
        display: block;
    }

    #fileInput {
        display: none;
    }

    .modal-content {
        border-radius: 12px;
        overflow: hidden;
        border: none;
    }

    .modal-header {
        background-color: #f1f4f9;
        border-bottom: none;
    }

    .modal-footer {
        border-top: none;
    }

    .delete-selected-btn {
        background-color: #dc3545;
        border: none;
    }

    .delete-selected-btn.show {
        display: inline-block;
    }

    /* Preview Styling */
    .preview-card {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        padding: 10px;
    }

    .preview-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
        margin-right: 15px;
    }

    .preview-info {
        font-size: 14px;
    }

    .preview-info .name {
        font-weight: 500;
        color: #333;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .preview-info .size {
        font-size: 12px;
        color: #888;
    }

    tbody td:last-child {
        text-align: center;
    }

    .active-link {
        background-color: var(--tw-secondary-active);
        border-radius: 0.25rem;
    }

    .active-title,
    .active-icon {
        color: var(--tw-primary);
    }

    .bg-red-100 {
        background-color: #fee2e2;
    }

    .text-red-700 {
        color: #b91c1c;
    }

    .border-red-300 {
        border-color: #fca5a5;
    }

    .bg-green-100 {
        background-color: #d1fae5;
    }

    .text-green-700 {
        color: #047857;
    }

    .border-green-300 {
        border-color: #6ee7b7;
    }
</style>
