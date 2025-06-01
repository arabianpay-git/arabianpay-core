{{-- resources/views/admin/products/bulk-upload.blade.php --}}
@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            /* In your main CSS file (e.g. app.css) */

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
                /* gray-200 */
                color: #6b7280;
                /* gray-500 */
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
                /* gray-300 */
                color: #374151;
                /* gray-700 */
            }



            /* Highlight a cell as a drop target */
            .drop-target {
                border: 2px dashed #3B82F6;
                /* Tailwind’s blue-500 */
                background-color: #EFF6FF;
                /* Tailwind’s blue-50 */
            }
        </style>
    @endpush
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ __('Product Bulk Upload') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('products.create') }}">
                        {{ __('Create New Product') }}
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
                                {{ __('Products Upload Form') }}
                            </h3>
                            <a href="{{ asset('assets/media/sample-product.xlsx') }}" download="sample-product.xlsx"
                                class="btn btn-sm btn-light flex items-center gap-1">
                                <i class="ki-filled ki-files"></i>
                                {{ __('Sample File') }}
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
                                                        {{ __('Bulk upload failed with the following errors:') }}
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

                                <!-- File Upload Field -->
                                <div>
                                    <label for="file" class="block text-sm font-semibold text-gray-800 mb-2">
                                        {{ __('Upload CSV File') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" name="file" id="file" accept=".csv,.xlsx,text/csv"
                                        class="block w-full file:border-0 file:px-4 file:py-2 file:bg-blue-600 file:text-white file:font-medium file:rounded-lg hover:file:bg-blue-700 border border-gray-300 rounded-lg text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 p-2.5">
                                    @error('file')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Submit & Link -->
                                <div class="flex items-center gap-4 mt-4">
                                    <button type="submit" class="btn btn-sm btn-outline btn-primary">
                                        <i class="ki-filled ki-exit-up"></i>
                                        {{ __('Upload Now') }}
                                    </button>
                                </div>

                                <!-- Success Message -->
                                @if (session('success'))
                                    <div class="mt-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                                        <strong>{{ __('Success:') }}</strong> {{ session('success') }}
                                    </div>
                                @endif

                                <!-- Error Message -->
                                @if ($errors->any())
                                    <div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                                        <strong>{{ __('Error:') }}</strong> {{ $errors->first('file') }}
                                    </div>
                                @endif
                            </form>

                        </div>
                    </div>
                </div>
                @if (!empty($parsedData))
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">
                                {{ __('Uploaded Products Preview') }}
                            </h3>
                        </div>
                        <div class="card-body mt-8">
                            <div id="team_crew_table">
                                <div class="overflow-x-auto">
                                    <form action="{{ route('productsBulkStore') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf

                                        <table class="min-w-full border border-gray-300 bg-white" id="editable-table">
                                            <thead class="bg-gray-100 text-gray-700 text-sm font-semibold">
                                                <tr>
                                                    @foreach ($header as $col)
                                                        <th class="border px-4 py-2">
                                                            {{ __(ucwords(str_replace('_', ' ', $col))) }}
                                                        </th>
                                                    @endforeach
                                                    <th class="border px-4 py-2">{{ __('Category') }}</th>
                                                    <th class="border px-4 py-2">{{ __('Brand') }}</th>
                                                    <th class="border px-4 py-2">{{ __('Thumbnail') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="editable-table-body">
                                                @foreach ($parsedData as $rowIndex => $row)
                                                    <tr class="text-sm text-gray-800">
                                                        @foreach ($header as $colIndex => $col)
                                                            <td class="cell-wrapper border px-4 py-2 group relative"
                                                                data-row="{{ $rowIndex }}"
                                                                data-col="{{ $colIndex }}">
                                                                <!-- Drag handle appears on hover -->
                                                                <span class="drag-handle" title="Drag cell"
                                                                    draggable="true">⠿</span>

                                                                <!-- Actual editable content -->
                                                                <div class="cell-content" contenteditable="true"
                                                                    data-input-name="products[{{ $rowIndex }}][{{ $col }}]">
                                                                    {{ $row[$col] ?? '' }}
                                                                </div>

                                                                <!-- Hidden input for form submission -->
                                                                <input type="hidden"
                                                                    name="products[{{ $rowIndex }}][{{ $col }}]"
                                                                    value="{{ $row[$col] ?? '' }}">
                                                            </td>
                                                        @endforeach

                                                        {{-- Category --}}
                                                        <td class="border px-4 py-2">
                                                            <select name="products[{{ $rowIndex }}][category_id]"
                                                                class="w-full text-sm rounded border-gray-300">
                                                                <option value="">Select Category</option>
                                                                @foreach ($categories as $category)
                                                                    <option value="{{ $category->id }}"
                                                                        {{ old("products.$rowIndex.category_id") == $category->id ? 'selected' : '' }}>
                                                                        {{ $category->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>

                                                        {{-- Brand --}}
                                                        <td class="border px-4 py-2">
                                                            <select name="products[{{ $rowIndex }}][brand_id]"
                                                                class="w-full text-sm rounded border-gray-300">
                                                                <option value="">Select Brand</option>
                                                                @foreach ($brands as $brand)
                                                                    <option value="{{ $brand->id }}"
                                                                        {{ old("products.$rowIndex.brand_id") == $brand->id ? 'selected' : '' }}>
                                                                        {{ $brand->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>

                                                        {{-- Thumbnail --}}
                                                        <td class="border px-4 py-2">
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

                                        <div class="mt-4 p-2">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                {{ __('Submit') }}
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
                            {{ __('Products Example') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th>
                                                {{ __('Column Name') }}
                                            </th>

                                            <th class="text-center">
                                                {{ __('Description') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">name <span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ __('The product’s display name.') }} <br>
                                                <strong>{{ __('Example:') }}</strong> <code>T-Shirt</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">unit_price <span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ __('Selling price for one unit (decimal).') }}<br>
                                                <strong>{{ __('Example:') }}</strong> <code>100.00</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">purchase_price <span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ __('Cost price you paid (decimal).') }}<br>
                                                <strong>{{ __('Example:') }}</strong> <code>70.00</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">category_name <span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ __('Must match an existing Category’s') }} <code>name</code><br>
                                                <strong>{{ __('Example:') }}</strong> <code>Clothing</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">brand_name<span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ __('Must match an existing Brand’s') }} <code>name</code><br>
                                                <strong>{{ __('Example:') }}</strong> <code>Nike</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">description</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ __('A brief description or details about the product (optional).') }}<br>
                                                <strong>{{ __('Example:') }}</strong> <code>High quality cotton
                                                    t-shirt</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">unit<span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ __('The unit of measurement for the product.') }}<br>
                                                <strong>{{ __('Example:') }}</strong> <code>PC</code>, <code>KG</code>,
                                                <code>Liters</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">stock<span
                                                    class="text-danger">*</span></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ __('Initial stock quantity available for the product.') }}<br>
                                                <strong>{{ __('Example:') }}</strong> <code>100</code>
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
            let draggedCell = null; // the <td> containing the cell being dragged
            let draggedContent = null; // the <span class="cell-content"> inside draggedCell

            // 1) Attach dragstart to each handle
            document.querySelectorAll('.drag-handle').forEach(handle => {
                handle.addEventListener('dragstart', (e) => {
                    // Find the parent <td> of this handle
                    draggedCell = handle.closest('td');
                    draggedContent = draggedCell.querySelector('.cell-content');
                    e.dataTransfer.effectAllowed = 'move';
                });
            });

            // 2) Attach dragover, dragleave, drop, dragend to every <td>
            const allCells = document.querySelectorAll('#editable-table-body td');

            allCells.forEach(cell => {
                cell.addEventListener('dragover', (e) => {
                    e.preventDefault(); // allow dropping
                    if (cell !== draggedCell) {
                        cell.classList.add('drop-target');
                        e.dataTransfer.dropEffect = 'move';
                    }
                });

                cell.addEventListener('dragleave', () => {
                    cell.classList.remove('drop-target');
                });

                cell.addEventListener('drop', (e) => {
                    e.preventDefault();
                    cell.classList.remove('drop-target');

                    if (!draggedCell || cell === draggedCell) {
                        draggedCell = null;
                        return;
                    }

                    const targetContent = cell.querySelector('.cell-content');
                    if (draggedContent && targetContent) {
                        // Swap innerHTML of the content spans
                        const temp = targetContent.innerHTML;
                        targetContent.innerHTML = draggedContent.innerHTML;
                        draggedContent.innerHTML = temp;
                    }

                    draggedCell = null;
                    draggedContent = null;
                });

                cell.addEventListener('dragend', () => {
                    // Remove drop-target class from all cells
                    allCells.forEach(c => c.classList.remove('drop-target'));
                    draggedCell = null;
                    draggedContent = null;
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
@endpush
