#!/usr/bin/env bash

rm -rf ./src/Grpc
mkdir ./src/Grpc
protoc --proto_path=protos  --php_out=./src/Grpc  --grpc_out=./src/Grpc  --plugin=protoc-gen-grpc=/usr/local/bin/grpc_php_plugin ./protos/server.proto
mv ./src/Grpc/YiluTech/YiMQ/Grpc/Services ./src/Grpc/
mv ./src/Grpc/YiluTech/YiMQ/Grpc/GPBMetadata ./src/Grpc
rm -r ./src/Grpc/YiluTech