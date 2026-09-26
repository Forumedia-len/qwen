<?php

namespace AC\core\system\object\entity\format;
use JetBrains\PhpStorm\NoReturn;

/**
 *
 * @todo пределать по аналогии с Json
*/
class CSV implements FormatInterface
{
  private static string $separator = ';';

  private array $_data = [];

  /**
   * {@inheritDoc}
   */
  public function get(array $params = [])
  {
    return $this->_data;
  }

  /**
   * {@inheritDoc}
   */
  public function set($value, array $params = []): string
  {
    return implode($params['separator'] ?? self::$separator, $value);
  }

  public function addRowFromArray(array $value)
  {
    $this->_data[] = self::set($value);
  }

  public function addRowsFromArray(array $arrayRows)
  {
    foreach ($arrayRows as $row) {
      $this->addRowFromArray($row);
    }

    return $this;
  }

  public function getAsString(): string
  {
    return self::set($this->_data, ['separator' => "\n"]);
  }

  #[NoReturn]
  public function getAsFile($fileName = 'file_export'): string
  {
    header("Content-Disposition: attachment; filename=" . $fileName . ".csv");
    header("Content-Type: application/x-force-download; name=\"" . $fileName . ".csv\"");
    echo "\xEF\xBB\xBF" . $this->getAsString();
    die;
  }
}