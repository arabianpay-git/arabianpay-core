@foreach ($permissions as $subject => $perms)
    <div class="card card-grid min-w-full">
        <div class="card-header border-b border-gray-200 flex items-center justify-between">
            <h3 class="card-title font-medium text-base text-gray-800">
                {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', ucfirst($subject))) }}
            </h3>
            <label class="flex items-center gap-1 text-sm switch">
                <input type="checkbox" class="select-all-perms" data-target="perm-group-{{ $loop->index }}">
                Select All
            </label>
        </div>
        <div class="card-body flex flex-wrap gap-7 perm-group-{{ $loop->index }}" style="padding: 0.725rem;">
            @foreach ($perms as $perm)
                @php
                    $actionName = Str::headline($perm->name);
                    $isChecked = $role->permissions->contains($perm->id);
                @endphp
                <div class="flex items-center gap-2 p-2">
                    <span class="text-sm font-medium text-gray-700">
                        {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $actionName)) }}
                    </span>
                    <label class="switch">
                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                            {{ $isChecked ? 'checked' : '' }}>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
