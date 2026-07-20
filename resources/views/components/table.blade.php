@props(['flush' => false, 'minWidth' => 'min-w-[46rem]'])
{{-- Styled table. Consumers write plain <thead>/<tbody>/<th>/<td>; cell styling
     is applied automatically.

     Sizing: columns take their natural width and the table scrolls inside its
     own container once it stops fitting. The previous approach forced
     table-layout:fixed and ellipsised every cell, which gave a product name and
     a SKU an identical share of the row and cut both. Natural widths plus a
     contained scroll keeps the page free of horizontal scroll while letting the
     data stay legible.

     Rows opt into the exception rail with class="vx-rail vx-rail-warn". --}}
@once
    <style>
        .vx-table{width:100%;border-collapse:separate;border-spacing:0;}
        .vx-table th,.vx-table td{white-space:nowrap;}
        /* Prose cells may wrap; number and control cells stay on one line. */
        .vx-table .vx-wrap{white-space:normal;}
        /* Selection and action columns hug their controls instead of taking an
           equal share of the row. */
        .vx-table th.vx-col-select,.vx-table td.vx-col-select{width:1%;padding-inline-end:0;}
        .vx-table th.vx-col-actions,.vx-table td.vx-col-actions{width:1%;text-align:end;}
        /* Legacy column hints from views not yet migrated. */
        .vx-table th.w-10,.vx-table td.w-10{width:1%;}
    </style>
@endonce
<div class="{{ $flush ? '' : 'overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200' }}">
    <div class="vx-scroll overflow-x-auto">
        <table
            {{ $attributes->merge(['class' =>
                'vx-table text-left text-sm tabular ' . $minWidth . ' '
                . '[&_thead_th]:bg-slate-50/80 [&_thead_th]:px-4 [&_thead_th]:py-2.5 [&_thead_th]:font-semibold [&_thead_th]:text-[11px] [&_thead_th]:uppercase [&_thead_th]:tracking-[0.07em] [&_thead_th]:text-slate-400 [&_thead_th]:border-b [&_thead_th]:border-slate-200 '
                . '[&_tbody_tr]:border-b [&_tbody_tr]:border-slate-100 [&_tbody_tr:last-child]:border-0 [&_tbody_tr:hover]:bg-slate-50/70 '
                . '[&_tbody_td]:px-4 [&_tbody_td]:py-3 [&_tbody_td]:text-slate-700 [&_tbody_td]:align-middle']) }}>
            {{ $slot }}
        </table>
    </div>
</div>
