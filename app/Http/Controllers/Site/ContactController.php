<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FormDefinition;

class ContactController extends Controller
{
    public function index()
    {
        return view('site.contact', [
            'departments' => Department::published()->ordered()->get(),
            'contactForm' => FormDefinition::published()->where('slug', 'contact-us')->first(),
        ]);
    }

    /**
     * Accessibility statement. A published statement plus a named contact is
     * both an ADA/Section 508 expectation and the fastest way for someone who
     * hits a barrier to tell the municipality about it.
     */
    public function accessibility()
    {
        return view('site.accessibility');
    }
}
