<?php

namespace Database\Seeders;

use App\Models\ArrestCharge;
use App\Models\ArrestRecord;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Demonstration data for the Jail And Arrest Records module.
 *
 * Everything here is invented. The names are deliberately not plausible as
 * real people in a real town: this seeds a product demo, and a demo blotter
 * populated with realistic-looking names is a demo blotter that will be
 * screenshotted and mistaken for the real thing.
 *
 * The seeder does NOT enable the module. A seeded install is still an install
 * that has to consciously turn arrest records on.
 */
class RecordsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (ArrestRecord::count() > 0) {
            $this->command?->info('Arrest records already present. Skipping.');

            return;
        }

        $this->command?->info('Seeding demonstration arrest records for Cottonwood Springs…');

        // Configuration only. records_module_enabled is deliberately untouched.
        foreach ([
            'records_agency_name' => 'Cottonwood Springs Police Department',
            'records_takedown_contact' => 'Records Division, Cottonwood Springs Police Department, (928) 555-0140, records@cottonwoodsprings.example.gov',
            'records_blotter_intro' => 'Bookings reported by the Cottonwood Springs Police Department and the Yavapai County Sheriff substation. Dispositions are updated as the court notifies the department.',
        ] as $key => $value) {
            Setting::put($key, $value);
        }

        $agency = 'Cottonwood Springs Police Department';
        $county = 'Yavapai County Sheriff Substation';

        $rows = [
            [
                'first_name' => 'Wendell', 'last_name' => 'Quartermain', 'age' => 44,
                'booked_at' => now()->subDays(2)->setTime(23, 14), 'agency' => $agency,
                'case_number' => 'CS-2026-004417', 'booking_number' => 'B-11204',
                'custody_status' => 'in_custody', 'disposition' => 'pending',
                'bond_amount' => 2500.00, 'bond_note' => 'Secured appearance bond',
                'is_published' => true,
                'charges' => [
                    ['Driving Under The Influence', 'ARS 28-1381', 'misdemeanor', 1],
                    ['Failure To Provide Proof Of Insurance', 'ARS 28-4135', 'infraction', 1],
                ],
            ],
            [
                'first_name' => 'Odessa', 'last_name' => 'Fenwick-Boyle', 'age' => 31,
                'booked_at' => now()->subDays(5)->setTime(14, 2), 'agency' => $agency,
                'case_number' => 'CS-2026-004402', 'booking_number' => 'B-11197',
                'custody_status' => 'released_bond', 'disposition' => 'dismissed',
                'disposition_note' => 'Charges dismissed by the town prosecutor on February 2.',
                'released_at' => now()->subDays(5)->setTime(20, 40),
                'bond_amount' => 750.00,
                'is_published' => true,
                'charges' => [
                    ['Criminal Damage To Property', 'ARS 13-1602', 'misdemeanor', 1],
                ],
            ],
            [
                'first_name' => 'Barnaby', 'middle_name' => 'T', 'last_name' => 'Illingsworth', 'age' => 58,
                'booked_at' => now()->subDays(9)->setTime(8, 51), 'agency' => $county,
                'case_number' => 'YC-2026-018866', 'booking_number' => 'B-11181',
                'custody_status' => 'in_custody', 'disposition' => 'pending',
                'bond_amount' => 15000.00, 'bond_note' => 'Held pending arraignment',
                'is_published' => true,
                'charges' => [
                    ['Aggravated Assault', 'ARS 13-1204', 'felony', 1],
                    ['Disorderly Conduct', 'ARS 13-2904', 'misdemeanor', 2],
                ],
            ],
            [
                'first_name' => 'Prudence', 'last_name' => 'Vandersnoot', 'age' => 27,
                'booked_at' => now()->subDays(14)->setTime(2, 27), 'agency' => $agency,
                'case_number' => 'CS-2026-004371', 'booking_number' => 'B-11162',
                'custody_status' => 'released_own_recognizance', 'disposition' => 'diverted',
                'disposition_note' => 'Referred to the county deferred prosecution program.',
                'released_at' => now()->subDays(13)->setTime(9, 15),
                'is_published' => true,
                'charges' => [
                    ['Possession Of Drug Paraphernalia', 'ARS 13-3415', 'misdemeanor', 1],
                ],
            ],
            [
                'first_name' => 'Cornelius', 'last_name' => 'Hatchmarsh', 'age' => 39,
                'booked_at' => now()->subDays(21)->setTime(19, 45), 'agency' => $agency,
                'case_number' => 'CS-2026-004330', 'booking_number' => 'B-11140',
                'custody_status' => 'released_time_served', 'disposition' => 'convicted',
                'disposition_note' => 'Pleaded guilty to one count. Sentenced to time served and a fine.',
                'released_at' => now()->subDays(19)->setTime(11, 0),
                'is_published' => true,
                'charges' => [
                    ['Shoplifting', 'ARS 13-1805', 'misdemeanor', 1],
                ],
            ],
            [
                'first_name' => 'Lavinia', 'last_name' => 'Pockleton', 'age' => 52,
                'booked_at' => now()->subDays(28)->setTime(6, 12), 'agency' => $county,
                'case_number' => 'YC-2026-018790', 'booking_number' => 'B-11118',
                'custody_status' => 'transferred', 'disposition' => 'acquitted',
                'disposition_note' => 'Found not guilty at trial on March 4.',
                'is_published' => true,
                'charges' => [
                    ['Theft Of Means Of Transportation', 'ARS 13-1814', 'felony', 1],
                ],
            ],
            // In custody but NOT published: shows that publication is a separate,
            // deliberate step rather than a side effect of creating a record.
            [
                'first_name' => 'Ambrose', 'last_name' => 'Widdlecombe', 'age' => 35,
                'booked_at' => now()->subDay()->setTime(4, 33), 'agency' => $agency,
                'case_number' => 'CS-2026-004420', 'booking_number' => 'B-11209',
                'custody_status' => 'in_custody', 'disposition' => 'pending',
                'bond_amount' => 5000.00,
                'internal_notes' => 'Awaiting confirmation of the charge list from the arresting officer before this is published.',
                'is_published' => false,
                'charges' => [
                    ['Trespassing In The Third Degree', 'ARS 13-1502', 'misdemeanor', 1],
                ],
            ],
            // Juvenile: demonstrates the block. Requested as published; the model
            // refuses and stores it unpublished.
            [
                'first_name' => 'Tobias', 'last_name' => 'Grimsditch', 'age' => 16,
                'booked_at' => now()->subDays(4)->setTime(17, 20), 'agency' => $agency,
                'case_number' => 'CS-2026-004409', 'booking_number' => 'B-11200',
                'custody_status' => 'released_other', 'disposition' => 'diverted',
                'released_at' => now()->subDays(4)->setTime(21, 5),
                'internal_notes' => 'Juvenile. Released to a guardian. This record is not publishable.',
                'is_published' => true,
                'charges' => [
                    ['Curfew Violation', 'Town Code 9-4-2', 'infraction', 1],
                ],
            ],
            // Booked long enough ago that its retention window has already run
            // out, so the nightly expiry job has something real to act on.
            [
                'first_name' => 'Millicent', 'last_name' => 'Crumbworth', 'age' => 47,
                'booked_at' => now()->subDays(80)->setTime(13, 5), 'agency' => $agency,
                'case_number' => 'CS-2026-004102', 'booking_number' => 'B-10988',
                'custody_status' => 'released_bond', 'disposition' => 'dismissed',
                'disposition_note' => 'Dismissed without prejudice.',
                'released_at' => now()->subDays(80)->setTime(18, 30),
                'is_published' => true,
                'charges' => [
                    ['Disorderly Conduct', 'ARS 13-2904', 'misdemeanor', 1],
                ],
            ],
        ];

        foreach ($rows as $row) {
            $charges = $row['charges'];
            unset($row['charges']);

            $agencyName = $row['agency'];
            unset($row['agency']);

            $record = ArrestRecord::create($row + ['arresting_agency' => $agencyName]);

            foreach ($charges as $i => [$description, $statute, $severity, $counts]) {
                ArrestCharge::create([
                    'arrest_record_id' => $record->id,
                    'description' => $description,
                    'statute' => $statute,
                    'severity' => $severity,
                    'counts' => $counts,
                    'sort_order' => $i,
                ]);
            }
        }

        $this->command?->info('Seeded ' . count($rows) . ' demonstration arrest records. The module remains DISABLED.');
    }
}
