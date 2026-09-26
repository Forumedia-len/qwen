<?php

namespace AC\app\actions\device;

use AC\core\system\actions\DeviceActionInterface;
use Service;
use const MC_ARENA;

/** Подготавливает запрос и общий шаблон публичного сайта. */
class SiteDeviceAction implements DeviceActionInterface
{
  /** Подключать ресурсы формы согласия только для интерфейсов, которые её выводят. */
  protected bool $showCookieConsent = true;

  /**
   * Проверяет параметры общей формы входа до начала вывода страницы.
   */
  public function before()
  {
    $request = Service::request();
    // Бронирование само проверяет эти параметры и сохраняет календарь при ошибке.
    if (!in_array(trim(Service::url()->getPath(false), '/'), ['reservations', 'reservations.php'], true)) {
      $request->validated('type_id', 'get', [['integer', ['min' => 0]]]);
      $request->validated('date', 'get', [['date', ['allowDottedFormat' => true, 'normalize' => true]]]);
    }
    $request->validated('tab', 'request', [['integer', ['min' => 0, 'max' => 1]]]);
    $request->validated('action', 'request', 'string');
    if (Service::session()->exists('type_id')) {
      Service::session()->delete('type_id');
    }
  }

  /**
   * Собирает страницу и выбирает оболочку текущего интерфейса.
   */
  public function after($variable)
  {
    extract($variable, EXTR_OVERWRITE);
    if (!preg_match('/ajax/i', Service::request()->_('action', '')) && isset($_page)) {
      //данные страницы
      $structure = Service::structure();
      $_page     = array_merge($structure->GetPageDataByKey($_page['key']), $_page);

      $_page['js'][]  = 'tooltip';
      $_page['css'][] = 'tooltip';
      if ($this->showCookieConsent && !config('cookieApply')->checkCookiesApplied()) {
        $_page['js'][] = 'cookie';
        $_page['css'][] = 'cookie';
      }

      //запрашиваем шаблон
      if ($template = Service::templates()->resolveTemplate($_page['template'] ?? 'default')) {
        require_once $template;
      }
    }
  }
}
