<?php

namespace AC\core\system\object\entity\html;

class TagHtml extends ElementHtml
{
  protected ?string $content;
  private ?string  $tag;

  public function __construct($name = null, $tag = 'p', $properties = [])
  {
    $this->tag = $tag;
    parent::__construct($name, $properties);
  }

  public function asString(): string
  {
    return $this->getContent() ? useLayout()->render('html\tag', ['tag' => $this], 'common') : '';
  }

  public function addContent($content): TagHtml
  {
    $this->content = $content;
    return $this;
  }

  public function getTag(): string
  {
    return $this->tag ?? $this->getName();
  }

  public function getContent(): string
  {
    return $this->content ?? '';
  }
}