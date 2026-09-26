<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;
use AC\core\system\format\FormatterInterface;


class FormatConfig extends BaseConfig
{
  /**
   * --------------------------------------------------------------------------
   * Available Response Formats
   * --------------------------------------------------------------------------
   *
   * When you perform content negotiation with the request, these are the
   * available formats that your application supports. This is currently
   * only used with the api\ResponseTrait. A valid Formatter must exist
   * for the specified format.
   *
   * These formats are only checked when the data passed to the respond()
   * method is an array.
   *
   * @var string[]
   */
  public $supportedResponseFormats
    = [
      'application/json',
      'application/xml', // machine-readable XML
      'text/xml', // human-readable XML
    ];

  /**
   * --------------------------------------------------------------------------
   * Formatters
   * --------------------------------------------------------------------------
   *
   * Lists the class to use to format responses with of a particular type.
   * For each mime type, list the class that should be used. Formatters
   * can be retrieved through the getFormatter() method.
   *
   * @var array<string, string>
   */
  public $formatters
    = [
      'application/json' => 'AC\core\system\format\JSONFormatter',
      'application/xml'  => 'AC\core\system\format\XMLFormatter',
      'text/xml'         => 'AC\core\system\format\XMLFormatter',
    ];

  /**
   * --------------------------------------------------------------------------
   * Formatters Options
   * --------------------------------------------------------------------------
   *
   * Additional Options to adjust default formatters behaviour.
   * For each mime type, list the additional options that should be used.
   *
   * @var array<string, int>
   */
  public $formatterOptions
    = [
      'application/json' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
      'application/xml'  => 0,
      'text/xml'         => 0,
    ];

  /**
   * A Factory method to return the appropriate formatter for the given mime type.
   *
   * @return FormatterInterface
   *
   * 3   */
  public function getFormatter($mime)
  {
    return \Service::format()->getFormatter($mime);
  }
}
