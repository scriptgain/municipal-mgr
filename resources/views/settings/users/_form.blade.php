<x-card title="Account Details">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Full Name" for="name" required :error="$errors->first('name')">
            <x-input id="name" name="name" :value="old('name', $user->name)" required />
        </x-field>

        <x-field label="Email Address" for="email" required :error="$errors->first('email')">
            <x-input id="email" name="email" type="email" :value="old('email', $user->email)" required />
        </x-field>

        <x-field label="Role" for="role" required :error="$errors->first('role')">
            <x-select id="role" name="role">
                @foreach ($roles as $value => $roleLabel)
                    <option value="{{ $value }}" @selected(old('role', $user->role ?: 'viewer') === $value)>{{ $roleLabel }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Department" for="department_id"
                 hint="Required for a Department Editor – it defines what they may edit." :error="$errors->first('department_id')">
            <x-select id="department_id" name="department_id">
                <option value="">No Department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $user->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-field label="Job Title" for="job_title" :error="$errors->first('job_title')">
            <x-input id="job_title" name="job_title" :value="old('job_title', $user->job_title)" />
        </x-field>

        <x-field label="Phone" for="phone" :error="$errors->first('phone')">
            <x-input id="phone" name="phone" :value="old('phone', $user->phone)" />
        </x-field>

        <x-field label="Password" for="password" :required="! $user->exists"
                 :hint="$user->exists ? 'Leave blank to keep the current password.' : 'At least eight characters.'"
                 :error="$errors->first('password')">
            <x-input id="password" name="password" type="password" autocomplete="new-password" @if (! $user->exists) required @endif />
        </x-field>

        <x-field label="Confirm Password" for="password_confirmation" :required="! $user->exists">
            <x-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @if (! $user->exists) required @endif />
        </x-field>

        <div class="sm:col-span-2">
            <x-toggle name="is_active" :checked="old('is_active', $user->is_active ?? true)"
                      label="Account Is Active"
                      description="Disabling blocks sign-in without deleting the account or its history." />
        </div>
    </div>

    <div class="section-divider mt-6 pt-5 text-sm text-slate-500">
        <p class="font-medium text-slate-700">What The Roles Mean</p>
        <ul class="mt-2 space-y-1">
            <li><span class="font-medium text-slate-700">Administrator</span> – everything, including settings, users, and security.</li>
            <li><span class="font-medium text-slate-700">Site Editor</span> – all content across every department, but no settings or users.</li>
            <li><span class="font-medium text-slate-700">Department Editor</span> – content belonging to their own department only.</li>
            <li><span class="font-medium text-slate-700">Read Only</span> – can sign in and look, but cannot change anything.</li>
        </ul>
    </div>
</x-card>
