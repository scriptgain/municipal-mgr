@props(['flush' => false])
{{-- Sleek flush-in-card table. Consumers write plain <thead>/<tbody>/<th>/<td>;
     cell styling is applied here. Fixed layout keeps long text from ever
     scrolling the table sideways — it truncates and gets a hover tooltip
     (see public/js/municipal.js). --}}
<div class="{{ $flush ? '' : 'rounded-xl ring-1 ring-slate-200 bg-white shadow-sm overflow-hidden' }}">
    <table {{ $attributes->merge(['class' =>
        'mm-table w-full text-sm text-left tabular '
        . '[&_thead]:bg-slate-50 [&_thead_th]:px-4 [&_thead_th]:py-3 [&_thead_th]:font-semibold [&_thead_th]:text-xs [&_thead_th]:uppercase [&_thead_th]:tracking-wide [&_thead_th]:text-slate-500 '
        . '[&_tbody_tr]:border-t [&_tbody_tr]:border-slate-100 [&_tbody_tr:hover]:bg-brand-50/40 '
        . '[&_tbody_td]:px-4 [&_tbody_td]:py-3 [&_tbody_td]:text-slate-700 [&_tbody_td]:align-middle']) }}>
        {{ $slot }}
    </table>
</div>
