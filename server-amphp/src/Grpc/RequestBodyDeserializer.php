<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Grpc;

use Amp\Http\Server\Request;
use Amp\Promise;
use Google\Protobuf\Internal\Message;

use function Amp\call;

/**
 * Class RequestBodyDeserializer
 * @package N1215\GrpcWebChatAmp\Grpc
 */
class RequestBodyDeserializer
{
    /**
     * @param Request $request
     * @param Message $message
     * @return Promise<void>
     */
    public function deserialize(Request $request, Message $message): Promise
    {
        return call(
            function () use ($request, $message) {
                $contents = yield $request->getBody()->buffer();
                $message->mergeFromString(LengthPrefixedMessage::unwrap($contents));
            }
        );
    }
}
