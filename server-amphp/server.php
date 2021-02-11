<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Http\Server\Options;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Monolog\Logger;
use N1215\GrpcWebChatAmp\Handlers\SendMessageRequestHandler;
use N1215\GrpcWebChatAmp\Handlers\SubscribeRequestHandler;

Amp\Loop::run(function () {
    $cert = new Socket\Certificate('/etc/certs/server.crt', '/etc/certs/server.key');
    $context = (new Socket\BindContext())
        ->withTlsContext(
            (new Socket\ServerTlsContext())
                ->withDefaultCertificate($cert)
                ->withCaFile('/etc/certs/ca.crt')
                ->withApplicationLayerProtocols(['h2'])
        );

    $servers = [
        Socket\Server::listen('0.0.0.0:1338', $context),
        Socket\Server::listen('[::]:1338', $context),
    ];

    $logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);

    $router = new Router();
    $chatMessageSubject = new Rx\Subject\Subject();
    $router->addRoute(
        'POST',
        '/GrpcWebChat.Chat/SendMessage',
        new SendMessageRequestHandler($chatMessageSubject, $logger)
    );
    $router->addRoute(
        'POST',
        '/GrpcWebChat.Chat/Subscribe',
        new SubscribeRequestHandler($chatMessageSubject, $logger)
    );

    $server = new HttpServer(
        $servers,
        $router,
        $logger,
        (new Options())->withoutCompression()
    );

    yield $server->start();

    // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
    Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        Amp\Loop::cancel($watcherId);
        yield $server->stop();
    });
});
