#!/usr/bin/env bash
# ==============================================================================
# SBC Telsipti - Teste de Ligação em Tempo Real no Debian 12 via SIPp
# Envia INVITE SIP-i multipart (SDP + ISUP IAM) e valida 180 ACM / 200 OK
# ==============================================================================

set -euo pipefail

TARGET="${1:-127.0.0.1:5060}"
SRC="${2:-1130030100}"
DST="${3:-11987654321}"

echo "====================================================================="
echo "   Disparando Teste de Ligação SIP-i em Tempo Real                   "
echo "   Alvo SBC: $TARGET | Origem: $SRC | Destino: $DST               "
echo "====================================================================="

if ! command -v sipp &> /dev/null; then
    echo "Instalando SIPp via apt-get..."
    apt-get update && apt-get install -y sipp
fi

cat << 'EOF' > /tmp/sipi-live-test.xml
<?xml version="1.0" encoding="ISO-8859-1" ?>
<!DOCTYPE scenario SYSTEM "sipp.dtd">
<scenario name="SBC Telsipti Live Call Test">
  <send retrans="500">
    <![CDATA[
      INVITE sip:[field1]@[remote_ip]:[remote_port] SIP/2.0
      Via: SIP/2.0/[transport] [local_ip]:[local_port];branch=[branch]
      From: <sip:[field0]@[local_ip]:[local_port]>;tag=[call_number]
      To: <sip:[field1]@[remote_ip]:[remote_port]>
      Call-ID: [call_id]
      CSeq: 1 INVITE
      Contact: sip:[field0]@[local_ip]:[local_port]
      Max-Forwards: 70
      User-Agent: Kamailio-SBC-SIPp-Tester
      Content-Type: multipart/mixed; boundary=telsipti-sipi-boundary
      Content-Length: [len]

      --telsipti-sipi-boundary
      Content-Type: application/sdp

      v=0
      o=user1 53655765 2353687637 IN IP[local_ip_type] [local_ip]
      s=Audio
      c=IN IP[media_ip_type] [media_ip]
      t=0 0
      m=audio [media_port] RTP/AVP 8 101
      a=rtpmap:8 PCMA/8000
      a=rtpmap:101 telephone-event/8000

      --telsipti-sipi-boundary
      Content-Type: application/isup; version=itu-t92+; base=itu-t
      Content-Disposition: signal; handling=required

      010040000a01031198765432
      --telsipti-sipi-boundary--
    ]]>
  </send>

  <recv response="100" optional="true"></recv>
  <recv response="180" optional="true"></recv>
  <recv response="200" rtd="true"></recv>

  <send>
    <![CDATA[
      ACK sip:[field1]@[remote_ip]:[remote_port] SIP/2.0
      Via: SIP/2.0/[transport] [local_ip]:[local_port];branch=[branch]
      From: <sip:[field0]@[local_ip]:[local_port]>;tag=[call_number]
      To: <sip:[field1]@[remote_ip]:[remote_port]>[peer_tag_param]
      Call-ID: [call_id]
      CSeq: 1 ACK
      Contact: sip:[field0]@[local_ip]:[local_port]
      Max-Forwards: 70
      Content-Length: 0
    ]]>
  </send>

  <pause milliseconds="5000"/>

  <send retrans="500">
    <![CDATA[
      BYE sip:[field1]@[remote_ip]:[remote_port] SIP/2.0
      Via: SIP/2.0/[transport] [local_ip]:[local_port];branch=[branch]
      From: <sip:[field0]@[local_ip]:[local_port]>;tag=[call_number]
      To: <sip:[field1]@[remote_ip]:[remote_port]>[peer_tag_param]
      Call-ID: [call_id]
      CSeq: 2 BYE
      Max-Forwards: 70
      Content-Length: 0
    ]]>
  </send>

  <recv response="200" crlf="true"></recv>
</scenario>
EOF

sipp -sf /tmp/sipi-live-test.xml -s "$DST" "$TARGET" -m 1 -r 1 -s "$SRC" -trace_err -trace_msg
echo "=== Chamada de teste concluída com sucesso! Verifique no painel ou sngrep ==="
