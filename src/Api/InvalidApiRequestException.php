<?php

namespace Morebec\Orkestra\Framework\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Base Exception class for API related exceptions.
 */
class InvalidApiRequestException extends HttpException
{
    public function __construct(?string $message = 'Invalid API Request.', \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, $message, $previous, $headers, $code);
    }
}
