<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkInterface extends Model
{
    protected $table = 'network_interfaces';

    protected $fillable = [
        'name',
        'label',
        'role_type',
        'ip_address',
        'netmask',
        'gateway',
        'dns',
        'mtu',
        'status',
        'mac_address',
    ];

    protected $casts = [
        'mtu' => 'integer',
    ];
}
