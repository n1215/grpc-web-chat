<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Grpc;

use Google\Protobuf\Internal\Message;

/**
 * Class LengthPrefixedMessage
 * @package N1215\GrpcWebChatAmp\Grpc
 */
class LengthPrefixedMessage
{
    public function __construct(private Message $message)
    {
    }

    public function serializeToString(): string
    {
        $serializedMessage = $this->message->serializeToString();
        return pack('c', 0x00)
            . pack('N', strlen($serializedMessage))
            . $serializedMessage;
    }

    public static function unwrap(string $binaryLengthPrefixedMessage): string
    {
        return substr($binaryLengthPrefixedMessage, 5);
    }
}
