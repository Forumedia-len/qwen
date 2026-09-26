<?php

namespace AC\core\system\exceptions;

use AC\core\system\exceptions\http\HTTPException;

/**
 * RedirectException
 */
class RedirectException extends HTTPException
{
    /**
     * Status code for redirects
     *
     * @var int
     */
    protected $code = 302;
}
