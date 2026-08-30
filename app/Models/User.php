<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'locale', 'mfa_required', 'last_login_at', 'status', 'must_change_password', 'mfa_verified_at', 'mfa_recovery_codes'])]
#[Hidden(['password', 'remember_token', 'mfa_recovery_codes'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'mfa_required' => 'boolean',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'mfa_verified_at' => 'datetime',
            'mfa_recovery_codes' => 'array',
        ];
    }

    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    public function hasPermission(string $permission, array $scope = []): bool
    {
        return $this->roles()->get()
            ->contains(function (Role $role) use ($permission, $scope): bool {
                if ($role->pivot->revoked_at !== null
                    || ($role->pivot->starts_at !== null && $role->pivot->starts_at->isAfter(now()))
                    || ($role->pivot->ends_at !== null && $role->pivot->ends_at->isBefore(now()))) {
                    return false;
                }
                if (! in_array('*', $role->permissions, true) && ! in_array($permission, $role->permissions, true)) {
                    return false;
                }

                foreach (['campus_id', 'department_id', 'program_id'] as $key) {
                    $assigned = $role->pivot->{$key};
                    if ($assigned !== null && isset($scope[$key]) && (string) $scope[$key] !== (string) $assigned) {
                        return false;
                    }
                }

                return true;
            });
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_assignments')
            ->withPivot(['campus_id', 'department_id', 'program_id', 'starts_at', 'ends_at', 'revoked_at', 'revoked_by', 'assigned_by'])
            ->withCasts(['starts_at' => 'datetime', 'ends_at' => 'datetime', 'revoked_at' => 'datetime']);
    }

    public function isPrivileged(): bool
    {
        return $this->roles()->where('privileged', true)->wherePivotNull('revoked_at')->exists();
    }
}
