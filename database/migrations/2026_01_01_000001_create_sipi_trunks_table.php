<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sipi_trunks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->string('carrier_name', 100)->nullable();
            $table->string('remote_host', 120);
            $table->integer('port')->default(5060);
            $table->enum('transport', ['UDP', 'TCP', 'TLS'])->default('UDP');
            $table->string('sipi_standard', 60)->default('ITU-T Q.1912.5 Profile C');
            $table->enum('isup_dialect', ['BR-TELEBRAS', 'ITU-T', 'ANSI', 'ETSI'])->default('BR-TELEBRAS');
            $table->string('default_cpc', 60)->default('Ordinary calling subscriber (10)');
            $table->string('default_noa', 60)->default('National (significant) number (03)');
            $table->boolean('charge_number_enabled')->default(true);
            $table->enum('status', ['ONLINE', 'OFFLINE', 'CONGESTION'])->default('ONLINE');
            $table->integer('max_channels')->default(120);
            $table->json('codecs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sipi_trunks');
    }
};
