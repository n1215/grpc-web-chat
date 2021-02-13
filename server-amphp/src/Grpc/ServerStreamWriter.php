<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Grpc;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\IteratorStream;
use Amp\ByteStream\Payload;
use Amp\Emitter;
use Google\Protobuf\Internal\Message;

/**
 * Class ServerStreamWriter
 * @package N1215\GrpcWebChatAmp\Grpc
 */
class ServerStreamWriter
{
    private Emitter $emitter;

    public function __construct()
    {
        $this->emitter = new Emitter();
    }

    public function write(Message $message): void
    {
        $this->emitter->emit((new LengthPrefixedMessage($message))->serializeToString());
    }

    public function complete(): void
    {
        $this->emitter->complete();
    }

    public function getStream(): InputStream
    {
        return new Payload(new IteratorStream($this->emitter->iterate()));
    }
}
