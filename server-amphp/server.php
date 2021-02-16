<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use N1215\GrpcWebChatAmp\Grpc\RequestBodyDeserializer;
use N1215\GrpcWebChatAmp\Grpc\ResponseFactory;
use N1215\GrpcWebChatAmp\Handlers\SendMessageRequestHandler;
use N1215\GrpcWebChatAmp\Handlers\SubscribeRequestHandler;
use N1215\GrpcWebChatAmp\Service\ChatService;

Amp\Loop::run(
    function () {
        $cert = new Amp\Socket\Certificate(__DIR__ . '/certs/server.crt', __DIR__ . '/certs/server.key');
        $context = (new Amp\Socket\BindContext())
            ->withTlsContext(
                (new Amp\Socket\ServerTlsContext())
                    ->withDefaultCertificate($cert)
                    ->withCaFile('/etc/certs/ca.crt')
                    ->withApplicationLayerProtocols(['h2'])
            );

        $servers = [
            Amp\Socket\Server::listen('0.0.0.0:1338', $context),
            Amp\Socket\Server::listen('[::]:1338', $context),
        ];

        $logHandler = new Amp\Log\StreamHandler(new Amp\ByteStream\ResourceOutputStream(STDOUT));
        $logHandler->setFormatter(new Amp\Log\ConsoleFormatter());
        $logger = new Monolog\Logger('server');
        $logger->pushHandler($logHandler);

        $requestBodyDeserializer = new RequestBodyDeserializer();
        $responseFactory = new ResponseFactory();
        $router = new Amp\Http\Server\Router();
        $chatService = new ChatService(
            new Rx\Subject\Subject(),
            $logger
        );
        $router->addRoute(
            'POST',
            '/GrpcWebChat.Chat/SendMessage',
            new SendMessageRequestHandler($chatService, $requestBodyDeserializer, $responseFactory)
        );
        $router->addRoute(
            'POST',
            '/GrpcWebChat.Chat/Subscribe',
            new SubscribeRequestHandler($chatService, $responseFactory)
        );

        $server = new Amp\Http\Server\HttpServer(
            $servers,
            $router,
            $logger,
            (new Amp\Http\Server\Options())
                ->withoutCompression()
                ->withHttp2Timeout(3600)
        );

        yield $server->start();

        // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
        Amp\Loop::onSignal(
            SIGINT,
            function (string $watcherId) use ($server) {
                Amp\Loop::cancel($watcherId);
                yield $server->stop();
            }
        );
    }
);
