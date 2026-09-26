<?php

namespace AC\app\actions\device;

use AC\core\engines\Engines;
use AC\core\system\actions\DeviceActionInterface;
use AC\core\system\view\Page;
use Service;


class TouchDeviceAction implements DeviceActionInterface
{
  /**
   */
  public function before()
  {
    if (!TOUCHSCREEN) {
      return Service::redirect()->redirect(base_url());
    }
    $type_id = (int)Service::request()->_('type_id');
    if(!$type_id) {
      Service::engines()->areas->getAreasTypesData($areas_type_data);
      $type_id = (Service::session()->get('type_id') ? : current($areas_type_data)['type_id']);
    }

    if($type_id) {
      Service::session()->set('type_id', (int)$type_id);
    }

    $menu = [
      'login'        => [
        'image' => base_url(paths()->getAssetsDir('images/menu/login'. ($this->checkActiveUrl('login') ? '_act' : '') .'.svg')),
        'title' => lang('authorization', 'menu'),
        'url'   => site_url('login.php?type_id=' . $type_id),
        'class' => 'logi' . ($this->checkActiveUrl('login') ? ' active' : '')
      ],
      'reservation'  => [
        'image' => base_url(paths()->getAssetsDir('images/menu/rasp'. ($this->checkActiveUrl('reservations') ? '_act' : '') .'.svg')),
        'title' => lang('dailySchedule', 'menu'),
        'url'   => site_url('reservations.php?action=selectSport&type_id=' . $type_id),
        'class' => 'rasp' . ($this->checkActiveUrl('reservations') ? ' active' : '')
      ],
      'registration' => [
        'image' => base_url(paths()->getAssetsDir('images/menu/new_reg'. ($this->checkActiveUrl('registration') ? '_act' : '') .'.svg')),
        'title' => lang('newRegistration', 'menu'),
        'url'   => site_url('registration.php?type_id=' . $type_id),
        'class' => 'reg'. ($this->checkActiveUrl('registration') ? ' active' : '')
      ]
    ];

    $engine = new Engines();

    Service::session()->set('cur_section', 'touch');

    $type_template = config('reservations')->isOpenType((int)$type_id) ? 'open' : 'close';
    $page          = new Page();

    return get_defined_vars();
  }

  /**
   * @param $variable
   *
   */
  public function after($variable)
  {
    extract($variable, EXTR_OVERWRITE);
    if (!Service::request()->_('ajax', 0) && !preg_match('/ajax/i', Service::request()->_('action', '')) && isset($_page)) {
      if (empty($type_id)) {
//        header('Location: ' . site_url());
      }
      $structure  = Service::structure();
      $_page      = array_merge($structure->getPageDataByKey($_page['key']), $_page);
      $template   = isset($_page['template']) ? $_page['template'] : 'default';
      $priorities = [
        paths()->getTplDir('template/' . $type_template . '/' . $template),
        paths()->getTplDir($template)
      ];
      $path       = Service::locator()->findPriorityPathToFile($priorities);

      if ($path) {
        require_once $path;
      }
    }
  }

  public function checkAuthorization()
  {

    return true;
  }

  /**
   * Проверяем по структуре активный путь url c заданым для выбранного ключа
   *
   * @param string $pageKey
   *
   * @return bool
   */
  protected function checkActiveUrl($pageKey)
  {
    return Service::url()->getPath(false) == Service::structure()->getPageHrefByKey($pageKey);
  }
}