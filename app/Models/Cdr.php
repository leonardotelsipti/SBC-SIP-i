<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Cdr extends Model
{
    protected $table = 'cdrs';

    protected $fillable = [
        'calldate',
        'clid',
        'src',
        'dst',
        'dcontext',
        'channel',
        'dstchannel',
        'lastapp',
        'lastdata',
        'duration',
        'billsec',
        'disposition',
        'sip_hangup_cause',
        'isup_cause',
        'isup_cause_desc',
        'trunk_in',
        'trunk_out',
        'sipi_encapsulated',
        'carrier_id',
        'carrier_name',
        'direction',
        'detraf_rate',
        'detraf_type',
        'detraf_amount',
        'client_code',
        'cost',
        'rate_per_min',
        'pdd_ms',
        'isup_cpc',
        'isup_noa',
        'isup_charge_number',
    ];

    protected $casts = [
        'calldate' => 'datetime',
        'duration' => 'integer',
        'billsec' => 'integer',
        'sip_hangup_cause' => 'integer',
        'isup_cause' => 'integer',
        'sipi_encapsulated' => 'boolean',
        'detraf_rate' => 'decimal:4',
        'detraf_amount' => 'decimal:4',
        'cost' => 'decimal:4',
        'rate_per_min' => 'decimal:4',
        'pdd_ms' => 'integer',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    public function scopeForClient(Builder $query, string $clientCode): Builder
    {
        return $query->where('client_code', $clientCode);
    }

    public function scopeAnswered(Builder $query): Builder
    {
        return $query->where('disposition', 'ANSWERED');
    }

    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', 'INBOUND');
    }

    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', 'OUTBOUND');
    }
}
