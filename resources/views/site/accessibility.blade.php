<x-layouts.public title="Accessibility Statement">
    <x-site.page-hero title="Accessibility Statement"
                      subtitle="Everyone has a right to use their government's website. Here is our commitment and how to tell us when we fall short."
                      :crumbs="[['label' => 'Accessibility']]" />

    <x-site.section :divider="false">
        <div class="prose-civic max-w-none">
            <h2>Our Commitment</h2>
            <p>
                The {{ $siteFormalName }} is committed to ensuring that this website is accessible to
                people with disabilities. We aim to conform to the Web Content Accessibility Guidelines
                (WCAG) 2.1 Level AA, which is the standard referenced by Section 508 of the
                Rehabilitation Act and the Americans with Disabilities Act.
            </p>

            <h2>What We Do</h2>
            <ul>
                <li>Every page uses semantic headings and landmarks so screen readers can navigate it.</li>
                <li>The entire site is operable with a keyboard alone, with a visible focus indicator on every control.</li>
                <li>Text meets contrast requirements against its background.</li>
                <li>Images that convey meaning carry descriptive alternative text.</li>
                <li>Forms use real labels, not placeholder text, and describe their errors in plain language.</li>
                <li>The site reflows to a single column on small screens without loss of content or function.</li>
                <li>Animation is reduced automatically when your device requests it.</li>
            </ul>

            <h2>Documents</h2>
            <p>
                Some documents on this site are PDFs produced by third parties, and older records may
                have been scanned before accessible formats were standard. If you need a document in an
                accessible format, contact us and we will provide it in an alternative format at no charge.
            </p>

            <h2>Tell Us About A Problem</h2>
            <p>
                If you encounter a barrier on this site, we want to hear about it. Please tell us the page
                address, what you were trying to do, and what went wrong. We will respond and work with you
                on an alternative way to get what you need in the meantime.
            </p>

            @if ($site['accessibility_contact'])
                <p><strong>Accessibility Contact:</strong> {{ $site['accessibility_contact'] }}</p>
            @endif

            <ul>
                @if ($site['contact_phone'])
                    <li><strong>Phone:</strong> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}">{{ $site['contact_phone'] }}</a></li>
                @endif
                @if ($site['contact_email'])
                    <li><strong>Email:</strong> <a href="mailto:{{ $site['contact_email'] }}">{{ $site['contact_email'] }}</a></li>
                @endif
                @if ($site['contact_address'])
                    <li><strong>In Person:</strong> {{ $site['contact_address'] }} {{ $site['contact_city_state_zip'] }}</li>
                @endif
            </ul>

            <h2>Ongoing Work</h2>
            <p>
                Accessibility is not a one-time project. We review this site regularly and fix issues as we
                find them or as they are reported to us.
            </p>
        </div>
    </x-site.section>
</x-layouts.public>
