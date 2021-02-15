<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Handlers;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use Google\Protobuf\GPBEmpty;
use N1215\GrpcWebChatAmp\Grpc\ResponseFactory;
use N1215\GrpcWebChatAmp\Grpc\ServerStreamWriter;
use N1215\GrpcWebChatAmp\Service\ChatService;

use function Amp\call;

/**
 * Class SubscribeRequestHandler
 * @package N1215\GrpcWebChatAmp\Http
 */
class SubscribeRequestHandler implements RequestHandler
{
    public function __construct(
        private ChatService $chatService,
        private ResponseFactory $responseFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handleRequest(Request $request): Promise
    {
        return call(
            function () {
                $streamWriter = new ServerStreamWriter();
                yield $this->chatService->subscribe(new GPBEmpty(), $streamWriter);
                return $this->responseFactory->success($streamWriter->getStream());
            }
        );
    }
}
