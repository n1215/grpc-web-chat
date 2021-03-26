<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Service;

use Amp\Promise;
use Amp\Success;
use DateTime;
use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Timestamp;
use GrpcWebChat\ChatMessage;
use GrpcWebChat\SendMessageRequest;
use N1215\GrpcWebChatAmp\Grpc\ServerStreamWriter;
use Psr\Log\LoggerInterface;
use Rx\Subject\Subject;

/**
 * Class ChatService
 * @package N1215\GrpcWebChatAmp\Service
 */
class ChatService
{
    public function __construct(
        private Subject $chatMessageSubject,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param SendMessageRequest $request
     * @return Promise<GPBEmpty>
     */
    public function sendMessage(SendMessageRequest $request): Promise
    {
        $date = new Timestamp();
        $date->fromDateTime(new DateTime());
        $chatMessage = new ChatMessage();
        $chatMessage->setBody($request->getBody());
        $chatMessage->setName($request->getName());
        $chatMessage->setDate($date);

        // publish chat message
        $this->chatMessageSubject->onNext($chatMessage);

        return new Success(new GPBEmpty());
    }

    /**
     * @param GPBEmpty $request
     * @param ServerStreamWriter $streamWriter
     * @return Promise<void>
     */
    public function subscribe(GPBEmpty $request, ServerStreamWriter $streamWriter): Promise
    {
        $this->chatMessageSubject->subscribe(
            function (ChatMessage $chatMessage) use ($streamWriter) {
                $this->logger->debug("emit", [$chatMessage->getBody()]);
                $streamWriter->write($chatMessage);
            },
            null,
            function () use ($streamWriter) {
                $streamWriter->complete();
            }
        );

        return new Success();
    }
}
