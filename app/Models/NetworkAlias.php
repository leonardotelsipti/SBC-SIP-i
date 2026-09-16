<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkAlias extends Model
{
    protected $table = 'network_aliases';
    public $timestamps = false;

    protected $fillable = [
        'alias_id',
        'ip_address',
        'netmask',
        'device',
    ];
}
