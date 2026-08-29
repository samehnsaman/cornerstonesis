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

#[Fillable(['name', 'email', 'password', 'locale', 'mfa_required', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
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
        ];
    }

    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->get()
            ->contains(fn (Role $role): bool => in_array('*', $role->permissions, true)
                || in_array($permission, $role->permissions, true));
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_assignments')
            ->withPivot(['campus_id', 'department_id', 'program_id', 'starts_at', 'ends_at']);
    }
}
