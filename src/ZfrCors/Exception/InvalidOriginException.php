<?php

namespace ZfrCors\Exception;

use DomainException;

/**
 * @license MIT
 * @author  Max Bösing <max@boesing.email>
 */
class InvalidOriginException extends DomainException implements ExceptionInterface
{

    /**
     * @return self
     */
    public static function fromInvalidHeaderValue()
    {
        return new self('Provided header value supposed to be invalid.');
    }
}
