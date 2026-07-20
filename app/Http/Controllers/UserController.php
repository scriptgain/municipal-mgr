<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Staff login accounts and their roles.
 *
 * Roles matter here: a small town wants the Parks director to post their own
 * events without also being able to edit the budget page or delete users. A
 * department editor is scoped to their department; a site editor can touch all
 * content but no settings; only an administrator can do both.
 */
class UserController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Administrators Only.');
    }

    public function index()
    {
        $this->ensureAdmin();

        return view('settings.users.index', [
            'users' => User::with('department')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->ensureAdmin();

        return view('settings.users.create', $this->formData() + ['user' => new User]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate($this->rules() + [
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['password_changed_at'] = now();
        $data['is_active'] = $request->boolean('is_active', true);

        User::create($data);

        return redirect()->route('settings.users.index')
            ->with('status', "User \"{$data['name']}\" Created.");
    }

    public function edit(User $user)
    {
        $this->ensureAdmin();

        return view('settings.users.edit', $this->formData() + compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin();

        $data = $request->validate($this->rules() + [
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Never let the signed-in admin demote or deactivate themselves out of
        // the panel — recovering from that needs shell access.
        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            return back()->with('warning', 'You Cannot Remove Your Own Administrator Role.');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
            $data['password_changed_at'] = now();
        }

        $data['is_active'] = $user->id === auth()->id() ? true : $request->boolean('is_active', true);

        // A department editor without a department can edit nothing, which is a
        // confusing way to fail. Require the pairing.
        if ($data['role'] === 'department_editor' && empty($data['department_id'])) {
            return back()->withInput()->withErrors([
                'department_id' => 'A Department Editor Must Be Assigned To A Department.',
            ]);
        }

        $user->update($data);

        return redirect()->route('settings.users.index')
            ->with('status', "User \"{$user->name}\" Updated.");
    }

    public function destroy(User $user)
    {
        $this->ensureAdmin();

        if ($user->id === auth()->id()) {
            return back()->with('warning', 'You Cannot Delete Your Own Account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('settings.users.index')
            ->with('status', "User \"{$name}\" Deleted.");
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in(array_keys(config('municipal.roles')))],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    private function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'roles' => config('municipal.roles'),
        ];
    }
}
