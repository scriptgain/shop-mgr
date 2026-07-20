@props(['icon' => 'folder', 'title' => 'Nothing Here Yet', 'description' => null])
{{-- Icon sits to the left of the copy, never stacked above centred text. --}}
<div {{ $attributes->merge(['class' => 'flex items-start gap-4 text-left py-12 px-6']) }}>
    <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-slate-100 text-slate-400 ring-1 ring-slate-200 shrink-0">
        <x-icon :name="$icon" class="w-6 h-6" />
    </span>
    <div class="min-w-0">
        <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
        @if ($description)<p class="mt-1 text-sm text-slate-500 max-w-sm">{{ $description }}</p>@endif
        @isset($action)<div class="mt-5 flex">{{ $action }}</div>@endisset
    </div>
</div>
