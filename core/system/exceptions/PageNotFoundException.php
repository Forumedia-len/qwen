<?php

namespace AC\core\system\exceptions;

use OutOfBoundsException;

class PageNotFoundException extends OutOfBoundsException implements ExceptionInterface
{

    /**
     * Error code
     *
     * @var int
     */
    protected $code = 404;
}
