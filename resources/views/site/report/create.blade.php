<x-layouts.public title="Report An Issue">
    <x-site.page-hero title="Report An Issue"
                      subtitle="Tell us about a pothole, a streetlight, a water problem, or anything else that needs attention. No account required."
                      :crumbs="[['label' => 'Report An Issue']]" />

    <x-site.section :divider="false">
        <div class="grid gap-10 lg:grid-cols-3">
            <div class="min-w-0 lg:col-span-2">
                <form method="POST" action="{{ route('site.report.store') }}" enctype="multipart/form-data" class="space-y-8">
                    @csrf

                    <fieldset class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <legend class="px-2 font-display text-xl font-semibold text-slate-900">What Is The Problem?</legend>

                        <div class="mt-4 space-y-5">
                            <div>
                                <label for="category" class="block text-sm font-medium text-slate-700">Type Of Issue <span class="text-rose-600">*</span></label>
                                <select id="category" name="category" required
                                        class="mt-1.5 block w-full rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                    @endforeach
                                </select>
                                @error('category')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-slate-700">Describe The Issue <span class="text-rose-600">*</span></label>
                                <textarea id="description" name="description" rows="6" required aria-describedby="description-help"
                                          class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old('description') }}</textarea>
                                <p id="description-help" class="mt-1.5 text-sm text-slate-500">The more specific you are, the faster a crew can find it.</p>
                                @error('description')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="location_text" class="block text-sm font-medium text-slate-700">Where Is It?</label>
                                <input id="location_text" name="location_text" type="text" value="{{ old('location_text') }}"
                                       placeholder="Nearest address, intersection, or landmark"
                                       class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                @error('location_text')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="photo" class="block text-sm font-medium text-slate-700">Add A Photograph</label>
                                <input id="photo" name="photo" type="file" accept="image/*"
                                       class="mt-1.5 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                                @error('photo')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <legend class="px-2 font-display text-xl font-semibold text-slate-900">How Can We Reach You?</legend>
                        <p class="mt-2 text-sm text-slate-500">
                            Optional, but leaving an email is the only way we can update you or ask a follow-up question.
                        </p>

                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="reporter_name" class="block text-sm font-medium text-slate-700">Your Name</label>
                                <input id="reporter_name" name="reporter_name" type="text" value="{{ old('reporter_name') }}"
                                       class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            </div>
                            <div>
                                <label for="reporter_email" class="block text-sm font-medium text-slate-700">Email Address</label>
                                <input id="reporter_email" name="reporter_email" type="email" value="{{ old('reporter_email') }}"
                                       class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                @error('reporter_email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="reporter_phone" class="block text-sm font-medium text-slate-700">Phone Number</label>
                                <input id="reporter_phone" name="reporter_phone" type="tel" value="{{ old('reporter_phone') }}"
                                       class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            </div>
                        </div>

                        <div class="mt-6">
                            <x-toggle name="is_anonymous" :checked="old('is_anonymous', false)"
                                      label="Submit Anonymously"
                                      description="We will not store your name, email, or phone number. You will still get a tracking link." />
                        </div>
                    </fieldset>

                    <x-captcha context="report" />

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-6 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-brand-800">
                            <x-icon name="bolt" class="w-5 h-5" /> Submit This Report
                        </button>
                        <a href="{{ route('site.track') }}" class="text-sm font-semibold text-brand-700 hover:underline">Already Reported Something? Track It</a>
                    </div>
                </form>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-rose-50 p-6 ring-1 ring-rose-200">
                    <h2 class="font-semibold text-rose-900">In An Emergency, Call 911</h2>
                    <p class="mt-2 text-sm leading-relaxed text-rose-800">
                        This form is checked during business hours only. For a fire, a crime in progress,
                        a medical emergency, or a downed power line, call 911 immediately.
                    </p>
                    @if ($site['contact_after_hours'])
                        <p class="mt-3 text-sm text-rose-800">
                            <span class="font-semibold">After Hours:</span> {{ $site['contact_after_hours'] }}
                        </p>
                    @endif
                </div>

                <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">What Happens Next</h2>
                    <span class="seal-rule mt-3 mb-4"></span>
                    <ol class="space-y-3 text-sm text-slate-600">
                        <li class="flex gap-3"><span class="font-bold text-brand-700">1.</span> You get a tracking reference immediately.</li>
                        <li class="flex gap-3"><span class="font-bold text-brand-700">2.</span> Staff review and route it to the right department.</li>
                        <li class="flex gap-3"><span class="font-bold text-brand-700">3.</span> Updates are posted to your tracking page as work progresses.</li>
                    </ol>
                </div>
            </aside>
        </div>
    </x-site.section>
</x-layouts.public>
