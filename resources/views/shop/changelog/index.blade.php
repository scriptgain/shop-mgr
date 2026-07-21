<x-layouts.shop title="Changelog">
    @include('shop.help._prose-style')

    {{-- Hero --}}
    <section class="border-b border-shop-line bg-slate-50">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-10">
            <div class="flex flex-wrap items-center gap-3">
                <p class="vx-eyebrow text-brand-600">What's New</p>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-white ring-1 ring-inset ring-brand-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-white"></span> Alpha
                </span>
            </div>
            <h1 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight text-shop-ink">Changelog</h1>
            <p class="mt-2 max-w-2xl text-shop-muted">Every release we ship, newest first. Follow along as the store gets better.</p>
        </div>
    </section>

    {{-- Alpha banner --}}
    <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <div class="flex items-start gap-3 rounded-2xl bg-brand-50 p-4 ring-1 ring-inset ring-brand-200">
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                <x-icon name="info" class="h-4 w-4" aria-hidden="true" />
            </span>
            <div class="min-w-0 text-sm">
                <p class="font-semibold text-shop-ink">This Product Is In Active Alpha Development</p>
                <p class="mt-1 text-shop-muted">Features are still landing and may change between releases. Thanks for building your store with us early. The list below is everything we have shipped so far.</p>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if ($entries->isEmpty())
            <div class="rounded-2xl bg-white p-10 text-center ring-1 ring-shop-line">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                    <x-icon name="star" class="h-5 w-5" aria-hidden="true" />
                </span>
                <p class="mt-4 font-semibold text-shop-ink">No Release Notes Yet</p>
                <p class="mt-1 text-sm text-shop-muted">Check back soon to see what's new.</p>
            </div>
        @else
            <ol class="relative border-l border-shop-line">
                @foreach ($entries as $entry)
                    <li class="ml-6 pb-10 last:pb-0">
                        {{-- Node --}}
                        <span class="absolute -left-[7px] mt-1.5 flex h-3.5 w-3.5 items-center justify-center rounded-full {{ $loop->first ? 'bg-brand-600 ring-4 ring-brand-100' : 'bg-slate-300 ring-4 ring-white' }}"></span>

                        <div class="rounded-2xl bg-white p-6 ring-1 ring-shop-line">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular text-slate-700 ring-1 ring-inset ring-slate-200">v{{ $entry->version }}</span>
                                @if ($loop->first)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-white">
                                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span> Current Alpha
                                    </span>
                                @endif
                                <time datetime="{{ $entry->released_on?->format('Y-m-d') }}" class="text-sm text-shop-muted">{{ $entry->released_on?->format(config('shop.date_format', 'M j, Y')) }}</time>
                            </div>

                            <h2 class="mt-3 text-xl font-semibold tracking-tight text-shop-ink">{{ $entry->title }}</h2>
                            @if ($entry->summary)
                                <p class="mt-1 text-shop-muted">{{ $entry->summary }}</p>
                            @endif

                            @if ($entry->body_html)
                                <div class="mt-4 shop-prose">
                                    {!! $entry->body_html !!}
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

</x-layouts.shop>
