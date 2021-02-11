<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Handlers;

use Amp\ByteStream\IteratorStream;
use Amp\ByteStream\Payload;
use Amp\Delayed;
use Amp\Emitter;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Trailers;
use Amp\Http\Status;
use Amp\Producer;
use Amp\Promise;
use Amp\Success;
use DateTime;
use Google\Protobuf\Timestamp;
use GrpcWebChat\ChatMessage;
use N1215\GrpcWebChatAmp\Grpc\LengthPrefixedMessage;
use Psr\Log\LoggerInterface;

use Rx\ObservableInterface;

use function Amp\call;

/**
 * Class SubscribeRequestHandler
 * @package N1215\GrpcWebChatAmp\Http
 */
class SubscribeRequestHandler implements RequestHandler
{
    /**
     * SubscribeRequestHandler constructor.
     * @param ObservableInterface<ChatMessage> $chatMessageStream
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ObservableInterface $chatMessageStream,
        private LoggerInterface $logger
    ) {}

    /**
     * @param Request $request
     * @return Promise
     */
    public function handleRequest(Request $request): Promise
    {
        return call([$this, 'handle'], $request);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Amp\Http\InvalidHeaderException
     */
    public function handle(Request $request): Response
    {
        $trailers = new Trailers(
            new Success(
                [
                    'grpc-status' => '0',
                    'grpc-message' => 'OK'
                ]
            ),
            ['grpc-stats', 'grpc-message']
        );

        $emitter = new Emitter();
        $stream = new Payload(new IteratorStream($emitter->iterate()));

        $this->chatMessageStream
            ->subscribe(
                function (ChatMessage $chatMessage) use ($emitter) {
                    $this->logger->debug("emit", [$chatMessage->getBody()]);
                    $emitter->emit((new LengthPrefixedMessage($chatMessage))->serializeToString());
                },
                null,
                function () use ($emitter) {
                    $this->logger->debug("complete");
                    $emitter->complete();
                }
            );

        return new Response(
            Status::OK,
            [
                'content-type' => 'application/grpc',
                'x-powered-by' => 'PHP/' . phpversion(),
            ],
            $stream,
            $trailers,
        );
    }
}
