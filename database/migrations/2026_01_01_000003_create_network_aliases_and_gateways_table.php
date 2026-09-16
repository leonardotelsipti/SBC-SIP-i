<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias_id', 10)->default('1')->comment('Identificador virtual ethX:alias_id');
            $table->string('ip_address', 45);
            $table->string('netmask', 45);
            $table->string('device', 30)->default('eth1');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('ip_address', 45);
            $table->integer('port')->default(5060);
            $table->string('from_ip', 45)->nullable();
            $table->string('interface_name', 30)->nullable();
            $table->string('gateway_gw', 45)->nullable();
            $table->boolean('active')->default(true);
            $table->string('type', 20)->default('CARRIER');
            $table->string('route_gw', 45)->nullable();
            $table->string('interface', 30)->nullable();
            $table->integer('metric')->default(100);
            $table->string('protocol', 10)->default('UDP');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateways');
        Schema::dropIfExists('network_aliases');
    }
};
