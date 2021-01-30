docker-compose run protoc -f ./chat.proto -l web -o /out/grpc-web-text --js-out=import_style=commonjs:. --grpc-web-out=import_style=typescript,mode=grpcwebtext:.
