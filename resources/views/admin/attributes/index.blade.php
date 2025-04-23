@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>
    <!-- End of Container -->
    <!-- Container -->
    <div class="container-fixed">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Attributes
                </h1>
            </div>
        </div>
    </div>
    <!-- End of Container -->
    <!-- Container -->
    <div class="container-fixed flex gap-5 lg:gap-7.5">
        <div class="grid">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        Attributes 
                    </h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"> </i>
                                <input data-datatable-search="#team_crew_table" placeholder="Search users" type="text" value="" />
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border" data-datatable-table="true">
                                <thead>
                                    <tr>
                                        <th class="w-[60px] text-center">
                                            No
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Name
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Values
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>
                                        
                                        <th class="">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Action
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($attributes as $attribute)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                
                                            <td>{{ $attribute->name }}</td>
                                
                                            <td>
                                                @if($attribute->values->count())
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($attribute->values as $value)
                                                            <span class="badge badge-sm badge-gray-200">
                                                                {{ $value->value }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>                                            
                                            
                                            <td>
                                                <div class="flex gap-1">
                                                    <a class="btn btn-sm btn-icon btn-clear btn-primary" href="{{ route('attributes.edit', $attribute->id) }}">
                                                        <i class="ki-filled ki-notepad-edit"> </i>
                                                    </a>
                                                    <a class="btn btn-sm btn-icon btn-clear btn-success" href="{{ route('attributes.editAttributeValue', $attribute->id) }}">
                                                        <i class="ki-filled ki-setting-2"> </i>
                                                    </a>
                                                    <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn" href="{{ route('attributes.destroy', $attribute->id) }}">
                                                        <i class="ki-filled ki-trash"> </i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                
                            </table>
                        </div>
                        <!-- Pagination Footer -->
                        @include('layouts.includes.table-pagination', ['paginator' => $attributes])
                    </div>
                </div>
            </div>
        </div>

        <div class="flex grow">
            
            <div class="flex flex-col items-stretch grow">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">
                            Add New Attribute
                        </h3>
                    </div>
                    <form action="{{ route('attributes.store') }}" method="POST">
                        @csrf
                        <div class="card-body grid gap-5">
                            
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Attribute Name
                                    </label>
                                    <input class="input @error('name') border-red-500 @enderror" name="name" type="text" value="{{ old('name') }}" required />
                                </div>
                                @error('name')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                    
                            <div class="flex justify-end pt-2.5">
                                <button class="btn btn-primary">
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                        
                </div>
                
                
            </div>
        </div>
    
    </div>
    <!-- End of Container -->
</main>

@endsection
