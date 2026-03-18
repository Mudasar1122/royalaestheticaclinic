<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function codeGenerator(): View
    {
        return view('aiapplication.codeGenerator');
    }

    public function addUser(): View
    {
        return view('users.addUser');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'string', 'in:admin,manager,staff,viewer'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $resolvedModuleAccess = array_keys($this->moduleOptions());

        $profilePhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'module_access' => $resolvedModuleAccess,
            'module_permissions' => $this->defaultModulePermissions($resolvedModuleAccess),
            'password' => $validated['password'],
            'is_active' => $request->boolean('is_active', true),
            'profile_photo_path' => $profilePhotoPath,
        ]);

        return redirect()
            ->route('usersList')
            ->with('status', 'New user created successfully.');
    }

    public function usersGrid(): View
    {
        return view('users.usersGrid');
    }

    public function usersList(Request $request): View
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->get();

        return view('users.usersList', [
            'users' => $users,
        ]);
    }

    public function editPermissions(User $user): View
    {
        return view('users.assignPermission', [
            'editUser' => $user,
            'moduleOptions' => $this->moduleOptions(),
            'permissionOptions' => $this->modulePermissionOptions(),
            'selectedPermissions' => $this->resolvedUserPermissions($user),
        ]);
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->withErrors([
                'permissions' => 'Administrator already has full permissions.',
            ]);
        }

        $moduleAccess = $this->resolveModuleAccess((string) $user->role, $user->module_access ?? []);
        $permissionOptions = $this->modulePermissionOptions();
        $rules = [
            'permissions' => ['nullable', 'array'],
        ];

        foreach ($moduleAccess as $moduleKey) {
            $allowedOperations = array_keys($permissionOptions[$moduleKey] ?? []);
            $rules['permissions.'.$moduleKey] = ['nullable', 'array'];
            $rules['permissions.'.$moduleKey.'.*'] = ['string', Rule::in($allowedOperations)];
        }

        $validated = $request->validate($rules);
        $rawPermissions = $validated['permissions'] ?? [];
        $resolvedPermissions = [];

        foreach ($moduleAccess as $moduleKey) {
            $allowedOperations = array_keys($permissionOptions[$moduleKey] ?? []);
            $selectedOperations = is_array($rawPermissions[$moduleKey] ?? null) ? $rawPermissions[$moduleKey] : [];

            $resolvedPermissions[$moduleKey] = $this->normalizeModulePermissions(
                (string) $moduleKey,
                $selectedOperations,
                $allowedOperations
            );
        }

        $user->forceFill([
            'module_permissions' => $resolvedPermissions,
        ])->save();

        return redirect()
            ->route('usersList')
            ->with('status', 'Permissions updated successfully.');
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        if (!($admin?->isAdmin() ?? false)) {
            abort(403, 'Only administrators can log in as another user.');
        }

        if ($user->isAdmin()) {
            return back()->withErrors([
                'impersonate' => 'You can only log in as non-admin users.',
            ]);
        }

        if ($admin->is($user)) {
            return back()->withErrors([
                'impersonate' => 'You are already using this account.',
            ]);
        }

        $request->session()->put('impersonator_id', $admin->id);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('index')
            ->with('status', 'Logged in as '.$user->name.'.');
    }

    public function stopImpersonation(Request $request): RedirectResponse
    {
        $impersonatorId = (int) $request->session()->get('impersonator_id', 0);

        if ($impersonatorId <= 0) {
            return redirect()->route('index');
        }

        $request->session()->forget('impersonator_id');

        $admin = User::find($impersonatorId);

        if ($admin === null || !$admin->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('signin')
                ->withErrors(['email' => 'The original admin account could not be restored.']);
        }

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()
            ->route('usersList')
            ->with('status', 'Returned to admin account.');
    }

    public function editUser(User $user): View
    {
        return view('users.editUser', [
            'editUser' => $user,
            'moduleOptions' => $this->moduleOptions(),
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'string', 'in:admin,manager,staff,viewer'],
            'module_access' => ['nullable', 'array'],
            'module_access.*' => ['string', Rule::in(array_keys($this->moduleOptions()))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $isActive = $request->boolean('is_active', false);

        if ($request->user()?->id === $user->id && !$isActive) {
            return back()->withErrors([
                'is_active' => 'You cannot deactivate your own account.',
            ])->withInput();
        }

        if ($request->user()?->id === $user->id && (string) $validated['role'] !== 'admin') {
            return back()->withErrors([
                'role' => 'You cannot change your own admin role.',
            ])->withInput();
        }

        $resolvedModuleAccess = $this->resolveModuleAccess(
            (string) $validated['role'],
            $validated['module_access'] ?? []
        );

        if ($validated['role'] !== 'admin' && empty($resolvedModuleAccess)) {
            return back()
                ->withErrors(['module_access' => 'Select at least one module for this user.'])
                ->withInput();
        }

        if ($request->hasFile('profile_photo')) {
            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'module_access' => $resolvedModuleAccess,
            'module_permissions' => $this->syncModulePermissions(
                $user,
                $resolvedModuleAccess,
                (string) $validated['role']
            ),
            'is_active' => $isActive,
        ]);

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('usersList')
            ->with('status', 'User updated successfully.');
    }

    public function viewProfile(Request $request): View
    {
        return view('users.viewProfile', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->forceFill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ])->save();

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        return back()->with('password_status', 'Password changed successfully.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->user()?->id === $user->id && !$validated['is_active']) {
            return back()->withErrors([
                'status' => 'You cannot deactivate your own account.',
            ]);
        }

        $user->forceFill([
            'is_active' => $validated['is_active'],
        ])->save();

        return back()->with('status', 'User status updated.');
    }

    /**
     * @return array<string, string>
     */
    private function moduleOptions(): array
    {
        return [
            'lead_management' => 'Lead Management',
            'campaign_management' => 'Campaign Management',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function modulePermissionOptions(): array
    {
        return [
            'lead_management' => [
                'view_leads' => 'View Leads',
                'view_all_leads' => 'All Leads',
                'view_own_leads' => 'Own Leads Only',
                'create_lead' => 'Create Lead',
                'edit_lead' => 'Edit Lead',
                'manage_followups' => 'Manage Follow-ups',
                'mark_booked' => 'Mark as Booked',
                'send_whatsapp' => 'Send WhatsApp',
            ],
            'campaign_management' => [
                'view_campaigns' => 'View Campaigns',
                'send_email_campaign' => 'Send Email Campaign',
                'send_whatsapp_campaign' => 'Send WhatsApp Campaign',
            ],
        ];
    }

    /**
     * @param  array<int, string>|mixed  $rawModuleAccess
     * @return array<int, string>
     */
    private function resolveModuleAccess(string $role, $rawModuleAccess): array
    {
        $moduleKeys = array_keys($this->moduleOptions());

        if ($role === 'admin') {
            return $moduleKeys;
        }

        $requested = is_array($rawModuleAccess) ? $rawModuleAccess : [];

        return collect($requested)
            ->map(static fn ($module): string => (string) $module)
            ->filter(static fn (string $module): bool => in_array($module, $moduleKeys, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $modules
     * @return array<string, array<int, string>>
     */
    private function defaultModulePermissions(array $modules): array
    {
        $resolved = [];

        foreach ($modules as $moduleKey) {
            $moduleName = (string) $moduleKey;
            $resolved[$moduleName] = $this->defaultPermissionsForModule($moduleName);
        }

        return $resolved;
    }

    /**
     * @param  array<int, string>  $modules
     * @return array<string, array<int, string>>
     */
    private function syncModulePermissions(User $user, array $modules, string $role): array
    {
        if ($role === 'admin') {
            return $this->defaultModulePermissions(array_keys($this->moduleOptions()));
        }

        $permissionOptions = $this->modulePermissionOptions();
        $existingPermissions = is_array($user->module_permissions) ? $user->module_permissions : [];
        $resolved = [];

        foreach ($modules as $moduleKey) {
            $moduleName = (string) $moduleKey;
            $allowedOperations = array_keys($permissionOptions[$moduleName] ?? []);
            $hasStoredOperations = array_key_exists($moduleName, $existingPermissions);
            $storedOperations = $hasStoredOperations && is_array($existingPermissions[$moduleName] ?? null)
                ? $existingPermissions[$moduleName]
                : null;

            if ($storedOperations === null) {
                $resolved[$moduleName] = $this->defaultPermissionsForModule($moduleName);
                continue;
            }

            $resolved[$moduleName] = $this->normalizeModulePermissions($moduleName, $storedOperations, $allowedOperations);
        }

        return $resolved;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function resolvedUserPermissions(User $user): array
    {
        $allowedModules = $user->isAdmin()
            ? array_keys($this->moduleOptions())
            : $this->resolveModuleAccess((string) $user->role, $user->module_access ?? []);

        if ($user->isAdmin()) {
            return $this->defaultModulePermissions($allowedModules);
        }

        $storedPermissions = is_array($user->module_permissions) ? $user->module_permissions : [];
        $permissionOptions = $this->modulePermissionOptions();
        $resolved = [];

        foreach ($allowedModules as $moduleKey) {
            $moduleName = (string) $moduleKey;
            $allowedOperations = array_keys($permissionOptions[$moduleName] ?? []);
            $hasStoredPermissions = array_key_exists($moduleName, $storedPermissions);
            $moduleStoredPermissions = $hasStoredPermissions && is_array($storedPermissions[$moduleName] ?? null)
                ? $storedPermissions[$moduleName]
                : null;

            if ($moduleStoredPermissions === null) {
                $resolved[$moduleName] = $this->defaultPermissionsForModule($moduleName);
                continue;
            }

            $resolved[$moduleName] = $this->normalizeModulePermissions(
                $moduleName,
                $moduleStoredPermissions,
                $allowedOperations
            );
        }

        return $resolved;
    }

    /**
     * @return array<int, string>
     */
    private function defaultPermissionsForModule(string $module): array
    {
        $permissions = array_keys($this->modulePermissionOptions()[$module] ?? []);

        if ($module === 'lead_management') {
            $permissions = array_values(array_filter(
                $permissions,
                static fn (string $permission): bool => $permission !== 'view_all_leads'
            ));
        }

        return $permissions;
    }

    /**
     * @param  array<int, string>  $allowedOperations
     * @param  array<int, string>|mixed  $operations
     * @return array<int, string>
     */
    private function normalizeModulePermissions(string $module, $operations, array $allowedOperations): array
    {
        $resolved = collect(is_array($operations) ? $operations : [])
            ->map(static fn ($operation): string => (string) $operation)
            ->filter(static fn (string $operation): bool => in_array($operation, $allowedOperations, true))
            ->unique()
            ->values()
            ->all();

        if (
            $module === 'lead_management'
            && !in_array('view_all_leads', $resolved, true)
            && !in_array('view_own_leads', $resolved, true)
        ) {
            $resolved[] = 'view_own_leads';
        }

        if (
            $module === 'lead_management'
            && in_array('view_all_leads', $resolved, true)
            && in_array('view_own_leads', $resolved, true)
        ) {
            $resolved = array_values(array_filter(
                $resolved,
                static fn (string $permission): bool => $permission !== 'view_own_leads'
            ));
        }

        return array_values(array_unique($resolved));
    }
}
