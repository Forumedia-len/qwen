<?php

namespace AC\core\system\view;

use AC\core\system\helpers\StringHelper;
use Service;
use Throwable;

/** Страница HTTP-ошибки в основном шаблоне текущего устройства. */
class HttpErrorPage
{
  /** Отобразить только публичное сообщение, без данных исключения и повторного запуска действия. */
  public function render(int $statusCode, ?string $message = null): string
  {
    $title = $statusCode . ' — ' . lang(match ($statusCode) {
      404 => 'page_not_found',
      401, 403 => 'disallowed_action',
      default => 'request_failed',
    }, 'http_error');
    $message ??= lang($statusCode >= 500 ? 'try_again_later' : 'check_address_or_return', 'http_error');
    $title = StringHelper::shield($title);
    $content = '<section class="http-error"><h1>' . $title . '</h1><p>'
      . StringHelper::shield($message) . '</p></section>';
    $bufferLevel = ob_get_level();
    $device = '';
    try {
      $device = Service::url()->getTemplatePath();
      $params = $this->getLayoutParams($device, $title, $content);
      $html = $this->renderLayout(paths()->getTplDir($params['_page']['template'], $device), $params);
      if (is_string($html) && trim($html) !== '') {
        return $html;
      }
    } catch (Throwable $error) {
      // При сбое БД или самого шаблона ошибка должна оставаться доступной без рекурсивного рендеринга.
    } finally {
      while (ob_get_level() > $bufferLevel) {
        ob_end_clean();
      }
    }
    $robots = (defined('NOINDEX_SITE') && NOINDEX_SITE) || $device === 'widget'
      ? 'noindex, indexifembedded' : 'noindex';
    return '<!doctype html><html lang="' . StringHelper::shield(config('lang')->getCurrentLang())
      . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
      . '<meta name="robots" content="' . $robots . '"><title>' . $title . '</title></head><body>' . $content . '</body></html>';
  }

  /** Подключить основной шаблон без служебного $file в переменных вложенных представлений. */
  protected function renderLayout(string $template, array $params): string
  {
    $path = Service::templates()->resolvePath($template);
    if (!$path) {
      return '';
    }
    ob_start();
    (static function (array $layoutParams): void {
      extract($layoutParams, EXTR_SKIP);
      require func_get_arg(1);
    })($params, $path);
    return ob_get_clean();
  }

  /** Подготовить навигацию из конфигурации устройства, не перечитывая отклонённый запрос. */
  protected function getLayoutParams(string $device, string $title, string $content): array
  {
    $structure = Service::structure();
    $defaultPage = $structure->getDefaultPageData() ?? [];
    $page = [
      'key' => $defaultPage['key'] ?? 'error',
      'parent_key' => $defaultPage['parent_key'] ?? null,
      'title' => $title,
      'h1' => $title,
      'useH1' => false,
      'http_error' => true,
      'content' => [1 => $content],
      'content_menu' => [],
      'head' => ['<meta name="robots" content="noindex">'],
      'js' => ['tooltip'],
      'css' => ['tooltip'],
      'template' => 'default',
    ];
    $params = ['structure' => $structure];
    if ($device === 'admin') {
      $page['template'] = Service::auth()->checkAuth() ? 'internal' : 'auth';
      $page['parents'] = [1 => ['key' => $page['key'], 'title' => $title]];
    } elseif ($device === 'touch') {
      $typeId = (int)Service::session()->get('type_id');
      $params['type_template'] = config('reservations')->isOpenType($typeId) ? 'open' : 'close';
      $page['template'] = 'template/' . $params['type_template'] . '/default';
      $params['menu'] = [];
      foreach (['login' => 'authorization', 'reservations' => 'dailySchedule', 'registration' => 'newRegistration'] as $path => $key) {
        $params['menu'][] = [
          'url' => site_url($path . '.php'),
          'title' => lang($key, 'menu'),
          'class' => '',
          'image' => base_url(paths()->getAssetsDir('images/menu/' . match ($path) {
            'login' => 'login', 'reservations' => 'rasp', default => 'new_reg',
          } . '.svg')),
        ];
      }
    } elseif ($device === 'site' && !config('cookieApply')->checkCookiesApplied()) {
      $page['js'][] = 'cookie';
      $page['css'][] = 'cookie';
    }
    $params['_page'] = $page;
    return $params;
  }
}
