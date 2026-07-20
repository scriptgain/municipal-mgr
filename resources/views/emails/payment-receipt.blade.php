<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt {{ $payment->reference }}</title>
</head>
{{-- Table-based, inline styles: this has to survive Outlook, Gmail and
     whatever the resident's mail client happens to be. --}}
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">

                @if ($isTestPayment)
                    <tr>
                        <td style="background:#f59e0b;color:#451a03;padding:14px 24px;font-size:14px;font-weight:bold;">
                            TEST PAYMENT: no money was taken. This is not proof of payment.
                        </td>
                    </tr>
                @endif

                <tr>
                    <td style="background:#0f4c81;padding:24px;">
                        <p style="margin:0;color:#ffffff;font-size:18px;font-weight:bold;">{{ $siteName }}</p>
                        <p style="margin:4px 0 0;color:#bfdbfe;font-size:12px;text-transform:uppercase;letter-spacing:1.5px;">Payment Receipt</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px 24px 8px;">
                        <p style="margin:0 0 8px;font-size:16px;">
                            @if ($payment->payer_name)Hello {{ $payment->payer_name }},@else Hello,@endif
                        </p>
                        <p style="margin:0;font-size:15px;line-height:1.6;color:#475569;">
                            Thank you. We have received your payment of
                            <strong style="color:#1e293b;">{{ $payment->amountFormatted() }}</strong>.
                            Please keep this receipt as proof of payment.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 24px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:8px;">
                            <tr>
                                <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#64748b;">Payment Reference</td>
                                <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;text-align:right;font-weight:bold;font-family:'Courier New',monospace;">{{ $payment->reference }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#64748b;">Date</td>
                                <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;text-align:right;">{{ ($payment->paid_at ?? $payment->created_at)->format(config('municipal.date_format')) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#64748b;">What This Was For</td>
                                <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;text-align:right;">{{ $bill?->type?->label ?? $payment->type?->label ?? 'Payment' }}</td>
                            </tr>
                            @if ($bill)
                                <tr>
                                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#64748b;">Bill Reference</td>
                                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;text-align:right;font-family:'Courier New',monospace;">{{ $bill->reference }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#64748b;">Payment Method</td>
                                <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;text-align:right;">{{ $payment->instrumentLabel() }}</td>
                            </tr>
                            <tr>
                                <td style="padding:14px 16px;font-size:16px;font-weight:bold;color:#1e293b;">Amount Paid</td>
                                <td style="padding:14px 16px;font-size:18px;font-weight:bold;text-align:right;color:#0f4c81;">{{ $payment->amountFormatted() }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @if ($bill && $bill->balanceCents() > 0)
                    <tr>
                        <td style="padding:0 24px 16px;">
                            <p style="margin:0;padding:12px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:14px;color:#78350f;">
                                <strong>{{ $bill->balanceFormatted() }}</strong> is still outstanding on this bill.
                            </p>
                        </td>
                    </tr>
                @endif

                <tr>
                    <td style="padding:0 24px 28px;" align="center">
                        <a href="{{ $receiptUrl }}"
                           style="display:inline-block;background:#0f4c81;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:15px;font-weight:bold;">
                            View Your Receipt Online
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                            Questions about this payment?
                            @if ($supportPhone) Call {{ $supportPhone }}. @endif
                            @if ($supportEmail) Email <a href="mailto:{{ $supportEmail }}" style="color:#0f4c81;">{{ $supportEmail }}</a>. @endif
                        </p>
                        <p style="margin:10px 0 0;font-size:12px;color:#94a3b8;">
                            This receipt was sent by {{ $siteName }}. We never ask for card details by email.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
