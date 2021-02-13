<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use N1215\GrpcWebChatAmp\Grpc\RequestBodyDeserializer;
use N1215\GrpcWebChatAmp\Grpc\ResponseFactory;
use N1215\GrpcWebChatAmp\Handlers\SendMessageRequestHandler;
use N1215\GrpcWebChatAmp\Handlers\SubscribeRequestHandler;

Amp\Loop::run(
    function () {
        $cert = new Amp\Socket\Certificate('/etc/certs/server.crt', '/etc/certs/server.key');
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

        $requestBodyDeserializer = new RequestBodyDeserializer();
        $responseFactory = new ResponseFactory();

        $logHandler = new \Amp\Log\StreamHandler(new Amp\ByteStream\ResourceOutputStream(\STDOUT));
        $logHandler->setFormatter(new \Amp\Log\ConsoleFormatter());
        $logger = new Monolog\Logger('server');
        $logger->pushHandler($logHandler);

        $router = new Amp\Http\Server\Router();
        $chatMessageSubject = new Rx\Subject\Subject();
        $router->addRoute(
            'POST',
            '/GrpcWebChat.Chat/SendMessage',
            new SendMessageRequestHandler($chatMessageSubject, $requestBodyDeserializer, $responseFactory, $logger)
        );
        $router->addRoute(
            'POST',
            '/GrpcWebChat.Chat/Subscribe',
            new SubscribeRequestHandler($chatMessageSubject, $responseFactory, $logger)
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
