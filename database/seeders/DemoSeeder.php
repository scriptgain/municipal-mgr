<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Bid;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Event;
use App\Models\JobPosting;
use App\Models\Meeting;
use App\Models\NewsPost;
use App\Models\Notice;
use App\Models\Official;
use App\Models\Page;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A plausible demonstration village: Cottonwood Springs, Arizona.
 *
 * Everything here is fictional but shaped like the real thing — a small
 * council-manager town with five departments, a monthly council meeting cycle,
 * a document library of ordinances and budgets, and a handful of open service
 * requests in different states. It exists so an evaluator sees a working
 * municipal site rather than an empty CMS.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding the Cottonwood Springs demonstration site…');

        // Site identity
        foreach ([
            'site_name' => 'Town Of Cottonwood Springs',
            'site_kind' => 'Town',
            'site_state' => 'Arizona',
            'site_motto' => 'Incorporated 1912 — Where The High Desert Meets The Creek',
            'site_hero_heading' => 'Welcome To Cottonwood Springs',
            'site_hero_subheading' => 'A community of 6,400 in the Verde Valley. Find services, meeting agendas, and the people who run your town government.',
            'contact_address' => '118 South Main Street',
            'contact_city_state_zip' => 'Cottonwood Springs, AZ 86326',
            'contact_phone' => '(928) 555-0142',
            'contact_fax' => '(928) 555-0148',
            'contact_email' => 'townhall@cottonwoodsprings.example.gov',
            'contact_hours' => 'Monday to Thursday, 7:00 AM to 5:30 PM',
            'contact_after_hours' => 'For water and sewer emergencies after hours, call (928) 555-0199.',
            'accessibility_contact' => 'Marisol Vega, Town Clerk — (928) 555-0143',
            'footer_note' => 'An equal opportunity provider and employer.',
            'timezone' => 'America/Phoenix',
        ] as $key => $value) {
            Setting::put($key, $value);
        }

        // Departments
        $departments = [
            ['Administration And Town Clerk', 'building', 'Records, elections, licensing, and the front counter at Town Hall.', '(928) 555-0142', 'clerk@cottonwoodsprings.example.gov'],
            ['Public Works', 'bolt', 'Streets, water, sewer, and the crews who keep them running.', '(928) 555-0155', 'publicworks@cottonwoodsprings.example.gov'],
            ['Police Department', 'shield', 'Community policing, records, and non-emergency reporting.', '(928) 555-0170', 'police@cottonwoodsprings.example.gov'],
            ['Parks And Recreation', 'star', 'Parks, trails, the community pool, and youth programs.', '(928) 555-0161', 'parks@cottonwoodsprings.example.gov'],
            ['Community Development', 'scale', 'Planning, zoning, building permits, and code enforcement.', '(928) 555-0158', 'planning@cottonwoodsprings.example.gov'],
            ['Finance And Utilities Billing', 'database', 'Budget, purchasing, and your monthly water bill.', '(928) 555-0150', 'finance@cottonwoodsprings.example.gov'],
        ];

        $deptModels = [];
        foreach ($departments as $i => [$name, $icon, $summary, $phone, $email]) {
            $deptModels[$name] = Department::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($name)], [
                'name' => $name,
                'icon' => $icon,
                'summary' => $summary,
                'description' => "<p>{$summary}</p><p>Staff are available at Town Hall during regular business hours, and by appointment outside them. Most routine business — licence renewals, permit intake, and utility questions — can be started by phone or email.</p>",
                'phone' => $phone,
                'email' => $email,
                'address' => '118 South Main Street, Cottonwood Springs, AZ 86326',
                'hours' => 'Monday to Thursday, 7:00 AM to 5:30 PM',
                'sort_order' => $i,
            ]);
        }

        // Staff
        $staff = [
            ['Marisol Vega', 'Town Clerk', 'Administration And Town Clerk', 'mvega@cottonwoodsprings.example.gov', '(928) 555-0143'],
            ['Daniel Okafor', 'Town Manager', 'Administration And Town Clerk', 'dokafor@cottonwoodsprings.example.gov', '(928) 555-0141'],
            ['Ruth Hollings', 'Deputy Clerk', 'Administration And Town Clerk', 'rhollings@cottonwoodsprings.example.gov', '(928) 555-0144'],
            ['Wes Turnbull', 'Public Works Director', 'Public Works', 'wturnbull@cottonwoodsprings.example.gov', '(928) 555-0156'],
            ['Alma Reyes', 'Water Operations Supervisor', 'Public Works', 'areyes@cottonwoodsprings.example.gov', '(928) 555-0157'],
            ['Chief Karen Boyd', 'Chief Of Police', 'Police Department', 'kboyd@cottonwoodsprings.example.gov', '(928) 555-0171'],
            ['Sgt. Miguel Ontiveros', 'Community Resource Sergeant', 'Police Department', 'montiveros@cottonwoodsprings.example.gov', '(928) 555-0172'],
            ['Priya Raman', 'Parks And Recreation Director', 'Parks And Recreation', 'praman@cottonwoodsprings.example.gov', '(928) 555-0162'],
            ['Grant Whitley', 'Community Development Director', 'Community Development', 'gwhitley@cottonwoodsprings.example.gov', '(928) 555-0159'],
            ['Ellen Sato', 'Building Official', 'Community Development', 'esato@cottonwoodsprings.example.gov', '(928) 555-0160'],
            ['Terrence Blake', 'Finance Director', 'Finance And Utilities Billing', 'tblake@cottonwoodsprings.example.gov', '(928) 555-0151'],
            ['Nina Cortez', 'Utilities Billing Specialist', 'Finance And Utilities Billing', 'ncortez@cottonwoodsprings.example.gov', '(928) 555-0152'],
        ];

        foreach ($staff as $i => [$name, $title, $dept, $email, $phone]) {
            $member = StaffMember::firstOrCreate(['name' => $name, 'job_title' => $title], [
                'department_id' => $deptModels[$dept]->id,
                'email' => $email,
                'phone' => $phone,
                'office' => 'Town Hall',
                'sort_order' => $i,
            ]);

            // Directors head their departments.
            if (str_contains($title, 'Director') || str_contains($title, 'Chief Of Police') || $title === 'Town Clerk') {
                $deptModels[$dept]->update(['head_staff_id' => $member->id]);
            }
        }

        // Elected officials
        $officials = [
            ['Rosa Delgado', 'Mayor', 'At Large', '2024-01-08', '2028-01-03'],
            ['Ben Whitaker', 'Vice Mayor', 'Ward 1', '2024-01-08', '2028-01-03'],
            ['Councilmember Joyce Ahn', 'Council Member', 'Ward 2', '2022-01-10', '2026-01-05'],
            ['Councilmember Ray Sandoval', 'Council Member', 'Ward 3', '2024-01-08', '2028-01-03'],
            ['Councilmember Dot Feeney', 'Council Member', 'At Large', '2022-01-10', '2026-01-05'],
            ['Councilmember Ivan Petrov', 'Council Member', 'At Large', '2024-01-08', '2028-01-03'],
        ];
        foreach ($officials as $i => [$name, $office, $district, $start, $end]) {
            Official::firstOrCreate(['name' => $name, 'office' => $office], [
                'district' => $district,
                'email' => strtolower(str_replace([' ', '.'], ['', ''], explode(' ', $name)[count(explode(' ', $name)) - 1])) . '@cottonwoodsprings.example.gov',
                'phone' => '(928) 555-01' . (80 + $i),
                'bio' => "<p>{$name} was elected to serve {$district} and sits on the Council's budget and public safety subcommittees.</p>",
                'term_start' => $start,
                'term_end' => $end,
                'sort_order' => $i,
            ]);
        }

        // Pages
        $pages = [
            ['About Cottonwood Springs', 'The history, geography, and government of Cottonwood Springs.', [
                ['type' => 'rich_text', 'heading' => 'Our History', 'body' => '<p>Cottonwood Springs was settled around the perennial springs at the north end of the valley and incorporated in 1912, the same year Arizona achieved statehood. Ranching and a short-lived copper smelter shaped the first fifty years; today the economy runs on agriculture, tourism, and light manufacturing along the Highway 89 corridor.</p>'],
                ['type' => 'rich_text', 'heading' => 'Form Of Government', 'body' => '<p>The Town operates under a council-manager form of government. Six council members and a mayor are elected at large or by ward to four-year staggered terms. The Council sets policy and adopts the budget; a professional Town Manager, appointed by the Council, runs day-to-day operations.</p>'],
                ['type' => 'rich_text', 'heading' => 'By The Numbers', 'body' => '<ul><li>Population: 6,412 (2020 Census)</li><li>Incorporated: March 1912</li><li>Elevation: 3,320 feet</li><li>Area: 8.4 square miles</li><li>Full-time employees: 61</li></ul>'],
            ]],
            ['Pay Your Water Bill', 'Ways to pay, billing cycles, and what to do if you are behind.', [
                ['type' => 'rich_text', 'heading' => 'How To Pay', 'body' => '<p>Utility bills are issued on the first business day of each month and are due on the twentieth. You can pay online, by phone, by mail, in person at the Town Hall front counter, or in the after-hours drop box on the Main Street side of the building.</p>'],
                ['type' => 'rich_text', 'heading' => 'Trouble Paying?', 'body' => '<p>Contact Utilities Billing before your due date. The Town offers payment arrangements and can connect you with county assistance programs. Service is not disconnected while an arrangement is in good standing.</p>'],
            ]],
            ['Building Permits', 'When you need a permit, how to apply, and how long it takes.', [
                ['type' => 'rich_text', 'heading' => 'When A Permit Is Required', 'body' => '<p>A building permit is required for new construction, additions, structural alterations, re-roofing, electrical and plumbing work, water heaters, block walls over three feet, and any detached structure larger than 200 square feet.</p>'],
                ['type' => 'rich_text', 'heading' => 'Review Timelines', 'body' => '<p>Residential plan review takes about ten business days; commercial review takes about twenty. Over-the-counter permits for water heaters, re-roofs, and like-for-like replacements are usually issued the same day.</p>'],
                ['type' => 'callout', 'heading' => 'Before You Start Work', 'body' => '<p>Call Arizona 811 at least two full working days before any digging. It is free, and it is the law.</p>'],
            ]],
            ['Trash And Recycling', 'Collection days, what goes in which bin, and bulk pickup.', [
                ['type' => 'rich_text', 'heading' => 'Collection Schedule', 'body' => '<p>Residential trash is collected weekly; recycling is collected every other week. Collection north of Creek Road is on Tuesdays and south of Creek Road on Wednesdays. Place carts at the curb by 6:00 AM with wheels toward the house.</p>'],
                ['type' => 'rich_text', 'heading' => 'Holiday Delays', 'body' => '<p>Collection slides one day when a holiday falls on or before your service day. Holiday schedules are posted to the Town calendar each December.</p>'],
            ]],
            ['Public Records Requests', 'How to request Town records under Arizona public records law.', [
                ['type' => 'rich_text', 'heading' => 'Making A Request', 'body' => '<p>Most Town records are public. Submit a request in writing to the Town Clerk describing the records you want as specifically as you can — a date range and a subject are usually enough. There is no fee to inspect records; copies are charged at cost.</p>'],
                ['type' => 'rich_text', 'heading' => 'Response Times', 'body' => '<p>The Clerk acknowledges requests within two business days and produces records promptly. Large or complex requests are produced on a rolling basis, and you will be told the schedule up front.</p>'],
            ]],
        ];

        foreach ($pages as $i => [$title, $summary, $sections]) {
            Page::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($title)], [
                'title' => $title,
                'summary' => $summary,
                'sections' => $sections,
                'status' => 'published',
                'published_at' => now()->subDays(60 - $i),
                'sort_order' => $i,
            ]);
        }

        // Document categories are seeded by municipal:bootstrap; attach documents.
        $agendas = DocumentCategory::where('slug', 'agendas-and-minutes')->first();
        $ordinances = DocumentCategory::where('slug', 'ordinances')->first();
        $budgets = DocumentCategory::where('slug', 'budgets-and-finance')->first();

        $documents = [
            ['Town Council Regular Meeting Agenda — ' . now()->addDays(9)->format('F j, Y'), $agendas, 'AGENDA-' . now()->addDays(9)->format('Ymd'), now()->addDays(9)],
            ['Town Council Regular Meeting Minutes — ' . now()->subDays(21)->format('F j, Y'), $agendas, 'MIN-' . now()->subDays(21)->format('Ymd'), now()->subDays(21)],
            ['Town Council Regular Meeting Minutes — ' . now()->subDays(51)->format('F j, Y'), $agendas, 'MIN-' . now()->subDays(51)->format('Ymd'), now()->subDays(51)],
            ['Ordinance 2026-04: Amending The Water Rate Schedule', $ordinances, 'Ordinance 2026-04', now()->subDays(38)],
            ['Ordinance 2026-03: Short Term Rental Registration', $ordinances, 'Ordinance 2026-03', now()->subDays(96)],
            ['Ordinance 2025-11: Dark Sky Lighting Standards', $ordinances, 'Ordinance 2025-11', now()->subMonths(9)],
            ['Adopted Budget, Fiscal Year 2026-2027', $budgets, 'FY2627-BUDGET', now()->subDays(44)],
            ['Annual Comprehensive Financial Report, FY 2025-2026', $budgets, 'ACFR-FY2526', now()->subMonths(4)],
        ];

        $docModels = [];
        foreach ($documents as [$title, $category, $reference, $date]) {
            $docModels[] = Document::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($title)], [
                'document_category_id' => $category?->id,
                'title' => $title,
                'reference' => $reference,
                'description' => 'Demonstration record. In a live install this entry links to the signed PDF as filed with the Town Clerk.',
                'file_path' => 'documents/demo-placeholder.pdf',
                'file_name' => \Illuminate\Support\Str::slug($title) . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => random_int(180000, 2400000),
                'document_date' => $date,
                'download_count' => random_int(3, 260),
            ]);
        }

        // Meetings on a real cadence: second Wednesday of the month, 6:00 PM.
        $council = 'Town Council';
        foreach ([-2, -1, 0, 1, 2] as $offset) {
            $date = now()->addMonths($offset)->startOfMonth()->next(\Carbon\Carbon::WEDNESDAY)->addWeek()->setTime(18, 0);
            $past = $date->isPast();

            Meeting::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($council . ' regular meeting ' . $date->format('Y-m-d'))], [
                'body' => $council,
                'title' => 'Regular Meeting',
                'meets_at' => $date,
                'location' => 'Council Chambers, Town Hall',
                'address' => '118 South Main Street, Cottonwood Springs, AZ 86326',
                'summary' => '<p>Regular business meeting of the Town Council. The meeting is open to the public, and time is reserved for public comment on items both on and off the agenda.</p>',
                'agenda_document_id' => $docModels[0]->id ?? null,
                'minutes_document_id' => $past ? ($docModels[1]->id ?? null) : null,
                'status' => $past ? 'held' : 'scheduled',
            ]);
        }

        Meeting::firstOrCreate(['slug' => 'planning-and-zoning-commission-regular-meeting-' . now()->addDays(16)->format('Y-m-d')], [
            'body' => 'Planning And Zoning Commission',
            'title' => 'Regular Meeting',
            'meets_at' => now()->addDays(16)->setTime(17, 30),
            'location' => 'Council Chambers, Town Hall',
            'summary' => '<p>Includes a public hearing on a requested zoning change at the Highway 89 frontage.</p>',
        ]);

        // News
        $news = [
            ['Water Main Replacement Begins On Third Street', 'Announcement', 'Public Works', 'Crews begin replacing 1,900 feet of aging cast iron water main on Third Street between Oak and Willow starting Monday.', 3, true],
            ['Council Adopts Fiscal Year 2026-2027 Budget', 'Press Release', 'Finance And Utilities Billing', 'The Town Council unanimously adopted a $14.2 million budget that holds the property tax rate flat and funds two additional public works positions.', 12, false],
            ['Summer Pool Passes Now On Sale', 'News', 'Parks And Recreation', 'Season passes for the Community Pool are available at the Parks office and online. Resident rates apply with proof of address.', 19, false],
            ['Police Department Launches Online Reporting', 'Announcement', 'Police Department', 'Residents can now file reports for lost property, vandalism without a suspect, and vehicle burglaries online instead of waiting for an officer.', 27, false],
            ['Household Hazardous Waste Collection Day', 'News', 'Public Works', 'Bring paint, pesticides, batteries, and used oil to the Public Works yard on the last Saturday of the month. Free for residents.', 34, false],
            ['Main Street Repaving Complete Ahead Of Schedule', 'Press Release', 'Public Works', 'The Main Street repaving project wrapped up eleven days early and roughly $40,000 under budget.', 48, false],
        ];
        foreach ($news as [$title, $category, $dept, $excerpt, $daysAgo, $featured]) {
            NewsPost::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($title)], [
                'department_id' => $deptModels[$dept]->id ?? null,
                'title' => $title,
                'category' => $category,
                'excerpt' => $excerpt,
                'body' => "<p>{$excerpt}</p><p>Residents with questions can contact the department directly during business hours. Updates will be posted to this page and to the Town's social media accounts as the work progresses.</p>",
                'is_featured' => $featured,
                'status' => 'published',
                'published_at' => now()->subDays($daysAgo),
            ]);
        }

        // Notices
        $notices = [
            ['Notice Of Public Hearing: Zoning Change At 1400 Highway 89', 'Public Hearing', 5, 25],
            ['Notice Of Intent To Adopt Ordinance 2026-05', 'Ordinance', 2, 30],
            ['Notice Of Election: Town Council Seats, Wards 2 And At Large', 'Election', 9, 60],
            ['Notice Of Availability: Draft Annual Water Quality Report', 'General', 14, 45],
        ];
        foreach ($notices as [$title, $type, $postedAgo, $expiresIn]) {
            Notice::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($title)], [
                'title' => $title,
                'notice_type' => $type,
                'body' => "<p>NOTICE IS HEREBY GIVEN that the Town of Cottonwood Springs will consider the matter described above. Written comment may be submitted to the Town Clerk at 118 South Main Street or by email prior to the hearing. All interested persons are invited to attend and be heard.</p><p>Persons with a disability may request a reasonable accommodation by contacting the Town Clerk at least 48 hours before the meeting.</p>",
                'posted_at' => now()->subDays($postedAgo),
                'expires_at' => now()->addDays($expiresIn),
                'status' => 'published',
            ]);
        }

        // Events
        $events = [
            ['Movies In The Park: Family Night', 'Community', 6, 'Veterans Memorial Park'],
            ['Town Hall Closed — Independence Day', 'Closure', 11, 'Town Hall'],
            ['Farmers Market Opening Day', 'Community', 17, 'Main Street Plaza'],
            ['Household Hazardous Waste Collection', 'Community', 24, 'Public Works Yard'],
            ['Youth Soccer Registration Deadline', 'Recreation', 30, 'Community Center'],
            ['Neighborhood Watch Kickoff Meeting', 'Community', 38, 'Council Chambers'],
        ];
        foreach ($events as [$title, $category, $daysOut, $location]) {
            Event::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($title)], [
                'title' => $title,
                'category' => $category,
                'description' => "<p>Join us for {$title}. All are welcome; no registration required unless noted.</p>",
                'starts_at' => now()->addDays($daysOut)->setTime(18, 0),
                'ends_at' => now()->addDays($daysOut)->setTime(20, 0),
                'location' => $location,
            ]);
        }

        // Jobs
        $jobs = [
            ['Water Distribution Operator I', 'Public Works', 'Full Time', '$44,600 to $56,300 Annually', 21],
            ['Police Officer (Lateral And Entry Level)', 'Police Department', 'Full Time', '$58,900 to $74,100 Annually', null],
            ['Lifeguard (Seasonal)', 'Parks And Recreation', 'Seasonal', '$17.25 Hourly', 14],
            ['Permit Technician', 'Community Development', 'Full Time', '$41,800 to $52,400 Annually', 28],
        ];
        foreach ($jobs as [$title, $dept, $type, $salary, $closesIn]) {
            JobPosting::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($title)], [
                'department_id' => $deptModels[$dept]->id ?? null,
                'title' => $title,
                'employment_type' => $type,
                'salary_range' => $salary,
                'description' => "<p>The Town of Cottonwood Springs is accepting applications for {$title}. This position reports to the department director and works a four-day, ten-hour schedule.</p>",
                'requirements' => '<ul><li>High school diploma or equivalent</li><li>Valid Arizona driver licence with an acceptable record</li><li>Ability to pass a background check and pre-employment screening</li></ul>',
                'apply_email' => 'hr@cottonwoodsprings.example.gov',
                'posted_on' => now()->subDays(10),
                'closes_at' => $closesIn ? now()->addDays($closesIn) : null,
                'is_open_until_filled' => $closesIn === null,
                'status' => 'published',
            ]);
        }

        // Bids
        $bids = [
            ['Third Street Water Main Replacement', 'Bid', 'BID 2026-11', 18, 'open'],
            ['Request For Proposals: Municipal Audit Services', 'RFP', 'RFP 2026-09', 26, 'open'],
            ['Community Pool Deck Resurfacing', 'Bid', 'BID 2026-06', -30, 'awarded'],
        ];
        foreach ($bids as [$title, $type, $reference, $closesIn, $status]) {
            Bid::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($title)], [
                'department_id' => $deptModels['Public Works']->id ?? null,
                'title' => $title,
                'reference' => $reference,
                'bid_type' => $type,
                'description' => "<p>The Town of Cottonwood Springs invites sealed proposals for {$title}. The complete package, including specifications and required forms, is available from the Town Clerk.</p><p>Proposals received after the closing time will not be accepted or considered.</p>",
                'contact_name' => 'Marisol Vega, Town Clerk',
                'contact_email' => 'clerk@cottonwoodsprings.example.gov',
                'opens_at' => now()->subDays(12),
                'closes_at' => now()->addDays($closesIn),
                'status' => $status,
                'awarded_to' => $status === 'awarded' ? 'Verde Valley Concrete And Coatings LLC' : null,
            ]);
        }

        // Service requests in a realistic spread of states
        $requests = [
            ['Pothole Or Road Damage', 'Large pothole in the northbound lane of Willow near the school crossing. It has taken out at least two tires this week.', '900 block of Willow Street', 'resolved', 'Public Works', 12],
            ['Streetlight Outage', 'Streetlight has been out for about two weeks. The corner is very dark at pickup time.', 'Corner of Third and Oak', 'in_progress', 'Public Works', 6],
            ['Water Or Sewer Issue', 'Water in the gutter running steadily from under the sidewalk, clear, no smell. Possible service line leak.', '215 South Main Street', 'assigned', 'Public Works', 3],
            ['Missed Trash Pickup', 'Cart was out by 6am Tuesday and was not collected. Still at the curb.', '1442 Creek Road', 'resolved', 'Public Works', 9],
            ['Graffiti Or Vandalism', 'Graffiti on the north wall of the restroom building at the park.', 'Veterans Memorial Park', 'new', 'Parks And Recreation', 1],
            ['Park Or Facility Maintenance', 'Two boards on the playground bridge are cracked and one is lifting at the end.', 'Willow Creek Playground', 'in_review', 'Parks And Recreation', 2],
            ['Code Enforcement Concern', 'Vehicle has been parked on the lawn next door for over a month and is not running.', '600 block of Juniper', 'assigned', 'Community Development', 5],
            ['Stray Or Nuisance Animal', 'Loose dog, medium sized, has been around the cul-de-sac for two days. Friendly but no collar.', 'Sycamore Court', 'closed', 'Police Department', 15],
        ];

        foreach ($requests as [$category, $description, $location, $status, $dept, $daysAgo]) {
            $existing = ServiceRequest::where('description', $description)->first();
            if ($existing) {
                continue;
            }

            $request = ServiceRequest::create([
                'category' => $category,
                'description' => $description,
                'location_text' => $location,
                'reporter_name' => 'Demonstration Resident',
                'reporter_email' => 'resident@example.com',
                'status' => $status,
                'department_id' => $deptModels[$dept]->id ?? null,
                'acknowledged_at' => $status === 'new' ? null : now()->subDays($daysAgo - 1),
                'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? now()->subDays(max(1, $daysAgo - 4)) : null,
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays(max(0, $daysAgo - 2)),
            ]);

            $request->updatesLog()->create([
                'status' => 'new',
                'note' => 'Request received.',
                'is_public' => true,
                'created_at' => now()->subDays($daysAgo),
            ]);

            if ($status !== 'new') {
                $request->updatesLog()->create([
                    'status' => $status,
                    'note' => 'Routed to ' . $dept . ' for assessment.',
                    'is_public' => true,
                    'created_at' => now()->subDays(max(1, $daysAgo - 1)),
                ]);
            }

            if (in_array($status, ['resolved', 'closed'], true)) {
                $request->updatesLog()->create([
                    'status' => $status,
                    'note' => 'Work completed and the request has been closed. Thank you for reporting it.',
                    'is_public' => true,
                    'created_at' => now()->subDays(max(1, $daysAgo - 4)),
                ]);
            }
        }

        // A staged (inactive) advisory so staff can see how the banner behaves.
        Alert::firstOrCreate(['title' => 'Stage Two Water Restrictions In Effect'], [
            'message' => 'Outdoor watering is limited to before 8:00 AM and after 6:00 PM, on odd or even days matching your address.',
            'level' => 'advisory',
            'link_label' => 'Read The Full Advisory',
            'is_active' => false,
        ]);

        // Demo staff accounts. Passwords are obvious on purpose — this seeder is
        // for demonstration installs and must never be run on a live town site.
        $adminEmail = 'clerk@cottonwoodsprings.example.gov';
        if (! User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'Marisol Vega',
                'email' => $adminEmail,
                'password' => Hash::make('password'),
                'role' => 'admin',
                'job_title' => 'Town Clerk',
                'password_changed_at' => now(),
            ]);
        }

        $editorEmail = 'parks-editor@cottonwoodsprings.example.gov';
        if (! User::where('email', $editorEmail)->exists()) {
            User::create([
                'name' => 'Priya Raman',
                'email' => $editorEmail,
                'password' => Hash::make('password'),
                'role' => 'department_editor',
                'department_id' => $deptModels['Parks And Recreation']->id,
                'job_title' => 'Parks And Recreation Director',
                'password_changed_at' => now(),
            ]);
        }

        $this->command->info('Demo site seeded. Sign in as ' . $adminEmail . ' / password (CHANGE THIS).');
    }
}
