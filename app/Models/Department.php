<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class Department extends Model
{
    use EncryptsAttributes, HasRoles;

    protected $fillable = ['name'];
    protected $encryptableAttributes = ['name'];
    protected $guard_name = 'web';

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'department_role', 'department_id', 'role_id')->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'department_has_permissions', 'department_id', 'permission_id');
    }
}
