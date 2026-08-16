<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('tenant.invoices.invoice_number', ['id' => $invoice->id]) }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white p-8 text-zinc-900">
    <h1 class="text-2xl font-bold">{{ tenant('name') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('tenant.invoices.invoice_number', ['id' => $invoice->id]) }}</p>
    <p class="mt-4 text-sm">
        <strong>{{ __('tenant.invoices.client_label') }}:</strong>
        {{ $invoice->client->name }}
    </p>
    @if ($invoice->description)
        <p class="mt-1 text-sm text-zinc-500">{{ $invoice->description }}</p>
    @endif

    <table class="mt-4 w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-300 text-left">
                <th class="py-1">{{ __('tenant.invoices.product_label') }}</th>
                <th class="py-1 text-right">{{ __('tenant.invoices.quantity_label') }}</th>
                <th class="py-1 text-right">{{ __('tenant.common.price') }}</th>
                <th class="py-1 text-right">{{ __('tenant.invoices.subtotal_label') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr class="border-b border-zinc-200">
                    <td class="py-1">{{ $item->product_name }}</td>
                    <td class="py-1 text-right">{{ $item->quantity }}</td>
                    <td class="py-1 text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="py-1 text-right">${{ number_format($item->subtotal(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 flex flex-col items-end gap-1 text-sm">
        <p>{{ __('tenant.invoices.subtotal_label') }}: ${{ number_format($invoice->subtotal(), 2) }}</p>
        @foreach ($invoice->taxes as $tax)
            <p>{{ $tax->name }} ({{ number_format($tax->rate, 2) }}%): ${{ number_format($tax->amount($invoice->subtotal()), 2) }}</p>
        @endforeach
        <p class="font-bold">{{ __('tenant.invoices.total_label') }}: ${{ number_format($invoice->totalAmount(), 2) }}</p>
        <p>{{ __('tenant.invoices.paid_label') }}: ${{ number_format($invoice->paidAmount(), 2) }}</p>
        <p class="font-bold">{{ __('tenant.invoices.balance_label') }}: ${{ number_format($invoice->totalAmount() - $invoice->paidAmount(), 2) }}</p>
    </div>

    @if ($invoice->notes)
        <div class="mt-8 border-t border-zinc-300 pt-3 text-xs text-zinc-500">
            <p class="font-semibold">{{ __('tenant.invoices.terms_label') }}</p>
            <p class="mt-1 whitespace-pre-line">{{ $invoice->notes }}</p>
        </div>
    @endif

    <script>
        window.onload = () => window.print();
    </script>
</body>
</html>
