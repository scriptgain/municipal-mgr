<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\BillType;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Clearly fictional demo data for Cottonwood Springs.
 *
 * Every name, address and email here is invented. Emails all sit on
 * example.com, which is reserved by RFC 2606 and can never reach a real
 * person, so a stray receipt cannot land in a stranger's inbox.
 *
 * Idempotent: re-running it will not duplicate anything.
 */
class PaymentsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $types = $this->billTypes();

        // Already seeded: leave it alone rather than piling up duplicates.
        if (Bill::where('reference', 'like', 'BILL-%')->count() > 0) {
            $this->command?->info('Payments demo data already present; skipping.');

            return;
        }

        $utility = $types['utility'];
        $permit = $types['permit'];
        $fine = $types['fine'];

        /*
        | A realistic spread: bills that are simply outstanding, one overdue,
        | one part paid at the counter, one paid in full online, and one void.
        | That is what a clerk's screen actually looks like, and it exercises
        | every status the UI has to render.
        */
        $rows = [
            [
                'type' => $utility,
                'account' => 'WS-104882',
                'name' => 'Marisol Alvarez',
                'email' => 'marisol.alvarez@example.com',
                'phone' => '(928) 555-0142',
                'zip' => '86326',
                'amount' => 8450,
                'description' => 'Water And Sewer, March 2026',
                'due' => 12,
                'paid' => 0,
            ],
            [
                'type' => $utility,
                'account' => 'WS-104913',
                'name' => 'Dennis Whitlock',
                'email' => 'd.whitlock@example.com',
                'phone' => '(928) 555-0177',
                'zip' => '86326',
                'amount' => 11235,
                'description' => 'Water And Sewer, February 2026',
                'due' => -21,          // Overdue.
                'paid' => 0,
            ],
            [
                'type' => $utility,
                'account' => 'WS-105044',
                'name' => 'Priya Raghunathan',
                'email' => 'praghunathan@example.com',
                'phone' => '(928) 555-0119',
                'zip' => '86325',
                'amount' => 9600,
                'description' => 'Water, Sewer And Refuse, March 2026',
                'due' => 9,
                'paid' => 4000,        // Part paid at the counter.
                'paid_method' => 'check',
            ],
            [
                'type' => $permit,
                'account' => 'BP-2026-0188',
                'name' => 'Cottonwood Springs Hardware',
                'email' => 'office@example.com',
                'phone' => '(928) 555-0163',
                'zip' => '86326',
                'amount' => 22500,
                'description' => 'Sign Permit, 114 Main Street',
                'due' => 20,
                'paid' => 22500,       // Paid in full online.
                'paid_method' => 'card',
            ],
            [
                'type' => $fine,
                'account' => 'CIT-88214',
                'name' => 'Roy Petersen',
                'email' => 'rpetersen@example.com',
                'phone' => '(928) 555-0198',
                'zip' => '86326',
                'amount' => 7500,
                'description' => 'Parking Citation, Civic Plaza Lot',
                'due' => 5,
                'paid' => 0,
            ],
            [
                'type' => $fine,
                'account' => 'CIT-88190',
                'name' => 'Hannah Broussard',
                'email' => 'hbroussard@example.com',
                'phone' => '(928) 555-0155',
                'zip' => '86325',
                'amount' => 5000,
                'description' => 'Parking Citation, Issued In Error',
                'due' => 3,
                'paid' => 0,
                'void' => true,
            ],
        ];

        foreach ($rows as $row) {
            $bill = Bill::create([
                'bill_type_id' => $row['type']->id,
                'account_number' => $row['account'],
                'payer_name' => $row['name'],
                'payer_email' => $row['email'],
                'payer_phone' => $row['phone'],
                'lookup_postal_code' => $row['zip'],
                'amount_cents' => $row['amount'],
                'description' => $row['description'],
                'issued_on' => Carbon::now()->subDays(30 - $row['due'])->toDateString(),
                'due_date' => Carbon::now()->addDays($row['due'])->toDateString(),
                'status' => 'unpaid',
            ]);

            if (! empty($row['void'])) {
                $bill->forceFill([
                    'status' => 'void',
                    'notes' => 'Demo record. Voided: citation issued in error.',
                ])->save();

                continue;
            }

            if ($row['paid'] > 0) {
                $isCard = ($row['paid_method'] ?? 'cash') === 'card';

                Payment::create([
                    'bill_id' => $bill->id,
                    'bill_type_id' => $bill->bill_type_id,
                    'amount_cents' => $row['paid'],
                    'status' => 'succeeded',
                    'method' => $row['paid_method'] ?? 'cash',
                    // Demo card payments are marked test, so nothing here can
                    // ever be mistaken for money that actually arrived.
                    'livemode' => ! $isCard,
                    'card_brand' => $isCard ? 'visa' : null,
                    'card_last4' => $isCard ? '4242' : null,
                    'payer_name' => $row['name'],
                    'payer_email' => $row['email'],
                    'paid_at' => Carbon::now()->subDays(rand(1, 6)),
                    'notes' => $isCard ? null : 'Demo record. Taken at the counter.',
                ]);

                $bill->recalculate();
            }
        }

        $this->command?->info('Seeded ' . count($rows) . ' demo bills for Cottonwood Springs.');
    }

    /**
     * Ensure the standard bill types exist, keyed for lookup.
     *
     * @return array<string, BillType>
     */
    private function billTypes(): array
    {
        $types = [];

        foreach (config('payments.default_bill_types', []) as $i => $definition) {
            $types[$definition['key']] = BillType::firstOrCreate(
                ['key' => $definition['key']],
                $definition + ['sort_order' => $i * 10, 'is_active' => true]
            );
        }

        return $types;
    }
}
