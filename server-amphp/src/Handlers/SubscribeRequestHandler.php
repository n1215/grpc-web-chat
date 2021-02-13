<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Handlers;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use Google\Protobuf\GPBEmpty;
use GrpcWebChat\ChatMessage;
use N1215\GrpcWebChatAmp\Grpc\ResponseFactory;
use N1215\GrpcWebChatAmp\Grpc\ServerStreamWriter;
use Psr\Log\LoggerInterface;
use Rx\ObservableInterface;

/**
 * Class SubscribeRequestHandler
 * @package N1215\GrpcWebChatAmp\Http
 */
class SubscribeRequestHandler implements RequestHandler
{
    public function __construct(
        private ObservableInterface $chatMessageStream,
        private ResponseFactory $responseFactory,
        private LoggerInterface $logger
    ) {
    }

    public function handleRequest(Request $request): Promise
    {
        $streamWriter = new ServerStreamWriter();

        $this->service(new GPBEmpty(), $streamWriter);

        return $this->responseFactory->success($streamWriter->getStream());
    }

    private function service(GPBEmpty $request, ServerStreamWriter $streamWriter): void
    {
        $this->chatMessageStream
            ->subscribe(
                function (ChatMessage $chatMessage) use ($streamWriter) {
                    $this->logger->debug("emit", [$chatMessage->getBody()]);
                    $streamWriter->write($chatMessage);
                },
                null,
                function () use ($streamWriter) {
                    $this->logger->debug("complete");
                    $streamWriter->complete();
                }
            );
    }
}
