@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            .drag-handle {
                position: absolute;
                top: 0.25rem;
                left: 0.25rem;
                width: 1.5rem;
                height: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 9999px;
                background-color: #e5e7eb;
                color: #6b7280;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                cursor: grab;
                opacity: 0;
                transition: opacity 0.2s ease, background-color 0.2s, color 0.2s;
                z-index: 10;
            }

            .cell-wrapper:hover .drag-handle {
                opacity: 1;
            }

            .drag-handle:hover {
                background-color: #d1d5db;
                color: #374151;
            }

            .drop-target {
                border: 2px dashed #3B82F6;
                background-color: #EFF6FF;
            }

            .remove-col {
                position: absolute;
                top: 0.25rem;
                right: 0.25rem;
                width: 1.5rem;
                height: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 9999px;
                background-color: #fee2e2;
                color: #dc2626;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                cursor: pointer;
                opacity: 0;
                transition: opacity 0.2s ease, background-color 0.2s, color 0.2s;
                z-index: 10;
            }

            .cell-wrapper:hover .remove-col {
                opacity: 1;
            }

            .remove-col:hover {
                background-color: #fecaca;
                color: #b91c1c;
            }

            /* Bulk selection styles */
            .bulk-toolbar {
                display: none;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 0.5rem;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                color: white;
            }

            .bulk-toolbar.active {
                display: flex;
            }

            .row-checkbox {
                width: 1.125rem;
                height: 1.125rem;
                cursor: pointer;
                border-radius: 0.25rem;
            }

            .selected-row {
                background-color: #eff6ff !important;
            }
        </style>
    @endpush

    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Product Bulk Upload') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('products.create') }}">
                        {{ translate('Create New Product') }}
                    </a>
                </div>
            </div>
        </div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">

                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <div class="flex items-center justify-between w-full">
                            <h3 class="card-title font-medium text-sm">
                                {{ translate('Products Upload Form') }}
                            </h3>
                            <a href="{{ asset('assets/media/sample-product.xlsx') }}" download="sample-product.xlsx"
                                class="btn btn-sm btn-light flex items-center gap-1">
                                <i class="ki-filled ki-files"></i>
                                {{ translate('Sample File') }}
                            </a>

                        </div>
                    </div>

                    <div class="card-body">
                        <div class="p-4">
                            @if (session('bulk_errors'))
                                <div class="mb-5">
                                    <div class="card rounded-xl">
                                        <div class="p-5 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                                            <div class="flex items-start gap-3">
                                                <i class="ki-filled ki-information-4 text-2xl text-red-600 mt-1"></i>
                                                <div>
                                                    <span class="font-semibold block mb-2">
                                                        {{ translate('Bulk upload failed with the following errors:') }}
                                                    </span>
                                                    <ul class="list-disc pl-5 space-y-1 text-sm">
                                                        @foreach (session('bulk_errors') as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="grid gap-5 lg:gap-7.5 mb-5">
                                    <div class="card rounded-xl">
                                        <div
                                            class="flex items-center flex-wrap sm:flex-wrap justify-between grow gap-2 p-5 rtl:[background-position:-30%_41%] [background-position:121%_41%] bg-no-repeat bg-[length:660px_310px] upgrade-bg">
                                            <div class="flex items-center gap-4">
                                                <div class="relative size-[50px] shrink-0">
                                                    <svg class="w-full h-full stroke-brand-clarity fill-brand-light"
                                                        fill="none" height="48" viewBox="0 0 44 48" width="44"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                                            fill=""></path>
                                                        <path
                                                            d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                                            stroke=""></path>
                                                    </svg>
                                                    <div
                                                        class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                                        <i class="ki-filled ki-information-4 text-xl text-brand"> </i>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col gap-1.5">
                                                    <div class="flex items-center flex-wrap gap-2.5">
                                                        <a class="text-base font-medium text-gray-900 hover:text-primary-active"
                                                            href="#">
                                                            Please fix the following errors:
                                                        </a>
                                                    </div>
                                                    <div class="text-2sm text-gray-800">
                                                        <ul class="mb-0">
                                                            @foreach ($errors->all() as $error)
                                                                <li class="text-danger">{{ $error }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <form action="{{ route('products.bulk-upload') }}" method="POST" enctype="multipart/form-data"
                                class="space-y-6">
                                @csrf

                                <div class="flex items-center w-full max-w-md relative">
                                    <button type="button" id="selectFileBtn"
                                        class="absolute top-0 bottom-0 px-3 flex items-center justify-center hover:bg-primary-light hover:text-primary text-gray-500 rounded-r">
                                        <i class="ki-filled ki-folder text-xl"></i>
                                    </button>

                                    <input type="text" id="fileNameDisplay" class="input w-full"
                                        placeholder="{{ translate('Click to select excel file') }}" readonly
                                        style="padding-inline-start: 2.75rem;">

                                    <input type="file" id="fileInput" name="file" class="hidden"
                                        accept=".csv,.txt,.xlsx">

                                    @error('file')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="flex items-center gap-4 mt-4">
                                    <button type="submit" class="btn btn-sm btn-outline btn-primary">
                                        <i class="ki-filled ki-exit-up"></i>
                                        {{ translate('Upload Now') }}
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
                @if (!empty($parsedData))
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">
                                {{ translate('Uploaded Products Preview') }}
                            </h3>
                        </div>
                        <div class="card-body mt-8">
                            <div id="team_crew_table">
                                <div class="overflow-x-auto">
                                    <form action="{{ route('productsBulkStore') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf

                                        <!-- Bulk Action Toolbar -->
                                        <div class="bulk-toolbar" id="bulkToolbar">
                                            <div class="flex items-center justify-between w-full gap-4 flex-wrap">
                                                <div class="flex items-center gap-2">
                                                    <i class="ki-filled ki-check-circle text-2xl"></i>
                                                    <span class="font-semibold" id="selectedCount">0 </span>{{ translate('products selected')}}
                                                </div>
                                                <div class="flex items-center gap-3 flex-wrap">
                                                    <div class="flex items-center gap-2">
                                                        <label class="text-sm font-medium">{{ translate('Category') }}</label>
                                                        <select id="bulkCategory" class="select bg-white text-gray-900 border-0 rounded px-3 py-2">
                                                            <option value="">{{ translate('Select Category') }}</option>
                                                            @foreach ($categories as $category)
                                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <label class="text-sm font-medium">{{ translate('Brand') }}</label>
                                                        <select id="bulkBrand" class="select bg-white text-gray-900 border-0 rounded px-3 py-2">
                                                            <option value="">{{ translate('Select Brand') }}</option>
                                                            @foreach ($brands as $brand)
                                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <button type="button" id="applyBulk" class="btn btn-sm btn-light">
                                                        <i class="ki-filled ki-check"></i>
                                                        {{ translate('Apply to Selected') }}
                                                    </button>
                                                    <button type="button" id="clearSelection" class="btn btn-sm btn-light">
                                                        <i class="ki-filled ki-cross"></i>
                                                        {{ translate('Clear Selection') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <table class="min-w-full border border-gray-300 bg-white" id="editable-table">
                                            <thead class="bg-gray-100 text-gray-700 text-sm font-semibold">
                                                <tr>
                                                    <th class="border px-4 py-2 w-12">
                                                        <input type="checkbox" id="selectAll" class="row-checkbox" title="{{ translate('Select All') }}">
                                                    </th>
                                                    <th class="border px-4 py-2" data-col="0">{{ translate('Name') }}
                                                    </th>
                                                    <th class="border px-4 py-2" data-col="1">
                                                        {{ translate('Unit Price') }}
                                                    </th>
                                                    <th class="border px-4 py-2" data-col="2">
                                                        {{ translate('Description') }}
                                                    </th>
                                                    <th class="border px-4 py-2" data-col="3">{{ translate('Unit') }}
                                                    </th>
                                                    <th class="border px-4 py-2" data-col="4">{{ translate('Stock') }}
                                                    </th>
                                                    <th class="border px-4 py-2" data-col="5">
                                                        {{ translate('Category') }}</th>
                                                    <th class="border px-4 py-2" data-col="6">{{ translate('Brand') }}
                                                    </th>
                                                    <th class="border px-4 py-2" data-col="7">
                                                        {{ translate('Thumbnail') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="editable-table-body">
                                                @foreach ($parsedData as $rowIndex => $row)
                                                    <tr class="text-sm text-gray-800" data-row-index="{{ $rowIndex }}">
                                                        <td class="border px-4 py-2 text-center">
                                                            <input type="checkbox" class="row-checkbox product-checkbox" data-row="{{ $rowIndex }}">
                                                        </td>
                                                        @foreach ($header as $colIndex => $col)
                                                            <td class="cell-wrapper border px-4 py-2 group relative"
                                                                data-row="{{ $rowIndex }}"
                                                                data-col="{{ $colIndex }}">

                                                                <span class="drag-handle" title="Drag column"
                                                                    draggable="true">⠿</span>

                                                                <span class="remove-col" title="Remove column">✖</span>

                                                                <div class="cell-content" contenteditable="true"
                                                                    data-input-name="products[{{ $rowIndex }}][{{ $col }}]">
                                                                    {{ $row[$col] ?? '' }}
                                                                </div>

                                                                <input type="hidden"
                                                                    name="products[{{ $rowIndex }}][{{ $col }}]"
                                                                    value="{{ $row[$col] ?? '' }}">
                                                            </td>
                                                        @endforeach

                                                        <td class="border px-4 py-2" data-col="{{ count($header) }}">
                                                            <select name="products[{{ $rowIndex }}][category_id]"
                                                                class="w-full text-sm rounded border-gray-300 select"
                                                                >
                                                                <option value="">{{ translate('Select Category') }}
                                                                </option>
                                                                @foreach ($categories as $category)
                                                                    <option value="{{ $category->id }}"
                                                                        {{ old("products.$rowIndex.category_id") == $category->id ? 'selected' : '' }}>
                                                                        {{ $category->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>

                                                        <td class="border px-4 py-2" data-col="{{ count($header) + 1 }}">
                                                            <select name="products[{{ $rowIndex }}][brand_id]"
                                                                class="w-full text-sm rounded border-gray-300 select"
                                                                >
                                                                <option value="">{{ translate('Select Brand') }}
                                                                </option>
                                                                @foreach ($brands as $brand)
                                                                    <option value="{{ $brand->id }}"
                                                                        {{ old("products.$rowIndex.brand_id") == $brand->id ? 'selected' : '' }}>
                                                                        {{ $brand->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>

                                                        <td class="border px-4 py-2" data-col="{{ count($header) + 2 }}">
                                                            @include('media.single', [
                                                                'name' => "products[$rowIndex][thumbnail]",
                                                                'label' => '',
                                                                'required' => false,
                                                                'value' => old("products.$rowIndex.thumbnail"),
                                                                'info' => '',
                                                            ])
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>

                                        <div class="w-full mt-4 p-4">
                                            <div class="w-full">
                                                <select class="w-full border border-gray-300 rounded px-3 py-2"
                                                    name="user_id" id="user_id" required>
                                                    <option value="">{{ translate('Select Merchant') }}</option>
                                                    @foreach ($merchants as $merchant)
                                                        <option value="{{ $merchant->id }}"
                                                            {{ old('user_id') == $merchant->id ? 'selected' : '' }}>
                                                            {{ $merchant->business_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('user_id')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="mt-4 p-2 flex justify-end" style="margin-right: 10px;">
                                            <button type="submit" class="btn btn-sm btn-outline btn-primary">
                                                {{ translate('Submit Product') }}
                                            </button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Products Example') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th>
                                                {{ translate('Column Name') }}
                                            </th>

                                            <th class="text-center">
                                                {{ translate('Description') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Product Name <span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ translate('The product’s display name.') }} <br>
                                                <strong>{{ translate('Example:') }}</strong> <code>T-Shirt</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Unit Price <span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ translate('Selling price for one unit (decimal).') }}<br>
                                                <strong>{{ translate('Example:') }}</strong> <code>100.00</code>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Description</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ translate('A brief description or details about the product (optional).') }}<br>
                                                <strong>{{ translate('Example:') }}</strong> <code>High quality cotton
                                                    t-shirt</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Unit<span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ translate('The unit of measurement for the product.') }}<br>
                                                <strong>{{ translate('Example:') }}</strong> <code>PC</code>,
                                                <code>KG</code>,
                                                <code>Liters</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">Stock<span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ translate('Initial stock quantity available for the product.') }}<br>
                                                <strong>{{ translate('Example:') }}</strong> <code>100</code>
                                            </td>
                                        </tr>

                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let draggedColIndex = null;
            let currentTargetCol = null;

            // 1) Attach dragstart to every .drag-handle INSIDE <tbody> cells
            document.querySelectorAll('#editable-table-body .drag-handle').forEach(handle => {
                handle.addEventListener('dragstart', (e) => {
                    const td = handle.closest('td');
                    draggedColIndex = parseInt(td.getAttribute('data-col'));
                    e.dataTransfer.effectAllowed = 'move';
                });
            });

            // 2) Attach dragover, dragleave, drop, dragend to every <td> in tbody
            const allCells = document.querySelectorAll('#editable-table-body td');

            allCells.forEach(cell => {
                cell.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const targetColIndex = parseInt(cell.getAttribute('data-col'));
                    if (targetColIndex !== draggedColIndex) {
                        if (currentTargetCol !== targetColIndex) {
                            // Remove highlight from previous column (if any)
                            if (currentTargetCol !== null) {
                                document.querySelectorAll(
                                    `#editable-table-body td[data-col='${currentTargetCol}']`
                                ).forEach(td => td.classList.remove('drop-target'));
                            }
                            // Add highlight to all cells in new target column
                            document.querySelectorAll(
                                `#editable-table-body td[data-col='${targetColIndex}']`
                            ).forEach(td => td.classList.add('drop-target'));
                            currentTargetCol = targetColIndex;
                        }
                        e.dataTransfer.dropEffect = 'move';
                    }
                });

                cell.addEventListener('dragleave', (e) => {
                    const related = e.relatedTarget;
                    const leavingCol = parseInt(cell.getAttribute('data-col'));
                    if (leavingCol === currentTargetCol) {
                        // Check if moving to another cell in same column
                        if (!related || !related.closest || !related.closest('td') || parseInt(
                                related.closest('td').getAttribute('data-col')) !== leavingCol) {
                            document.querySelectorAll(
                                `#editable-table-body td[data-col='${currentTargetCol}']`
                            ).forEach(td => td.classList.remove('drop-target'));
                            currentTargetCol = null;
                        }
                    }
                });

                cell.addEventListener('drop', (e) => {
                    e.preventDefault();
                    if (currentTargetCol !== null) {
                        document.querySelectorAll(
                            `#editable-table-body td[data-col='${currentTargetCol}']`
                        ).forEach(td => td.classList.remove('drop-target'));
                        currentTargetCol = null;
                    }

                    const targetColIndex = parseInt(cell.getAttribute('data-col'));
                    if (draggedColIndex === null || targetColIndex === draggedColIndex) {
                        draggedColIndex = null;
                        return;
                    }

                    // Swap every <td> in every <tr> between those two columns in <tbody> only
                    document.querySelectorAll('#editable-table-body tr').forEach(row => {
                        const sourceTd = row.querySelector(
                            `td[data-col='${draggedColIndex}']`);
                        const targetTd = row.querySelector(
                            `td[data-col='${targetColIndex}']`);
                        if (sourceTd && targetTd) {
                            const sourceContentDiv = sourceTd.querySelector(
                                '.cell-content');
                            const targetContentDiv = targetTd.querySelector(
                                '.cell-content');
                            const tempContent = targetContentDiv.innerHTML;
                            targetContentDiv.innerHTML = sourceContentDiv.innerHTML;
                            sourceContentDiv.innerHTML = tempContent;

                            const sourceHidden = sourceTd.querySelector(
                                'input[type="hidden"]');
                            const targetHidden = targetTd.querySelector(
                                'input[type="hidden"]');
                            const tempHiddenVal = targetHidden.value;
                            targetHidden.value = sourceHidden.value;
                            sourceHidden.value = tempHiddenVal;
                        }
                    });

                    // Finally, swap the data-col attributes on the affected <td> elements
                    document.querySelectorAll('#editable-table-body tr').forEach(row => {
                        row.querySelectorAll('td').forEach(td => {
                            const thisCol = parseInt(td.getAttribute('data-col'));
                            if (thisCol === draggedColIndex) {
                                td.setAttribute('data-col', targetColIndex);
                            } else if (thisCol === targetColIndex) {
                                td.setAttribute('data-col', draggedColIndex);
                            }
                        });
                    });

                    draggedColIndex = null;
                });

                cell.addEventListener('dragend', () => {
                    if (currentTargetCol !== null) {
                        document.querySelectorAll(
                            `#editable-table-body td[data-col='${currentTargetCol}']`
                        ).forEach(td => td.classList.remove('drop-target'));
                        currentTargetCol = null;
                    }
                    allCells.forEach(c => c.classList.remove('drop-target'));
                    draggedColIndex = null;
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.cell-content').forEach(function(editableSpan) {
                editableSpan.addEventListener('input', function() {
                    const name = editableSpan.dataset.inputName;
                    const value = editableSpan.innerText.trim();
                    const hiddenInput = editableSpan.parentElement.querySelector(
                        `input[name="${name}"]`);
                    if (hiddenInput) {
                        hiddenInput.value = value;
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.remove-col').forEach(icon => {
                icon.addEventListener('click', function(e) {
                    e.stopPropagation();

                    const td = this.closest('td');
                    const colIndex = td.getAttribute('data-col');

                    document.querySelectorAll(`#editable-table-body td[data-col="${colIndex}"]`)
                        .forEach(cell => cell.remove());
                });
            });
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('fileInput');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const selectFileBtn = document.getElementById('selectFileBtn');

            // Trigger file input when button or input is clicked
            selectFileBtn.addEventListener('click', () => fileInput.click());
            fileNameDisplay.addEventListener('click', () => fileInput.click());

            // Show selected file name
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    fileNameDisplay.value = fileInput.files[0].name;
                } else {
                    fileNameDisplay.value = '';
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Choices('#user_id', {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });
        });
    </script>

    <script>
        // Bulk selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            const bulkToolbar = document.getElementById('bulkToolbar');
            const selectedCountEl = document.getElementById('selectedCount');
            const applyBulkBtn = document.getElementById('applyBulk');
            const clearSelectionBtn = document.getElementById('clearSelection');
            const bulkCategorySelect = document.getElementById('bulkCategory');
            const bulkBrandSelect = document.getElementById('bulkBrand');

            let selectedRows = new Set();

            // Update toolbar visibility and count
            function updateToolbar() {
                const count = selectedRows.size;
                if (count > 0) {
                    bulkToolbar.classList.add('active');
                    selectedCountEl.textContent = count + ' ' + (count === 1 ? 'product' : 'products') + ' selected';
                } else {
                    bulkToolbar.classList.remove('active');
                }

                // Update select all checkbox state
                if (count === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (count === productCheckboxes.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                }
            }

            // Handle individual checkbox change
            productCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const rowIndex = this.getAttribute('data-row');
                    const row = this.closest('tr');

                    if (this.checked) {
                        selectedRows.add(rowIndex);
                        row.classList.add('selected-row');
                    } else {
                        selectedRows.delete(rowIndex);
                        row.classList.remove('selected-row');
                    }

                    updateToolbar();
                });
            });

            // Handle select all checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    
                    productCheckboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                        const rowIndex = checkbox.getAttribute('data-row');
                        const row = checkbox.closest('tr');
                        
                        if (isChecked) {
                            selectedRows.add(rowIndex);
                            row.classList.add('selected-row');
                        } else {
                            selectedRows.delete(rowIndex);
                            row.classList.remove('selected-row');
                        }
                    });

                    updateToolbar();
                });
            }

            // Handle apply bulk action
            if (applyBulkBtn) {
                applyBulkBtn.addEventListener('click', function() {
                    const categoryId = bulkCategorySelect.value;
                    const brandId = bulkBrandSelect.value;

                    if (!categoryId && !brandId) {
                        alert('{{ translate("Please select a category or brand to apply.") }}');
                        return;
                    }

                    // Apply to selected rows
                    selectedRows.forEach(rowIndex => {
                        if (categoryId) {
                            const categorySelect = document.querySelector(`select[name="products[${rowIndex}][category_id]"]`);
                            if (categorySelect) {
                                categorySelect.value = categoryId;
                                // Trigger change event if using Choices.js or similar
                                const event = new Event('change', { bubbles: true });
                                categorySelect.dispatchEvent(event);
                            }
                        }

                        if (brandId) {
                            const brandSelect = document.querySelector(`select[name="products[${rowIndex}][brand_id]"]`);
                            if (brandSelect) {
                                brandSelect.value = brandId;
                                // Trigger change event if using Choices.js or similar
                                const event = new Event('change', { bubbles: true });
                                brandSelect.dispatchEvent(event);
                            }
                        }
                    });

                    // Clear selections and reset
                    clearSelections();

                    // Reset bulk dropdowns
                    bulkCategorySelect.value = '';
                    bulkBrandSelect.value = '';
                });
            }

            // Handle clear selection
            if (clearSelectionBtn) {
                clearSelectionBtn.addEventListener('click', function() {
                    clearSelections();
                });
            }

            function clearSelections() {
                productCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    const row = checkbox.closest('tr');
                    row.classList.remove('selected-row');
                });
                selectedRows.clear();
                updateToolbar();
            }
        });
    </script>
@endpush
