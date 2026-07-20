<x-layouts.public :title="$form->name" :description="$form->description">
    <x-site.page-hero :title="$form->name" :subtitle="$form->description"
                      :eyebrow="$form->department?->name"
                      :crumbs="[['label' => $form->name]]" />

    <x-site.section :divider="false">
        <div class="mx-auto max-w-2xl">
            @if (session('form_success'))
                <div role="status" class="mb-8 rounded-2xl bg-emerald-50 p-6 ring-1 ring-emerald-200">
                    <div class="flex items-start gap-3">
                        <x-icon name="check-circle" class="mt-0.5 w-6 h-6 shrink-0 text-emerald-600" />
                        <p class="font-medium text-emerald-900">{{ session('form_success') }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('site.forms.submit', $form) }}" class="rounded-2xl bg-white p-8 ring-1 ring-slate-200 shadow-sm">
                @csrf

                <div class="hidden" aria-hidden="true">
                    <label for="website">Leave This Field Empty</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div class="space-y-6">
                    @foreach ($fields as $field)
                        @php($inputId = 'field-' . $field['key'])
                        @php($inputName = 'fields[' . $field['key'] . ']')
                        <div>
                            <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700">
                                {{ $field['label'] }}
                                @if ($field['required'])<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(Required)</span>@endif
                            </label>

                            @if ($field['type'] === 'textarea')
                                <textarea id="{{ $inputId }}" name="{{ $inputName }}" rows="5" @required($field['required'])
                                          @if ($field['help']) aria-describedby="{{ $inputId }}-help" @endif
                                          class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">{{ old($inputName) }}</textarea>
                            @elseif ($field['type'] === 'select')
                                <select id="{{ $inputId }}" name="{{ $inputName }}" @required($field['required'])
                                        class="mt-1.5 block w-full rounded-lg border-0 py-2.5 pl-3 pr-9 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                    <option value="">Please Choose</option>
                                    @foreach ($field['options'] as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field['type'] === 'radio')
                                <div class="mt-2 space-y-2" role="radiogroup" aria-labelledby="{{ $inputId }}">
                                    @foreach ($field['options'] as $option)
                                        <label class="flex items-center gap-2.5 text-sm text-slate-700">
                                            <input type="radio" name="{{ $inputName }}" value="{{ $option }}" @required($field['required'])
                                                   class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600">
                                            {{ $option }}
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($field['type'] === 'checkbox')
                                <div class="mt-2">
                                    <x-toggle :name="$inputName" :checked="false" :label="$field['label']" />
                                </div>
                            @else
                                <input id="{{ $inputId }}" name="{{ $inputName }}" type="{{ $field['type'] }}" value="{{ old($inputName) }}"
                                       @required($field['required'])
                                       @if ($field['help']) aria-describedby="{{ $inputId }}-help" @endif
                                       class="mt-1.5 block w-full rounded-lg border-0 py-2.5 px-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            @endif

                            @if ($field['help'])
                                <p id="{{ $inputId }}-help" class="mt-1.5 text-sm text-slate-500">{{ $field['help'] }}</p>
                            @endif
                            @error($inputName)<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="mt-8 w-full rounded-lg bg-brand-700 px-5 py-3.5 text-base font-semibold text-white transition hover:bg-brand-800">
                    Submit This Form
                </button>
            </form>
        </div>
    </x-site.section>
</x-layouts.public>
