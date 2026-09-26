<?php

namespace AC\app\helpers;

use AC\core\system\helpers\ObjectHelper;

class ButtonLinkLayoutHelper
{
  public static function getButton(
    $name = 'Link',
    $href = '',
    $style = null,
    $class = null,
    $id = null,
    $target = null,
    $otherProperties = null
  ): string {
    return useLayout()->render('link', [
      'field' => ObjectHelper::createObject([
        'href'            => $href,
        'style'           => $style,
        'id'              => $id,
        'class'           => $class,
        'target'          => $target,
        'value'           => $name,
        'otherProperties' => $otherProperties

      ], true)
    ], 'common');
  }

  public static function getRemoveButton($href = '', $name = null, $style = null, $class = null, $id = null): string
  {
    return self::getButton(
      $name ?? lang('button_remove'),
      $href,
      $style,
        $class ?? 'btnRemove',
      $id,
      null,
      'onclick="return ifConfirm()"');
  }

  public static function getEditButton($href = '', $name = null, $style = null, $class = null, $id = null): string
  {
    return self::getButton(
      $name ?? lang('button_update'),
      $href,
      $style,
        $class ?: 'btnEdit',
      $id);
  }

  public static function getViewButton($href = '', $name = null, $style = null, $class = null, $id = null): string
  {
    return self::getButton(
      $name ?? lang('View'),
      $href,
      $style,
        $class ?? 'btnView',
      $id,
      '_blank',
      'onclick="return ifConfirm()"');
  }
}