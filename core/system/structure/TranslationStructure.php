<?php

namespace AC\core\system\structure;

/**
 * TODO используем частный случай для кортов
 */
class TranslationStructure extends Structure
{
  /**
   *  Должны быть обязательно заполнены ключи
   *  и их дефолтные значения
   * @var array
   */
  private array $requiredKeys = [
    'image'                     => null,              // изображение
    'name'                      => '',                // название
    'section'                   => 'messages',        // секция в которую будет сохранено в файле перевода
    'group'                     => 'common',          // группа при выводе для удобства
    'device'                    => ['site', 'touch'], // тип устройства на котором будет отображаться
    'courtTypeNotUse'           => [],                // типы кортов на которых не будет использоваться
    'courtTypeUse'              => false,             // использовать для типов кортов или это общие
    'courtTypeNotUseGroup'      => 'common',          // использовать для типов кортов или это общие
    'key'                       => null,              // ключ для перевода
    'showAccordingToConditions' => true,              // показывать или нет
    'variables'                 => [],                // предаваемы варианты переменных
  ];
  
  public function getTreeByGroup()
  {
    $out = [];
    foreach ($this->getTree() as $item) {
      $image = $item['image'];
      $item = $this->setDefaultValues($item);
      foreach ($item['device'] as $device) {
        if ($item['courtTypeUse']) {
          foreach (module('areas')->useModel()?->getEngine()->selectActiveType('current_alias') as $courtType => $courtTypeItem) {
            if (!in_array($courtType, $item['courtTypeNotUse'])) {
              $item['image'] = $this->getUrlImage($device, $courtTypeItem->alias, $image);
              $out[$device][$courtType][$item['group']][$item['key']] = $item;
            }
          }
        } else {
          $item['image'] = $this->getUrlImage($device, $item['courtTypeNotUseGroup'], $image);
          $out[$device][$item['courtTypeNotUseGroup']][$item['group']][$item['key']] = $item;
        }
      }
    }
    
    return $out;
  }
  
  protected function setDefaultValues($item)
  {
    foreach ($this->requiredKeys as $key => $value) {
      if (!isset($item[$key])) {
        $item[$key] = $value;
      }
    }
    
    return $item;
  }
  
  protected function getUrlImage($device, $courtType, $imageName)
  {
    $urlImage = cdn_url(paths()?->getAssetsDir('images/messages/' . $device. '/' . $courtType . '/' . $imageName));
//    Debug($device, $courtType, $imageName, $urlImage);
  // todo сделать проверку существования на сервере cdn файлов
    return  $urlImage;
//    return strpos(get_headers($urlImage)[0], '200 OK') ? $urlImage : null;
  }
  
}