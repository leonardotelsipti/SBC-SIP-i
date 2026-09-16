#!/usr/bin/env php
<?php
/**
 * Script de restauração de rede para inicialização via Systemd no Debian 12
 * Executado pelo serviço sbc-network-restore.service após o mariadb.service
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

use App\Models\NetworkAlias;
use App\Models\StaticRoute;
use App\Services\LinuxNetworkService;

echo "[" . date('Y-m-d H:i:s') . "] Iniciando restauração de rede SBC Telsipti...\n";

try {
    $service = new LinuxNetworkService();
    $aliases = NetworkAlias::all();
    echo "Restaurando " . $aliases->count() . " aliases IP virtuais...\n";
    foreach ($aliases as $alias) {
        $res = $service->addIpAlias($alias->device, $alias->ip_address, $alias->netmask, $alias->alias_id);
        echo " - [{$alias->device}:{$alias->alias_id}] {$alias->ip_address} => " . ($res['success'] ? "OK" : "FALHA: {$res['output']}") . "\n";
    }

    $routes = StaticRoute::where('is_active', true)->get();
    echo "Restaurando " . $routes->count() . " rotas estáticas ativas...\n";
    foreach ($routes as $route) {
        $res = $service->addStaticRoute($route->destination, $route->netmask, $route->gateway, $route->interface_name, $route->metric);
        echo " - [{$route->destination}] via {$route->gateway} => " . ($res['success'] ? "OK" : "FALHA: {$res['output']}") . "\n";
    }

    echo "[" . date('Y-m-d H:i:s') . "] Restauração de rede concluída com sucesso!\n";
} catch (\Throwable $e) {
    echo "ERRO ao restaurar rede: " . $e->getMessage() . "\n";
    exit(1);
}
