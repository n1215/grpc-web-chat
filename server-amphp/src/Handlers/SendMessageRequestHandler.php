<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Handlers;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use Amp\Success;
use DateTime;
use N1215\GrpcWebChatAmp\Grpc\RequestBodyDeserializer;
use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Timestamp;
use GrpcWebChat\ChatMessage;
use GrpcWebChat\SendMessageRequest;
use N1215\GrpcWebChatAmp\Grpc\LengthPrefixedMessage;
use N1215\GrpcWebChatAmp\Grpc\ResponseFactory;
use Psr\Log\LoggerInterface;
use Rx\ObserverInterface;

use function Amp\call;

/**
 * Class SendMessageRequestHandler
 * @package N1215\GrpcWebChatAmp\Http
 */
class SendMessageRequestHandler implements RequestHandler
{
    public function __construct(
        private ObserverInterface $chatMessageObserver,
        private RequestBodyDeserializer $requestBodyDeserializer,
        private ResponseFactory $responseFactory,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param Request $request
     * @return Promise
     */
    public function handleRequest(Request $request): Promise
    {
        return call(
            function ($request) {
                $serviceRequest = new SendMessageRequest();
                yield $this->requestBodyDeserializer->deserialize($request, $serviceRequest);

                $serviceResponse = yield $this->service($serviceRequest);

                $stream = (new LengthPrefixedMessage($serviceResponse))->serializeToString();
                return $this->responseFactory->success($stream);
            },
            $request
        );
    }

    /**
     * @param SendMessageRequest $request
     * @return Promise<GPBEmpty>
     */
    private function service(SendMessageRequest $request): Promise
    {
        $date = new Timestamp();
        $date->fromDateTime(new DateTime());
        $chatMessage = new ChatMessage();
        $chatMessage->setBody($request->getBody());
        $chatMessage->setName($request->getName());
        $chatMessage->setDate($date);

        // publish chat message
        $this->chatMessageObserver->onNext($chatMessage);

        return new Success(new GPBEmpty());
    }
}
