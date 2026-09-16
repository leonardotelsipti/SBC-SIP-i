<?php

namespace App\Services;

use App\Models\NetworkAlias;
use App\Models\StaticRoute;
use Illuminate\Support\Facades\Log;

/**
 * Service para replicação e sincronização imediata de IPs e Rotas com o Kernel Linux (Debian 12)
 * Utiliza o binário /sbin/ip via sudo sem senha (conforme /etc/sudoers.d/kamailio_routes)
 */
class LinuxNetworkService
{
    /**
     * Aplica um endereço IP virtual (alias) na placa de rede do Linux
     * Exemplo: sudo /sbin/ip addr replace 10.250.250.90/29 dev eth1 label eth1:1
     */
    public function addIpAlias(string $device, string $ipAddress, string $netmask, string $aliasId): array
    {
        $this->validateNetworkInputs($device, $ipAddress, $netmask);
        $cidr = $this->netmaskToCidr($netmask);
        $cmd = sprintf(
            'sudo /sbin/ip addr replace %s/%d dev %s label %s:%s 2>&1',
            escapeshellarg($ipAddress),
            (int)$cidr,
            escapeshellarg($device),
            escapeshellarg($device),
            escapeshellarg($aliasId)
        );

        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        $success = ($returnVar === 0);
        Log::info("LinuxNetworkService::addIpAlias [{$cmd}] - Resultado: " . implode("\n", $output));

        return [
            'success' => $success,
            'command' => $cmd,
            'output' => implode("\n", $output),
            'return_code' => $returnVar,
        ];
    }

    /**
     * Remove um endereço IP virtual da placa de rede
     * Exemplo: sudo /sbin/ip addr del 10.250.250.90/29 dev eth1
     */
    public function deleteIpAlias(string $device, string $ipAddress, string $netmask): array
    {
        $this->validateNetworkInputs($device, $ipAddress, $netmask);
        $cidr = $this->netmaskToCidr($netmask);
        $cmd = sprintf(
            'sudo /sbin/ip addr del %s/%d dev %s 2>&1',
            escapeshellarg($ipAddress),
            (int)$cidr,
            escapeshellarg($device)
        );

        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        Log::info("LinuxNetworkService::deleteIpAlias [{$cmd}]");

        return [
            'success' => ($returnVar === 0),
            'command' => $cmd,
            'output' => implode("\n", $output),
        ];
    }

    /**
     * Aplica ou atualiza uma rota estática no kernel Linux
     * Exemplo: sudo /sbin/ip route replace 10.200.0.0/16 via 10.200.50.9 dev eth1 metric 10
     */
    public function addStaticRoute(string $destination, string $netmask, string $gateway, string $interface, int $metric = 100): array
    {
        $cidr = $this->netmaskToCidr($netmask);
        $cmd = sprintf(
            'sudo /sbin/ip route replace %s/%d via %s dev %s metric %d 2>&1',
            escapeshellarg($destination),
            (int)$cidr,
            escapeshellarg($gateway),
            escapeshellarg($interface),
            (int)$metric
        );

        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        Log::info("LinuxNetworkService::addStaticRoute [{$cmd}]");

        return [
            'success' => ($returnVar === 0),
            'command' => $cmd,
            'output' => implode("\n", $output),
        ];
    }

    /**
     * Remove uma rota estática do kernel
     * Exemplo: sudo /sbin/ip route del 10.200.0.0/16 via 10.200.50.9
     */
    public function deleteStaticRoute(string $destination, string $netmask, string $gateway): array
    {
        $cidr = $this->netmaskToCidr($netmask);
        $cmd = sprintf(
            'sudo /sbin/ip route del %s/%d via %s 2>&1',
            escapeshellarg($destination),
            (int)$cidr,
            escapeshellarg($gateway)
        );

        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        return [
            'success' => ($returnVar === 0),
            'command' => $cmd,
            'output' => implode("\n", $output),
        ];
    }

    /**
     * Restaura todos os IPs virtuais e rotas cadastrados no banco de dados (usado no Boot pelo systemd)
     */
    public function restoreAllFromDatabase(): array
    {
        $aliases = NetworkAlias::all();
        $routes = StaticRoute::where('is_active', true)->get();
        $results = [];

        foreach ($aliases as $alias) {
            $results[] = $this->addIpAlias($alias->device, $alias->ip_address, $alias->netmask, $alias->alias_id);
        }

        foreach ($routes as $route) {
            $results[] = $this->addStaticRoute($route->destination, $route->netmask, $route->gateway, $route->interface_name, $route->metric);
        }

        return $results;
    }

    private function validateNetworkInputs(string $device, string $ip, string $mask): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $device)) {
            throw new \InvalidArgumentException("Nome de dispositivo inválido: {$device}");
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new \InvalidArgumentException("Endereço IP inválido: {$ip}");
        }
    }

    public function netmaskToCidr(string $netmask): int
    {
        $long = ip2long($netmask);
        $base = ip2long('255.255.255.255');
        return 32 - log(($long ^ $base) + 1, 2);
    }
}
