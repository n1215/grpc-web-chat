<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Handlers;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Trailers;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use DateTime;
use Exception;
use Generator;
use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Timestamp;
use GrpcWebChat\ChatMessage;
use GrpcWebChat\SendMessageRequest;
use N1215\GrpcWebChatAmp\Grpc\LengthPrefixedMessage;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Rx\ObserverInterface;

use function Amp\call;

/**
 * Class SendMessageRequestHandler
 * @package N1215\GrpcWebChatAmp\Http
 */
class SendMessageRequestHandler implements RequestHandler
{
    /**
     * SendMessageRequestHandler constructor.
     * @param ObserverInterface<ChatMessage> $chatMessageObserver
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ObserverInterface $chatMessageObserver,
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
     * @return Generator
     */
    public function handle(Request $request): Generator
    {
        $contents = yield $request->getBody()->buffer();

        $sendMessageRequest = new SendMessageRequest();
        try {
            $sendMessageRequest->mergeFromString(LengthPrefixedMessage::unwrap($contents));
        } catch (Exception $e) {
            throw new RuntimeException('failed to parse request body.', 0, $e);
        }

        $date = new Timestamp();
        $date->fromDateTime(new DateTime());
        $chatMessage = new ChatMessage();
        $chatMessage->setBody($sendMessageRequest->getBody());
        $chatMessage->setName($sendMessageRequest->getName());
        $chatMessage->setDate($date);
        $this->chatMessageObserver->onNext($chatMessage);

        $trailers = new Trailers(
            new Success(
                [
                    'grpc-status' => '0',
                    'grpc-message' => 'OK'
                ]
            ),
            ['grpc-stats', 'grpc-message']
        );

        $message = new GPBEmpty();
        $stream = (new LengthPrefixedMessage($message))->serializeToString();

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
