# client grpcweb
docker-compose run --entrypoint "/usr/local/bin/protoc" protoc -I . -I /opt/include ./chat.proto --grpc-web_out=import_style=typescript,mode=grpcweb:/out/grpc-web --js_out=import_style=commonjs:/out/grpc-web --plugin=protoc-gen-grpc-web=/usr/local/bin/grpc_web_plugin
