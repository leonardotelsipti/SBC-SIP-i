<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cdrs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('calldate')->index();
            $table->string('clid', 80);
            $table->string('src', 80)->index();
            $table->string('dst', 80)->index();
            $table->string('dcontext', 80)->default('sbc-incoming');
            $table->string('channel', 80)->nullable();
            $table->string('dstchannel', 80)->nullable();
            $table->string('lastapp', 80)->default('Dial');
            $table->string('lastdata', 255)->nullable();
            $table->integer('duration')->default(0);
            $table->integer('billsec')->default(0);
            $table->enum('disposition', ['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED'])->default('FAILED')->index();
            $table->integer('sip_hangup_cause')->default(200);
            $table->integer('isup_cause')->default(16);
            $table->string('isup_cause_desc', 120)->nullable();
            $table->string('trunk_in', 60)->nullable()->index();
            $table->string('trunk_out', 60)->nullable()->index();
            $table->boolean('sipi_encapsulated')->default(true);
            
            // Campos DETRAF e Operadoras
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->string('carrier_name', 100)->nullable()->index();
            $table->enum('direction', ['INBOUND', 'OUTBOUND'])->default('INBOUND')->index();
            $table->decimal('detraf_rate', 8, 4)->default(0.0000);
            $table->enum('detraf_type', ['RECEIVABLE', 'PAYABLE'])->default('RECEIVABLE');
            $table->decimal('detraf_amount', 10, 4)->default(0.0000);

            $table->string('client_code', 40)->nullable()->index();
            $table->decimal('cost', 10, 4)->default(0);
            $table->decimal('rate_per_min', 8, 4)->default(0.10);
            $table->integer('pdd_ms')->default(1200);
            $table->string('isup_cpc', 40)->nullable();
            $table->string('isup_noa', 40)->nullable();
            $table->string('isup_charge_number', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdrs');
    }
};
