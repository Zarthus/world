#!/usr/bin/env bash

set -e

CA_DIR='ca'
mkdir $CA_DIR &>/dev/null || true

openssl req -x509 -signkey -out $CA_DIR/server.crt -keyout $CA_DIR/server.key \
  -newkey rsa:4096 -nodes -sha256  \
  -subj '/CN=localhost.world.dev/O=Nomenclature/C=EU' \
  -addext "subjectAltName=DNS:localhost.world.dev,DNS:world,DNS:localhost" \
  -addext "keyUsage=digitalSignature" \
  -addext "extendedKeyUsage=serverAuth"

cat $CA_DIR/server.key $CA_DIR/server.crt > $CA_DIR/server.pem

echo "[Success] CA created in: $CA_DIR"
echo "To make this certificate trusted, you need to add $CA_DIR/server.crt to your trusted roots or browser."
