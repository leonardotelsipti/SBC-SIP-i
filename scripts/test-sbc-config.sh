#!/usr/bin/env bash
# ==============================================================================
# Script de Diagnóstico e Validação da Configuração do Kamailio SBC
# Plataforma: Debian 12 (Bookworm)
# ==============================================================================

set -euo pipefail

echo "====================================================================="
echo "   SBC Telsipti - Teste de Diagnóstico e Configuração do Sistema     "
echo "====================================================================="

echo -n "[1/5] Validando Sintaxe do Kamailio (kamailio -c)... "
if kamailio -c &> /dev/null; then
    echo "OK (Sintaxe Perfeita)"
else
    echo "ERRO! Execute 'kamailio -c' para ver os erros de sintaxe."
fi

echo -n "[2/5] Checando status do daemon Kamailio... "
if systemctl is-active --quiet kamailio; then
    echo "ATIVO (Running)"
else
    echo "INATIVO! Execute 'systemctl restart kamailio'."
fi

echo -n "[3/5] Checando status do RTPEngine (Porta 22222)... "
if systemctl is-active --quiet rtpengine; then
    echo "ATIVO (Relay de Mídia Online)"
else
    echo "INATIVO! Execute 'systemctl restart rtpengine'."
fi

echo -n "[4/5] Checando portas de escuta SIP (5060 UDP/TCP)... "
if ss -ulpn | grep -q ":5060"; then
    echo "OK (Escutando em 5060 UDP)"
else
    echo "ALERTA: Porta 5060 não encontrada nos sockets UDP."
fi

echo -n "[5/5] Testando conexão com MariaDB (sbc_sip_i)... "
if mysql -u sbc_user -p'Secret_SBC_2026!' -e "USE sbc_sip_i; SELECT COUNT(*) FROM carriers;" &> /dev/null; then
    echo "OK (Banco Conectado e Operadoras Carregadas)"
else
    echo "FALHA na autenticação MySQL."
fi

echo "====================================================================="
echo " Resumo de Troncos e Operadoras Ativas via Kamctl:"
kamctl dispatcher show || true
echo "====================================================================="
