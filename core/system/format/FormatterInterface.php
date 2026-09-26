<?php

namespace AC\core\system\format;

/**
 * Formatter interface
 */
interface FormatterInterface
{
    /**
     * Takes the given data and formats it.
     *
     * @param array|string $data
     *
     * @return mixed
     */
    public function format($data);
}
