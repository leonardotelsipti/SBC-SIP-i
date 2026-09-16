# SBC Telsipti - SIP-i (Kamailio 5.8 & Laravel 13 no Debian 12)

Sistema completo de Session Border Controller (SBC) para telecomunicações no **Debian 12 (Bookworm)**:
- Núcleo SBC de alta capacidade: **Kamailio 5.8+** com relé de mídia **RTPEngine**
- Encapsulamento **SIP-i** (ITU-T Q.1912.5 Profile B/C) com mensagens ISUP multipart/mime (IAM, ACM, ANM, REL, RLC)
- **Módulo DETRAF STFC**: Cadastro de operadoras (Oi, Claro, Vivo, TIM), tarifas TU-RL de entrada/saída e apuração bilateral
- Suporte a placas digitais **E1 Aligera AG5600** (SS7 / ISUP / R2)
- Bilhetagem **CDR em tempo real** com ASR, ACD, NER, PDD e causas de terminação Q.850
- Painel Web em **PHP 8.5**, **Laravel 13** e **Laravel Boost**
- Ferramentas de **Diagnóstico** e **Testes de Ligação em Tempo Real** integradas

## Requisitos do Sistema
- **Debian 12 Bookworm**
- **Kamailio 5.8+** (oficial deb.kamailio.org)
- **RTPEngine**
- **PHP 8.5** & **Laravel 13**
- **MariaDB 10.11+** e **Nginx**

## Instalação Automatizada no Debian 12
```bash
chmod +x scripts/install-debian12-kamailio-sbc.sh
sudo ./scripts/install-debian12-kamailio-sbc.sh
```

## Testes de Configuração e Diagnóstico
```bash
chmod +x scripts/test-sbc-config.sh
sudo ./scripts/test-sbc-config.sh
```

## Teste de Ligação em Tempo Real (SIPp)
```bash
chmod +x scripts/test-call-sipp.sh
sudo ./scripts/test-call-sipp.sh 127.0.0.1:5060 1130030100 11987654321
```

## Documentação e Manual em PDF
O manual completo passo a passo formatado para impressão e leitura está localizado em:
- Arquivo PDF: `docs/MANUAL_INSTALACAO_PASSO_A_PASSO_SBC_TELSIPTI.pdf`
- Arquivo Markdown: `docs/MANUAL_DE_INSTALACAO_PASSO_A_PASSO.md`
