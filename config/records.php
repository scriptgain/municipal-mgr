<?php

/*
|--------------------------------------------------------------------------
| Jail And Arrest Records
|--------------------------------------------------------------------------
| Build-time defaults for the arrest blotter and inmate roster. Everything an
| operator can change at runtime lives in the DB Setting store (see
| App\Services\RecordsSettings): these are the shapes and vocabularies the
| module is built from, not operator knobs.
|
| This module publishes information about people who have been accused of
| nothing more than what a police officer wrote down at 2am. The defaults below
| are deliberately conservative: the module ships off, mugshots ship off, and
| the public blotter forgets a record after RETENTION_DAYS.
*/

return [
    // A subject below this age is never publishable. This is not an operator
    // knob and is not exposed in Settings: juvenile arrest records are sealed
    // by statute in essentially every jurisdiction, and a platform that lets
    // an admin uncheck that is a platform that will eventually publish a child.
    'minimum_publish_age' => 18,

    // Conservative default retention for the PUBLIC blotter, in days. The
    // record itself is kept for staff; only its public visibility expires.
    'default_retention_days' => 60,

    // An arrest is not a conviction. Every record carries one of these, and the
    // public views render it next to the charges rather than hiding it.
    'dispositions' => [
        'pending' => ['label' => 'Pending', 'color' => 'warn', 'public' => 'Case Pending. No Finding Has Been Made.'],
        'dismissed' => ['label' => 'Dismissed', 'color' => 'success', 'public' => 'Charges Dismissed.'],
        'acquitted' => ['label' => 'Acquitted', 'color' => 'success', 'public' => 'Found Not Guilty.'],
        'convicted' => ['label' => 'Convicted', 'color' => 'danger', 'public' => 'Convicted.'],
        'diverted' => ['label' => 'Diverted', 'color' => 'info', 'public' => 'Referred To A Diversion Program.'],
    ],

    'custody_statuses' => [
        'in_custody' => ['label' => 'In Custody', 'color' => 'danger', 'roster' => true],
        'released_bond' => ['label' => 'Released On Bond', 'color' => 'neutral', 'roster' => false],
        'released_own_recognizance' => ['label' => 'Released On Own Recognizance', 'color' => 'neutral', 'roster' => false],
        'released_time_served' => ['label' => 'Released, Time Served', 'color' => 'neutral', 'roster' => false],
        'transferred' => ['label' => 'Transferred To Another Agency', 'color' => 'info', 'roster' => false],
        'released_other' => ['label' => 'Released', 'color' => 'neutral', 'roster' => false],
    ],

    'charge_severities' => [
        'felony' => ['label' => 'Felony', 'color' => 'danger'],
        'misdemeanor' => ['label' => 'Misdemeanor', 'color' => 'warn'],
        'infraction' => ['label' => 'Infraction Or Citation', 'color' => 'neutral'],
        'other' => ['label' => 'Other', 'color' => 'neutral'],
    ],

    // Date windows offered on the public blotter filter.
    'blotter_ranges' => [
        '7' => 'Past 7 Days',
        '14' => 'Past 14 Days',
        '30' => 'Past 30 Days',
        'all' => 'Entire Published Period',
    ],
];
