<?php

/*
|--------------------------------------------------------------------------
| MunicipalMGR
|--------------------------------------------------------------------------
| Product-level knobs for the municipal platform. Anything an operator should
| be able to change at runtime lives in the DB Setting store instead (see
| AppServiceProvider::boot) — these are the build-time defaults it overrides.
*/

return [
    // Section width for BOTH the admin panel and the public site. Never
    // hardcode a max-w-* in a view; read config('municipal.max_width').
    'max_width' => env('MUNICIPAL_MAX_WIDTH', 'max-w-7xl'),

    // Public-site identity defaults (overridden by Settings -> Site).
    'site' => [
        'name' => env('MUNICIPAL_SITE_NAME', 'Village of Example'),
        'kind' => env('MUNICIPAL_SITE_KIND', 'Village'), // City | Town | Village | Township | County
        'state' => env('MUNICIPAL_SITE_STATE', 'Arizona'),
        'motto' => env('MUNICIPAL_SITE_MOTTO', 'Serving Our Residents Since 1912'),
    ],

    'date_format' => 'M j, Y',
    'time_format' => 'g:i A',
    'rows_per_page' => 25,

    // Report-An-Issue intake categories. Editable per install via Settings.
    'request_categories' => [
        'Pothole Or Road Damage',
        'Streetlight Outage',
        'Water Or Sewer Issue',
        'Missed Trash Pickup',
        'Graffiti Or Vandalism',
        'Stray Or Nuisance Animal',
        'Code Enforcement Concern',
        'Park Or Facility Maintenance',
        'Snow Or Storm Debris',
        'Other',
    ],

    // Lifecycle of a service request. The public status tracker maps these to
    // plain-language labels; the admin table maps them to status dots.
    'request_statuses' => [
        'new' => ['label' => 'Received', 'color' => 'info'],
        'in_review' => ['label' => 'In Review', 'color' => 'info'],
        'assigned' => ['label' => 'Assigned To Crew', 'color' => 'warn'],
        'in_progress' => ['label' => 'Work In Progress', 'color' => 'warn'],
        'resolved' => ['label' => 'Resolved', 'color' => 'success'],
        'closed' => ['label' => 'Closed', 'color' => 'neutral'],
        'duplicate' => ['label' => 'Duplicate', 'color' => 'neutral'],
    ],

    // Public bodies that hold posted meetings (agendas/minutes/video).
    'meeting_bodies' => [
        'Town Council',
        'Planning And Zoning Commission',
        'Board Of Adjustment',
        'Parks And Recreation Board',
        'Public Safety Committee',
        'Budget And Finance Committee',
    ],

    // Roles. A department editor may only touch content owned by their
    // department; an editor may touch all content but no settings/users.
    'roles' => [
        'admin' => 'Administrator',
        'editor' => 'Site Editor',
        'department_editor' => 'Department Editor',
        'viewer' => 'Read Only',
    ],

    // Page-builder section blocks available in the CMS.
    'sections' => [
        'rich_text' => 'Rich Text',
        'hero' => 'Hero Banner',
        'quick_links' => 'Quick Links Grid',
        'cards' => 'Card Row',
        'accordion' => 'Accordion (FAQ)',
        'contact_panel' => 'Contact Panel',
        'documents' => 'Document List',
        'staff_grid' => 'Staff Grid',
        'callout' => 'Callout Banner',
        'embed' => 'Embed (Map Or Video)',
    ],

    // Read-only public demo. When true the panel auto-signs-in a demo user and
    // blocks every write so anyone can click around a fully seeded instance.
    'demo' => (bool) env('DEMO_MODE', false),
];
