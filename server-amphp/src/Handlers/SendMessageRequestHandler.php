<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Handlers;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use N1215\GrpcWebChatAmp\Grpc\RequestBodyDeserializer;
use GrpcWebChat\SendMessageRequest;
use N1215\GrpcWebChatAmp\Grpc\LengthPrefixedMessage;
use N1215\GrpcWebChatAmp\Grpc\ResponseFactory;
use N1215\GrpcWebChatAmp\Service\ChatService;

use function Amp\call;

/**
 * Class SendMessageRequestHandler
 * @package N1215\GrpcWebChatAmp\Http
 */
class SendMessageRequestHandler implements RequestHandler
{
    public function __construct(
        private ChatService $chatService,
        private RequestBodyDeserializer $requestBodyDeserializer,
        private ResponseFactory $responseFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handleRequest(Request $request): Promise
    {
        return call(
            function ($request) {
                $serviceRequest = new SendMessageRequest();
                yield $this->requestBodyDeserializer->deserialize($request, $serviceRequest);

                $serviceResponse = yield $this->chatService->sendMessage($serviceRequest);

                $stream = (new LengthPrefixedMessage($serviceResponse))->serializeToString();
                return $this->responseFactory->success($stream);
            },
            $request
        );
    }
}
