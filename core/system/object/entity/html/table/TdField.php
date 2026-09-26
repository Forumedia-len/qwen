<?php

namespace AC\core\system\object\entity\html\table;

use AC\core\system\object\entity\html\TagHtml;

class TdField extends TagHtml
{
  public ?string $colspan;
  public ?string $rowspan;

  public function getAttributes(): array
  {
    return array_merge(parent::getAttributes(), ['colspan', 'rowspan']);
  }
}