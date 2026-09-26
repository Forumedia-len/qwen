<?php

namespace AC\core\system\object\entity\html;

class Img extends TagHtml
{

  public string $src;
  public string $alt = '';
  public function asString(): string
  {
    return useLayout()->render('html\img', ['tag' => $this], 'common');
  }

  public function getAttributes(): array
  {
    return array_merge(['src', 'alt'], parent::getAttributes());
  }
}