<?php

declare(strict_types=1);

namespace N1215\GrpcWebChatAmp\Grpc;

use Amp\ByteStream\InputStream;
use Amp\Http\InvalidHeaderException;
use Amp\Http\Server\Response;
use Amp\Http\Server\Trailers;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use LogicException;

/**
 * Class ResponseFactory
 * @package N1215\GrpcWebChatAmp\Grpc
 */
class ResponseFactory
{
    /**
     * @param InputStream|string $stream
     * @return Promise<Response>
     */
    public function success(InputStream|string $stream): Promise
    {
        try {
            $trailers = new Trailers(
                new Success(
                    [
                        'grpc-status' => '0',
                        'grpc-message' => 'OK'
                    ]
                ),
                ['grpc-status', 'grpc-message']
            );
        } catch (InvalidHeaderException $e) {
            throw new LogicException('failed to creat trailers', 0, $e);
        }

        $response = new Response(
            Status::OK,
            [
                'content-type' => 'application/grpc',
                'x-powered-by' => 'PHP/' . phpversion(),
            ],
            $stream,
            $trailers,
        );

        return new Success($response);
    }
}
