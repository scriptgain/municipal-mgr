<?php

/*
|--------------------------------------------------------------------------
| Payments (Pay Your Bill)
|--------------------------------------------------------------------------
| Build-time defaults for the payments module. Everything an operator can
| change at runtime (Stripe credentials, test/live mode, the enable flag)
| lives in the DB Setting store instead, per the fleet's DB-driven config
| convention. Nothing secret belongs in this file or in .env.
|
| Money is handled in MINOR UNITS (cents) as integers everywhere: floats are
| not an acceptable representation of a resident's water bill.
*/

return [
    // Stripe API surface. Pinned so an upstream default change cannot silently
    // alter request/response shapes on a live municipal site.
    'stripe' => [
        'base_uri' => 'https://api.stripe.com',
        'version' => '2024-06-20',
        'timeout' => 20,
    ],

    'currency' => 'usd',
    'currency_symbol' => '$',

    // Guard rails for the "pay without a bill reference" path, where the
    // resident types the amount. A bill-backed payment ignores these entirely:
    // its amount comes from the bill record.
    'open_payment' => [
        'min_cents' => 100,        // $1.00
        'max_cents' => 2500000,    // $25,000.00
    ],

    /*
    | Bill lifecycle. `paid` and `void` are terminal for the resident-facing
    | flow: neither is payable. `partially_paid` is reachable because counter
    | staff take part payments on utility accounts all the time.
    */
    'bill_statuses' => [
        'unpaid' => ['label' => 'Unpaid', 'color' => 'warn'],
        'partially_paid' => ['label' => 'Partially Paid', 'color' => 'info'],
        'paid' => ['label' => 'Paid', 'color' => 'success'],
        'void' => ['label' => 'Void', 'color' => 'neutral'],
    ],

    'payment_statuses' => [
        'pending' => ['label' => 'Pending', 'color' => 'warn'],
        'succeeded' => ['label' => 'Succeeded', 'color' => 'success'],
        'failed' => ['label' => 'Failed', 'color' => 'danger'],
        'refunded' => ['label' => 'Refunded', 'color' => 'neutral'],
        'partially_refunded' => ['label' => 'Partially Refunded', 'color' => 'info'],
        'canceled' => ['label' => 'Canceled', 'color' => 'neutral'],
    ],

    /*
    | How the money arrived. `card` is the online path; the rest are what staff
    | record when someone pays at the counter or mails a check, which a town
    | clerk needs far more often than any software vendor expects.
    */
    'methods' => [
        'card' => 'Card (Online)',
        'cash' => 'Cash (Counter)',
        'check' => 'Check',
        'money_order' => 'Money Order',
        'bank_transfer' => 'Bank Transfer',
        'other' => 'Other',
    ],

    // The second factor a resident supplies alongside a bill reference, so
    // bills are not enumerable by guessing sequential reference numbers.
    'lookup_factors' => [
        'surname' => 'Last Name On The Account',
        'postal_code' => 'Billing ZIP Code',
    ],

    // Bill types seeded on first enable. Staff can edit, add and retire these.
    'default_bill_types' => [
        [
            'key' => 'utility',
            'label' => 'Utility Bill',
            'description' => 'Water, sewer and refuse service billed to your address.',
            'icon' => 'bolt',
            'requires_lookup' => true,
            'allows_open_payment' => false,
        ],
        [
            'key' => 'permit',
            'label' => 'Permit Fee',
            'description' => 'Building, sign, fence and special event permit fees.',
            'icon' => 'clipboard',
            'requires_lookup' => false,
            'allows_open_payment' => true,
        ],
        [
            'key' => 'fine',
            'label' => 'Citation Or Fine',
            'description' => 'Parking citations, code enforcement penalties and court fines.',
            'icon' => 'scale',
            'requires_lookup' => true,
            'allows_open_payment' => false,
        ],
        [
            'key' => 'other',
            'label' => 'Other Payment',
            'description' => 'Facility rentals, copies of records and other town charges.',
            'icon' => 'file-text',
            'requires_lookup' => false,
            'allows_open_payment' => true,
        ],
    ],

    // Bill lookup attempts allowed per minute, per IP. Low on purpose: this is
    // the enumeration surface.
    'lookup_rate_limit' => 8,
];
