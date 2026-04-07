<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

// [Inferencia] Necesario para tus PKs

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUlids, HasRoles, SoftDeletes;

    protected $primaryKey = 'ulid';

    protected $fillable = [
        'name',
        'lastname',
        'document',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
