{{-- Spam protection block. Markup only; every decision was made in
     App\View\Components\Captcha. The baseline honeypot + time-trap is always
     present; the provider widget and its vendor script appear only when active.
     Vendor scripts are the one external dependency the house style allows, and
     load only for the provider actually in use. --}}
<div class="mm-captcha">
    {!! $baselineFields !!}

    @if ($providerActive)
        <div class="mt-6">
            {!! $widget !!}
            @error('captcha')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        @foreach ($scripts as $src)
            <script src="{{ $src }}" async defer></script>
        @endforeach

        @if ($needsHelperJs)
            <script defer src="{{ asset_v('js/captcha.js') }}"></script>
        @endif
    @else
        @error('captcha')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    @endif
</div>
