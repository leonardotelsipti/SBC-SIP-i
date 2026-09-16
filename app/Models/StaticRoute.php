<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticRoute extends Model
{
    protected $table = 'static_routes';

    protected $fillable = [
        'destination',
        'netmask',
        'gateway',
        'interface_name',
        'metric',
        'description',
        'is_active',
    ];

    protected $casts = [
        'metric' => 'integer',
        'is_active' => 'boolean',
    ];
}
