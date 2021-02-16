#!/usr/bin/env sh

##### CA #####
# 秘密鍵
openssl genrsa -out ca.key 2048

# 署名要求
openssl req -new -sha256 -key ca.key -subj "/C=JP/ST=Kyoto/O=gRPC Web Chat CA" -out ca.csr

# 証明書
openssl x509 -req -sha256 -days 3650 -signkey ca.key -in ca.csr -out ca.crt

# 証明書検証
openssl x509 -in ca.crt -text -noout

##### サーバ #####
# 秘密鍵
openssl genrsa -out server.key 2048

# 署名要求
openssl req -new -sha256 -key server.key -out server.csr -config csr.conf -extensions v3_req

# 証明書
openssl x509 -req -sha256 -days 825 -in server.csr -CA ca.crt -CAkey ca.key -CAcreateserial -out server.crt -extfile csr.conf -extensions v3_req

# 証明書検証
openssl x509 -in server.crt -text -noout
