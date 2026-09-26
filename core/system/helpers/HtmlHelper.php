<?php

namespace AC\core\system\helpers;

use AC\core\system\object\entity\html\Form;
use AC\core\system\object\entity\html\Img;
use AC\core\system\object\entity\html\Table;
use AC\core\system\object\entity\html\table\RowTable;
use AC\core\system\object\entity\html\table\TdField;
use AC\core\system\object\entity\html\table\ThField;
use AC\core\system\object\entity\html\TagHtml;

class HtmlHelper
{

  /**
   * @param string $name
   * @param string|null $additionalName
   * @param string|null $tag
   * @param mixed ...$arguments
   * @return TagHtml|object
   */
  protected static function createTag(string $name = 'tagHtml', string $additionalName = null, string $tag = null, ...$arguments): object
  {
    $arguments = array_merge([array_shift($arguments), $tag], $arguments);

    return ObjectHelper::createEntity('html\\' . (str_contains($name, '\\') ? $name : ucfirst($name)), $additionalName, ...$arguments);
  }

  /**
   * @param $tagName
   * @param ?string $additionalName
   * @param mixed ...$arguments
   * @return TagHtml
   */
  public static function tag($tagName, string $additionalName = null, ...$arguments): TagHtml
  {
    return self::createTag('tagHtml', $additionalName, $tagName, ...$arguments);
  }

  /**
   * @param string $tag
   * @param ?string $additionalName
   * @param mixed ...$arguments
   * @return TdField
   */
  public static function tagTdTh(string $tag = 'td', ?string $additionalName = null, ...$arguments): TdField
  {
    return self::createTag('table\\' . ucfirst($tag) . 'Field', $additionalName, $tag, ...$arguments);
  }

  /**
   * @param ?string $additionalName
   * @param mixed ...$arguments
   * @return ThField
   */
  public static function tagTr( ?string $additionalName = null, ...$arguments): RowTable
  {
    return self::createTag('table\RowTable', $additionalName, 'tr', ...$arguments);
  }

  /**
   * @param string $additionalName
   * @param mixed ...$arguments
   * @return Table
   */
  public static function table($additionalName = null, ...$arguments): Table
  {
    return self::createTag('table', $additionalName, 'table', ...$arguments);
  }

  /**
   * @param string $additionalName
   * @param mixed ...$arguments
   * @return Form
   */
  public static function form($additionalName = null, ...$arguments): Form
  {
    return self::createTag('form', $additionalName, 'form', ...$arguments);
  }

  /**
   * @param string $additionalName
   * @param mixed ...$arguments
   * @return Form
   */
  public static function img(string $additionalName = null, ...$arguments): Img
  {
    return self::createTag('Img', $additionalName, 'img', ...$arguments);
  }
}