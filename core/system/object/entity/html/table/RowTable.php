<?php

namespace AC\core\system\object\entity\html\table;

use AC\core\system\object\entity\html\TagHtml;

class RowTable extends TagHtml
{
  /**
   * @var TdField[]
   */
  public array $items = [];

  /**
   * @return string
   */
  public function getContent(): string
  {
    $out = [];
    foreach ($this->items as $item) {
      $out[] = $item->asString();
    }

    return implode("\n", $out);
  }

  /**
   * @param TdField $item
   * @return $this
   */
  public function addItem(TdField $item): RowTable
  {
    $this->items[] = $item;
    return $this;
  }

  /**
   * @return $this
   */
  public function generateItems($data = []): RowTable
  {
    return $this;
  }

}