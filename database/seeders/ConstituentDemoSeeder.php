<?php

namespace Database\Seeders;

use App\Models\Constituent;
use App\Models\ConstituentInteraction;
use App\Models\Department;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Residents of Cottonwood Springs, plus the contact history a town hall really
 * accumulates: calls, counter visits, letters.
 *
 * Kept out of DemoSeeder so it can be re-run on its own after the constituent
 * migrations land on an install that already has demo content. Idempotent:
 * everything keys off the email address.
 *
 * The stock demo filed all eight service requests under one placeholder address
 * (resident@example.com), which collapses into a single constituent and makes
 * the feature look like it does nothing. This seeder spreads those requests
 * across real-looking residents first, then links them.
 */
class ConstituentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding Cottonwood Springs residents…');

        $people = [
            ['Marisol Vega', 'mvega@example.com', '(928) 555-0142', '118 South Main Street', ['Business Owner']],
            ['Dale Ferris', 'dferris@example.com', '(928) 555-0173', '900 Willow Street', []],
            ['Anita Broward', 'abroward@example.com', '(928) 555-0118', '215 South Main Street', ['Council District 2']],
            ['Ray Okafor', 'rokafor@example.com', '(928) 555-0126', '1442 Creek Road', []],
            ['Judy Halloran', 'jhalloran@example.com', '(928) 555-0155', '77 Juniper Lane', ['Snowbird']],
            ['Tomas Reyna', 'treyna@example.com', '(928) 555-0164', '640 Juniper Street', []],
            ['Priya Nandan', 'pnandan@example.com', '(928) 555-0109', '12 Sycamore Court', ['Business Owner']],
            ['Wendell Cross', 'wcross@example.com', '(928) 555-0191', '308 Third Street', []],
            ['Grace Tillman', 'gtillman@example.com', '(928) 555-0137', '55 Oak Avenue', ['Council District 1']],
            ['Hector Salas', 'hsalas@example.com', '(928) 555-0148', '204 Creekside Drive', []],
        ];

        $created = [];
        foreach ($people as $index => [$name, $email, $phone, $address, $tags]) {
            $key = Constituent::emailKey($email);

            $created[] = Constituent::firstOrCreate(['email_key' => $key], [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'phone_key' => Constituent::phoneKey($phone),
                'address_line1' => $address,
                'city' => 'Cottonwood Springs',
                'state' => 'AZ',
                'postal_code' => '86326',
                'tags' => $tags,
                'source' => 'seed',
                'do_not_contact' => $name === 'Judy Halloran', // one flagged record, so the flag is visible
                'last_interaction_at' => now()->subDays($index + 1),
            ]);
        }

        $this->spreadDemoServiceRequests($created);
        $this->logContacts($created);
    }

    /**
     * Move the stock demo requests off the single placeholder reporter and onto
     * distinct residents, then link them to the constituent record.
     */
    private function spreadDemoServiceRequests(array $people): void
    {
        $requests = ServiceRequest::whereIn('reporter_email', ['resident@example.com'])
            ->orderBy('id')->get();

        foreach ($requests as $i => $request) {
            $person = $people[$i % count($people)];

            $request->forceFill([
                'reporter_name' => $person->name,
                'reporter_email' => $person->email,
                'reporter_phone' => $person->phone,
                'constituent_id' => $person->id,
            ])->save();

            $person->touchInteraction($request->created_at);
        }

        // Anything already carrying a matching email but no link (for instance a
        // request filed before the migration ran) gets attached too.
        foreach ($people as $person) {
            ServiceRequest::whereNull('constituent_id')
                ->where('reporter_email', $person->email)
                ->update(['constituent_id' => $person->id]);
        }
    }

    /** Staff-logged contact: the half of the record that never comes in online. */
    private function logContacts(array $people): void
    {
        $staff = User::where('is_active', true)->orderBy('id')->get();
        $departments = Department::orderBy('id')->get()->keyBy('name');

        $log = [
            ['mvega@example.com', 'phone_call', 'inbound', 'Business license renewal question', 'Called to ask whether the renewal can be filed by mail. Walked her through the form and confirmed the fee has not changed this year.', 2, 'Community Development'],
            ['dferris@example.com', 'counter_visit', 'inbound', 'Followed up on the Willow Street pothole', 'Came to the counter to ask about timing. Explained the patch crew schedule and that the permanent repair waits on the paving contract.', 4, 'Public Works'],
            ['dferris@example.com', 'phone_call', 'outbound', 'Repair completed', 'Called to let him know the pothole was patched Tuesday morning.', 1, 'Public Works'],
            ['abroward@example.com', 'email', 'inbound', 'Water bill adjustment request', 'Emailed asking about a spike on the March bill. Forwarded to Utility Billing for a meter re-read.', 6, 'Finance'],
            ['rokafor@example.com', 'phone_call', 'inbound', 'Missed trash pickup', 'Reported the cart was skipped again. Confirmed the route change and asked the hauler to return same day.', 8, 'Public Works'],
            ['jhalloran@example.com', 'letter', 'outbound', 'Seasonal water shutoff confirmation', 'Mailed written confirmation of the seasonal shutoff dates she requested at the counter.', 20, 'Public Works'],
            ['treyna@example.com', 'counter_visit', 'inbound', 'Code enforcement complaint', 'Walked in about the vehicle parked on the lawn next door. Took the details and opened a service request.', 5, 'Community Development'],
            ['pnandan@example.com', 'meeting', 'inbound', 'Sidewalk frontage for the Main Street shop', 'Met with Planning about the sidewalk seating permit ahead of the summer season.', 11, 'Community Development'],
            ['wcross@example.com', 'phone_call', 'inbound', 'Question about the council agenda', 'Asked how to get an item on the agenda. Sent him the clerk request form.', 3, null],
            ['gtillman@example.com', 'email', 'outbound', 'Meeting packet sent', 'Emailed the June council packet at her request.', 7, null],
            ['hsalas@example.com', 'phone_call', 'inbound', 'Streetlight outage on Creekside', 'Reported two lights out. Logged and passed to the utility.', 9, 'Public Works'],
            ['mvega@example.com', 'counter_visit', 'inbound', 'Dropped off renewal paperwork', 'Delivered the completed renewal and paid by check. Receipt issued.', 1, 'Finance'],
        ];

        foreach ($log as $i => [$email, $type, $direction, $subject, $note, $daysAgo, $department]) {
            $person = collect($people)->firstWhere('email', $email);
            if (! $person) {
                continue;
            }

            $exists = ConstituentInteraction::where('constituent_id', $person->id)
                ->where('subject', $subject)->exists();
            if ($exists) {
                continue;
            }

            $occurred = now()->subDays($daysAgo)->setTime(9 + ($i % 7), ($i * 7) % 60);

            ConstituentInteraction::create([
                'constituent_id' => $person->id,
                'user_id' => $staff[$i % max(1, $staff->count())]->id ?? null,
                'department_id' => $department ? ($departments[$department]->id ?? null) : null,
                'type' => $type,
                'direction' => $direction,
                'subject' => $subject,
                'note' => $note,
                'occurred_at' => $occurred,
                'created_at' => $occurred,
                'updated_at' => $occurred,
            ]);

            $person->touchInteraction($occurred);
        }
    }
}
