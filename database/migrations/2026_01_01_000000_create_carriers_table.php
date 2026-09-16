<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('rn1_code', 3)->unique()->comment('Código RN1 da Anatel para roteamento');
            $table->decimal('inbound_rate', 8, 4)->default(0.0280)->comment('TU-RL de Entrada a Receber por minuto');
            $table->decimal('outbound_rate', 8, 4)->default(0.0420)->comment('TU-RL de Saída a Pagar por minuto');
            $table->enum('carrier_type', [
                'INCUMBENT_CONCESSIONARIA',
                'AUTORIZADA_ESPELHO',
                'MOVEL_SMP',
                'TRANSITO_ATACADO'
            ])->default('INCUMBENT_CONCESSIONARIA');
            $table->boolean('is_active')->default(true);
            $table->string('contact_noc', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};
