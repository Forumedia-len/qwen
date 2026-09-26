<?php

namespace AC\core\system\format;


use AC\app\config\FormatConfig;
use AC\core\system\exceptions\format\FormatException;

/**
 * JSON data formatter
 */
class JSONFormatter implements FormatterInterface
{
    /**
     * Takes the given data and formats it.
     *
     * @param mixed $data
     *
     * @return bool|string (JSON string | false)
     */
    public function format($data)
    {
        $config = new FormatConfig();

        $options = isset($config->formatterOptions['application/json']) ? $config->formatterOptions['application/json'] : JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $options = $options | JSON_PARTIAL_OUTPUT_ON_ERROR;

        $options = ENVIRONMENT === 'production' ? $options : $options | JSON_PRETTY_PRINT;

        $result = json_encode($data, $options, 512);

        if (! in_array(json_last_error(), [JSON_ERROR_NONE, JSON_ERROR_RECURSION], true)) {
            throw FormatException::forInvalidJSON(json_last_error_msg());
        }

        return $result;
    }
}
