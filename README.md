# gRPC-Web Chat Example
WIP

## requirements
- Docker Compose
- Node.js v14.16.1

## chat client implementations
- [client-grpc-web-text](client-grpc-web-text)
  - http://localhost:8080
  - mode=grpcwebtext
- [client-grpc-web](client-grpc-web)
  - http://localhost:8081
  - mode=grpcweb
  - Server streaming RPCs are not supported yet. See [Wire Format Mode](https://github.com/grpc/grpc-web#wire-format-mode)

## chat server implementations
- [server-dotnet](server-dotnet)
  - https://localhost:5001
  - C# + ASP.NET Core
- [server-amphp](server-amphp)
  - https://localhost:9000
  - PHP + Amp
  - Envoy Proxy

## code generation
```
sh ./gen-proto.sh
```
