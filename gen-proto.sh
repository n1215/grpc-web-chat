# client grpcweb
docker-compose run --entrypoint "/usr/local/bin/protoc" protoc -I . -I /opt/include ./chat.proto --grpc-web_out=import_style=typescript,mode=grpcweb:/out/grpc-web --js_out=import_style=commonjs:/out/grpc-web --plugin=protoc-gen-grpc-web=/usr/local/bin/grpc_web_plugin

# client grpcwebtext
docker-compose run --entrypoint "/usr/local/bin/protoc" protoc -I . -I /opt/include ./chat.proto --grpc-web_out=import_style=typescript,mode=grpcwebtext:/out/grpc-web-text --js_out=import_style=commonjs:/out/grpc-web-text --plugin=protoc-gen-grpc-web=/usr/local/bin/grpc_web_plugin

# server amphp
docker-compose run --entrypoint "/usr/local/bin/protoc" protoc -I . -I /opt/include ./chat.proto --php_out=/out/grpc-php --grpc_out=/out/grpc-php --plugin=protoc-gen-grpc=/usr/local/bin/grpc_php_plugin
