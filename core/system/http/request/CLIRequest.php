<?php

namespace AC\core\system\http\request;

use RuntimeException;

/**
 * Class CLIRequest
 *
 * Represents a request from the command-line. Provides additional
 * tools to interact with that request since CLI requests are not
 * static like HTTP requests might be.
 *
 * Portions of this code were initially from the FuelPHP Framework,
 * version 1.7.x, and used here under the MIT license they were
 * originally made available under.
 *
 * http://fuelphp.com
 */
class CLIRequest extends Request
{
  /**
   * Stores the segments of our cli "URI" command.
   *
   * @var array
   */
  protected $segments = [];

  /**
   * Command line options and their values.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Set the expected HTTP verb
   *
   * @var string
   */
  protected $method = 'cli';

  /**
   * Constructor
   */
  public function __construct($config)
  {
    if (!is_cli()) {
      throw new RuntimeException(static::class . ' needs to run from the command line.'); // @codeCoverageIgnore
    }

    parent::__construct($config);

    // Don't terminate the script when the cli's tty goes away
    ignore_user_abort(true);

    $this->parseCommand();
  }

  /**
   * Returns the "path" of the request script so that it can be used
   * in routing to the appropriate controller/method.
   *
   * The path is determined by treating the command line arguments
   * as if it were a URL - up until we hit our first option.
   *
   * Example:
   *      php index.php users 21 profile -foo bar
   *
   *      // Routes to /users/21/profile (index is removed for routing sake)
   *      // with the option foo = bar.
   */
  public function getPath()
  {
    $path = implode('/', $this->segments);

    return empty($path) ? '' : $path;
  }

  /**
   * Returns an associative array of all CLI options found, with
   * their values.
   */
  public function getOptions()
  {
    return $this->options;
  }

  /**
   * Returns the path segments.
   */
  public function getSegments()
  {
    return $this->segments;
  }

  /**
   * Returns the value for a single CLI option that was passed in.
   *
   * @return string|null
   */
  public function getOption($key)
  {
    return isset($this->options[$key]) ? $this->options[$key] : null;
  }

  /**
   * Returns the options as a string, suitable for passing along on
   * the CLI to other commands.
   *
   * Example:
   *      $options = [
   *          'foo' => 'bar',
   *          'baz' => 'queue some stuff'
   *      ];
   *
   *      getOptionString() = '-foo bar -baz "queue some stuff"'
   */
  public function getOptionString($useLongOpts = false)
  {
    if (empty($this->options)) {
      return '';
    }

    $out = '';

    foreach ($this->options as $name => $value) {
      if ($useLongOpts && mb_strlen($name) > 1) {
        $out .= "--{$name} ";
      } else {
        $out .= "-{$name} ";
      }

      // If there's a space, we need to group
      // so it will pass correctly.
      if (mb_strpos($value, ' ') !== false) {
        $out .= '"' . $value . '" ';
      } elseif ($value !== null) {
        $out .= "{$value} ";
      }
    }

    return trim($out);
  }

  /**
   * Parses the command line it was called from and collects all options
   * and valid segments.
   *
   * NOTE: I tried to use getopt but had it fail occasionally to find
   * any options, where argv has always had our back.
   */
  protected function parseCommand()
  {
    $args = $this->getServer('argv');
    array_shift($args); // Scrap index.php

    $optionValue = false;

    foreach ($args as $i => $arg) {
      if (mb_strpos($arg, '-') !== 0) {
        if ($optionValue) {
          $optionValue = false;
        } else {
          $this->segments[] = filter_var($arg, FILTER_SANITIZE_STRING);
        }

        continue;
      }

      $arg   = filter_var(ltrim($arg, '-'), FILTER_SANITIZE_STRING);
      $value = null;

      if (isset($args[$i + 1]) && mb_strpos($args[$i + 1], '-') !== 0) {
        $value       = filter_var($args[$i + 1], FILTER_SANITIZE_STRING);
        $optionValue = true;
      }

      $this->options[$arg] = $value;
    }
  }

  /**
   * Determines if this request was made from the command line (CLI).
   */
  public function isCLI()
  {
    return is_cli();
  }
}
