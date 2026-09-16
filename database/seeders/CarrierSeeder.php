<?php

namespace Database\Seeders;

use App\Models\Carrier;
use Illuminate\Database\Seeder;

class CarrierSeeder extends Seeder
{
    public function run(): void
    {
        Carrier::firstOrCreate(
            ['rn1_code' => '031'],
            [
                'name' => 'Oi (Telemar Norte Leste / Brasil Telecom)',
                'inbound_rate' => 0.0280,
                'outbound_rate' => 0.0420,
                'carrier_type' => 'INCUMBENT_CONCESSIONARIA',
                'is_active' => true,
                'contact_noc' => 'noc-interconexao@oi.net.br / 0800 031 0001',
                'notes' => 'Concessionária STFC Regiões I e II. Interconexão SIP-i homologada Anatel.',
            ]
        );

        Carrier::firstOrCreate(
            ['rn1_code' => '021'],
            [
                'name' => 'Claro / Embratel',
                'inbound_rate' => 0.0295,
                'outbound_rate' => 0.0450,
                'carrier_type' => 'INCUMBENT_CONCESSIONARIA',
                'is_active' => true,
                'contact_noc' => 'noc.embratel@claro.com.br / 0800 721 2121',
                'notes' => 'Concessionária STFC Longa Distância Nacional e Internacional.',
            ]
        );

        Carrier::firstOrCreate(
            ['rn1_code' => '015'],
            [
                'name' => 'Telefônica / Vivo',
                'inbound_rate' => 0.0310,
                'outbound_rate' => 0.0490,
                'carrier_type' => 'INCUMBENT_CONCESSIONARIA',
                'is_active' => true,
                'contact_noc' => 'intercon.telefonica@vivo.com.br / 0800 015 1515',
                'notes' => 'Concessionária STFC Região III (Estado de São Paulo).',
            ]
        );

        Carrier::firstOrCreate(
            ['rn1_code' => '041'],
            [
                'name' => 'TIM Brasil',
                'inbound_rate' => 0.0270,
                'outbound_rate' => 0.0380,
                'carrier_type' => 'AUTORIZADA_ESPELHO',
                'is_active' => true,
                'contact_noc' => 'noc.carrier@timbrasil.com.br / 0800 741 4141',
                'notes' => 'Autorizada STFC e SMP Nacional.',
            ]
        );
    }
}
