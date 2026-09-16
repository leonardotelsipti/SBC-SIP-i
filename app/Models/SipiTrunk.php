<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SipiTrunk extends Model
{
    protected $table = 'sipi_trunks';

    protected $fillable = [
        'name',
        'carrier_id',
        'carrier_name',
        'remote_host',
        'port',
        'transport',
        'sipi_standard',
        'isup_dialect',
        'default_cpc',
        'default_noa',
        'charge_number_enabled',
        'status',
        'max_channels',
        'codecs',
    ];

    protected $casts = [
        'port' => 'integer',
        'max_channels' => 'integer',
        'charge_number_enabled' => 'boolean',
        'codecs' => 'array',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }
}
