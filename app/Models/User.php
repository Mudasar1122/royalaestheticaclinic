<?php

namespace App\Models;

use App\Notifications\Auth\RoyalResetPasswordNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'profile_photo_path',
        'password',
        'role',
        'module_access',
        'module_permissions',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'module_access' => 'array',
            'module_permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to_user_id');
    }

    public function assignedFollowUps(): HasMany
    {
        return $this->hasMany(FollowUp::class, 'assigned_to_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasModule(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $moduleAccess = is_array($this->module_access) ? $this->module_access : [];

        return in_array($module, $moduleAccess, true);
    }

    public function hasModulePermission(string $module, string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->hasModule($module)) {
            return false;
        }

        $permissionMap = is_array($this->module_permissions) ? $this->module_permissions : [];

        if (!array_key_exists($module, $permissionMap)) {
            // Backward compatibility for users created before operation-level permissions.
            return true;
        }

        $modulePermissions = $permissionMap[$module];

        if (!is_array($modulePermissions)) {
            return true;
        }

        return in_array($permission, $modulePermissions, true);
    }

    /**
     * @return array<int, string>
     */
    public function explicitModulePermissions(string $module): array
    {
        $permissionMap = is_array($this->module_permissions) ? $this->module_permissions : [];
        $modulePermissions = $permissionMap[$module] ?? [];

        if (!is_array($modulePermissions)) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn ($permission): string => (string) $permission,
            $modulePermissions
        )));
    }

    public function hasExplicitModulePermission(string $module, string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->explicitModulePermissions($module), true);
    }

    public function leadVisibilityScope(): string
    {
        if ($this->isAdmin()) {
            return 'all';
        }

        return $this->hasExplicitModulePermission('lead_management', 'view_all_leads')
            ? 'all'
            : 'own';
    }

    /**
     * @return array<int, string>
     */
    public function moduleAccessLabels(): array
    {
        $moduleOptions = [
            'lead_management' => 'Lead Management',
            'campaign_management' => 'Campaign Management',
        ];

        $moduleAccess = $this->isAdmin()
            ? array_keys($moduleOptions)
            : (is_array($this->module_access) ? $this->module_access : []);

        $labels = [];

        foreach ($moduleAccess as $module) {
            $moduleKey = (string) $module;
            if (isset($moduleOptions[$moduleKey])) {
                $labels[] = $moduleOptions[$moduleKey];
            }
        }

        return array_values(array_unique($labels));
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RoyalResetPasswordNotification($token));
    }
}
