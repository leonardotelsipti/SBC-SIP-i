<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Carrier extends Model
{
    protected $table = 'carriers';

    protected $fillable = [
        'name',
        'rn1_code',
        'inbound_rate',
        'outbound_rate',
        'carrier_type',
        'is_active',
        'contact_noc',
        'notes',
    ];

    protected $casts = [
        'inbound_rate' => 'decimal:4',
        'outbound_rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function trunks(): HasMany
    {
        return $this->hasMany(SipiTrunk::class, 'carrier_id');
    }

    public function cdrs(): HasMany
    {
        return $this->hasMany(Cdr::class, 'carrier_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Calcula o balanço financeiro DETRAF da operadora para um período específico
     */
    public function getDetrafSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $inboundQuery = $this->cdrs()->where('direction', 'INBOUND');
        $outboundQuery = $this->cdrs()->where('direction', 'OUTBOUND');

        if ($startDate) {
            $inboundQuery->where('calldate', '>=', $startDate);
            $outboundQuery->where('calldate', '>=', $startDate);
        }
        if ($endDate) {
            $inboundQuery->where('calldate', '<=', $endDate);
            $outboundQuery->where('calldate', '<=', $endDate);
        }

        $inboundCalls = (clone $inboundQuery)->count();
        $inboundBillsec = (clone $inboundQuery)->where('disposition', 'ANSWERED')->sum('billsec');
        $inboundMinutes = $inboundBillsec / 60;
        $inboundReceivable = $inboundMinutes * (float)$this->inbound_rate;

        $outboundCalls = (clone $outboundQuery)->count();
        $outboundBillsec = (clone $outboundQuery)->where('disposition', 'ANSWERED')->sum('billsec');
        $outboundMinutes = $outboundBillsec / 60;
        $outboundPayable = $outboundMinutes * (float)$this->outbound_rate;

        return [
            'carrier_id' => $this->id,
            'carrier_name' => $this->name,
            'rn1_code' => $this->rn1_code,
            'inbound_calls' => $inboundCalls,
            'inbound_minutes' => round($inboundMinutes, 2),
            'inbound_rate' => (float)$this->inbound_rate,
            'inbound_receivable' => round($inboundReceivable, 2),
            'outbound_calls' => $outboundCalls,
            'outbound_minutes' => round($outboundMinutes, 2),
            'outbound_rate' => (float)$this->outbound_rate,
            'outbound_payable' => round($outboundPayable, 2),
            'net_balance' => round($inboundReceivable - $outboundPayable, 2),
        ];
    }
}
