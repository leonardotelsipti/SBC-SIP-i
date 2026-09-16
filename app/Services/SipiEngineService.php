<?php

namespace App\Services;

/**
 * Service para gerenciamento de encapsulamento SIP-i (ITU-T Q.1912.5)
 * Converte mensagens ISUP (IAM, ACM, ANM, REL, RLC) para SIP com MIME Multipart
 */
class SipiEngineService
{
    /**
     * Gera os cabeçalhos e corpo MIME para encapsulamento ISUP no SIP INVITE
     */
    public function buildSipiInviteBody(string $sdp, array $isupParams): array
    {
        $boundary = 'unique-boundary-' . bin2hex(random_bytes(8));
        $isupHex = $this->encodeInitialAddressMessage($isupParams);

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: application/sdp\r\n\r\n";
        $body .= $sdp . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/isup; version=itu-t92+; base=itu-t\r\n";
        $body .= "Content-Disposition: signal; handling=required\r\n\r\n";
        $body .= hex2bin($isupHex) . "\r\n";
        $body .= "--{$boundary}--\r\n";

        return [
            'content_type' => "multipart/mixed; boundary={$boundary}",
            'body' => $body,
            'isup_hex' => $isupHex,
        ];
    }

    /**
     * Codifica parâmetros ISUP IAM (Initial Address Message)
     */
    public function encodeInitialAddressMessage(array $params): string
    {
        $cpc = dechex($params['cpc'] ?? 10);
        $cpc = str_pad($cpc, 2, '0', STR_PAD_LEFT);
        
        $natureOfAddress = dechex($params['noa'] ?? 3);
        $natureOfAddress = str_pad($natureOfAddress, 2, '0', STR_PAD_LEFT);

        return "01004000{$cpc}01{$natureOfAddress}" . bin2hex($params['src'] ?? '0000');
    }

    /**
     * Mapeia códigos de causa ISUP (Q.850) para códigos SIP
     */
    public function isupToSipCause(int $isupCause): int
    {
        return match($isupCause) {
            16 => 200, // Normal clearing -> OK
            17 => 486, // User busy -> Busy Here
            18, 19 => 480, // No user responding / no answer -> Temporarily Unavailable
            1  => 404, // Unallocated number -> Not Found
            34 => 503, // No circuit available -> Service Unavailable
            21 => 603, // Call rejected -> Decline
            default => 500, // Server internal error
        };
    }
}
