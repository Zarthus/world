#!/usr/bin/env bash

CA_DIR='ca'
mkdir $CA_DIR
openssl req -newkey rsa:2048 -nodes -keyout $CA_DIR/server.key -out $CA_DIR/server.csr
openssl x509 -signkey $CA_DIR/server.key -in $CA_DIR/server.csr -req -days 365 -out $CA_DIR/server.crt

cat $CA_DIR/server.key > $CA_DIR/server.pem
cat $CA_DIR/server.crt >> $CA_DIR/server.pem
