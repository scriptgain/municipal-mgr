<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt {{ $payment->reference }}</title>
    {{-- Self-contained: this file is downloaded and opened off the resident's
         own machine, so it cannot rely on the site's stylesheets being
         reachable. Plain CSS, no CDN, no build step. --}}
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; color: #1e293b; margin: 0; padding: 40px 24px; background: #fff; line-height: 1.6; }
        .sheet { max-width: 640px; margin: 0 auto; }
        .head { border-bottom: 3px solid #0f4c81; padding-bottom: 16px; margin-bottom: 28px; }
        .org { font-size: 20px; font-weight: bold; color: #0f4c81; margin: 0; }
        .doc { font-size: 12px; text-transform: uppercase; letter-spacing: .12em; color: #64748b; margin: 6px 0 0; }
        .test { background: #fef3c7; border: 2px solid #d97706; color: #78350f; padding: 12px 16px; margin-bottom: 24px; font-family: Arial, sans-serif; font-weight: bold; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { text-align: left; padding: 10px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 14px; }
        th { color: #64748b; font-weight: normal; width: 45%; }
        td { text-align: right; font-weight: bold; }
        .total { border-top: 2px solid #0f4c81; border-bottom: none; padding-top: 16px; font-size: 20px; color: #0f4c81; }
        .total th { font-size: 16px; color: #1e293b; font-weight: bold; padding-top: 16px; border-top: 2px solid #0f4c81; }
        .ref { font-family: 'Courier New', monospace; }
        .foot { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
<div class="sheet">
    @if ($isTestMode)
        <p class="test">
            TEST PAYMENT: NO MONEY WAS TAKEN.
            This receipt was produced while the payment system was in test mode and is not proof of payment.
        </p>
    @endif

    <div class="head">
        <p class="org">{{ $siteName }}</p>
        <p class="doc">Official Payment Receipt</p>
    </div>

    <table>
        <tbody>
            <tr>
                <th scope="row">Payment Reference</th>
                <td class="ref">{{ $payment->reference }}</td>
            </tr>
            <tr>
                <th scope="row">Date Paid</th>
                <td>{{ ($payment->paid_at ?? $payment->created_at)->format(config('municipal.date_format') . ', ' . config('municipal.time_format')) }}</td>
            </tr>
            <tr>
                <th scope="row">What This Was For</th>
                <td>
                    {{ $bill?->type?->label ?? $payment->type?->label ?? 'Payment' }}
                    @if ($bill?->description)
                        <br><span style="font-weight: normal;">{{ $bill->description }}</span>
                    @elseif ($payment->notes)
                        <br><span style="font-weight: normal;">{{ $payment->notes }}</span>
                    @endif
                </td>
            </tr>
            @if ($bill)
                <tr>
                    <th scope="row">Bill Reference</th>
                    <td class="ref">{{ $bill->reference }}</td>
                </tr>
                @if ($bill->account_number)
                    <tr>
                        <th scope="row">Account Number</th>
                        <td class="ref">{{ $bill->account_number }}</td>
                    </tr>
                @endif
            @endif
            @if ($payment->payer_name)
                <tr>
                    <th scope="row">Paid By</th>
                    <td>{{ $payment->payer_name }}</td>
                </tr>
            @endif
            <tr>
                <th scope="row">Payment Method</th>
                <td>{{ $payment->instrumentLabel() }}</td>
            </tr>
            <tr>
                <th scope="row">Status</th>
                <td>{{ $payment->statusLabel() }}</td>
            </tr>
            @if ($payment->refunded_cents > 0)
                <tr>
                    <th scope="row">Refunded</th>
                    <td>- {{ $payment->refundedFormatted() }}</td>
                </tr>
            @endif
            <tr class="total">
                <th scope="row">Amount Paid</th>
                <td class="total">{{ $payment->amountFormatted() }}</td>
            </tr>
        </tbody>
    </table>

    @if ($bill && $bill->balanceCents() > 0)
        <p style="font-size: 14px;">
            <strong>{{ $bill->balanceFormatted() }}</strong> remains outstanding on bill {{ $bill->reference }}.
        </p>
    @endif

    <div class="foot">
        <p>
            Retain this receipt as your proof of payment.
            @if ($supportPhone) Questions: {{ $supportPhone }}. @endif
            @if ($supportEmail) {{ $supportEmail }} @endif
        </p>
        <p>Issued by {{ $siteName }} on {{ now()->format(config('municipal.date_format')) }}.</p>
    </div>
</div>
</body>
</html>
