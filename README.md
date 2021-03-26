# gRPC-Web チャット サンプル
PHPerKaigi 2021発表用のサンプルコード

## 動作環境環境
- Docker Desktop for Mac 2.5.0.1
- Google Chrome 89.0.4389.90

## 環境構築

```
git clone https://github.com/n1215/grpc-web-chat
cd grpc-web-chat

# 初期化
./task init

# 起動
./task up
```

- 環境構築後に http://localhost:8080 にアクセス
- クライアント側のwebpack DevServerの起動に少し時間がかかります

### 備考
#### C＃実装のサーバに切り替え
`http://localhost:8080?server=csharp` でアクセスするとC#実装のサーバに接続
#### 自己署名証明書を利用しています
証明書のエラーでつながらない場合は、一度ブラウザでサーバのURL（下記）にアクセスして許可してください

## コマンド

### 起動
```
./task up
```

### 停止
```
./task down
```

### .protoファイルからコードを自動生成
```
./task proto:codegen
```

## 構成

### チャットクライアント
- [client-grpc-web-text](client-grpc-web-text)
  - http://localhost:8080
  - TypeScript
  - protocのgRPC-Web用プラグインのmode=grpcwebtextでクライアントコードを生成

### チャットサーバ
- [server-amphp](server-amphp)
  - https://localhost:9000
  - PHP + [Amp](https://amphp.org/) によるUnary RPC、Server Streaming RPC実装
  - [Envoy](https://www.envoyproxy.io/) によるProxy
  - protocのPHPクライアント用プラグインでMessage部分のみ自動生成
- [server-dotnet](server-dotnet)
  - https://localhost:5001
  - C# + ASP.NET Core
  - In-process Proxy方式のため、Kestrel単体で動作
