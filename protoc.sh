#!/usr/bin/env bash

rm -rf ./src/Grpc
rm -rf ./temp
mkdir ./temp
protoc --proto_path=protos  --php_out=./temp  --grpc_out=./temp  --plugin=protoc-gen-grpc=/usr/local/bin/grpc_php_plugin ./protos/server.proto
mv ./temp/YiluTech/YiMQ/Grpc ./src/
rm -r ./temp


#https://cloud.google.com/php/grpc?hl=zh_cn#c-implementation