<?php

namespace AC\core\system\object\entity\html;

class Button extends ElementHtml
{
  /**
   * @var string{'submit', 'button', 'image', 'reset'}
   */
  public string $type = 'submit';
  public string $value;
}