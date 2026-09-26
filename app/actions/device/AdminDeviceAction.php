<?php

namespace AC\app\actions\device;

use AC\core\modules\users\controllers\admin\UsersAuthController;
use AC\core\system\actions\DeviceActionInterface;

use Service;

class AdminDeviceAction implements DeviceActionInterface
{

  public function before()
  {
//    Debug()::log(json_encode(['url' => $_SERVER["REQUEST_URI"], 'post' => $_POST, 'get' => $_GET, 'session' => $_SESSION, 'cookie' => $_COOKIE, 'server' => $_SERVER]), 'request', 'message', '');
    defined('THEME') || define('THEME', 'at');

    header('Content-Type: text/html; charset=utf-8');
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-cache");
    header("Cache-Control: post-check=0, pre-check=0");
    header("Pragma: no-cache");

    uses('accountNumber');
    return $this->checkAuthorization();
  }

  public function after($variable)
  {
//    Debug($_SESSION);
    extract($variable, EXTR_OVERWRITE);
    $structure = Service::structure();
    if (isset($_page)) {
      $pageKey = $_page['key'] ?? null;
      $fromStructure = ($pageKey && $structure->isPageKey($pageKey))
        ? $structure->getPageDataByKey($pageKey)
        : [];
      $_page = array_merge($_page, is_array($fromStructure) ? $fromStructure : []);

      $parent = null;
      if (!empty($_page['parent_key']) && $structure->isPageKey($_page['parent_key'])) {
        $parentData = $structure->getPageDataByKey($_page['parent_key']);
        $parent     = is_array($parentData) ? $parentData : null;
      }
      /** @var  $auth  UsersAuthController */
      $auth = Service::auth();
      if (!$auth->checkRights(
          (isset($_page['access']) ? (int)$_page['access'] : ($parent && isset($parent['access']) ? (int)$parent['access'] : 1))
        ) && $_page['key'] != 'login') {
        unset($_page['content']);
        $_page['content'][] = '<p style="text-align:center; font-size:18px"><span class="error">' . lang('access_not_allowed', 'auth') . '</span></p>';
      }

      if ($_page['key'] != 'login' && $_page['key'] != 'offline_report' && $structure->isPageKey($_page['key'])) {
        $_page['parents'] = array_reverse($structure->getParentsTreeByPageKey($_page['key']));
        $_page['content'][] = '<div style="text-align: right">' . lang('current_date_and_time', null, ['date_time' => date('d.m.Y H:i:s')]) . '</div>';
      }

      //запрашиваем шаблон
      require_once Service::autoloader()->getPathFile(paths()->getTplDir($_page['template'] . '.php'));
    }
  }

  public function checkAuthorization()
  {
    $hrefRedirect = Service::structure()->getPageHrefByKey('login');
    if (!Service::auth()->checkAuth($hrefRedirect)
      && Service::url()->getPath(false) !== $hrefRedirect
      && !in_array(Service::url()->getPath(), config('auth')->urlNotCheckAuth)
    ) {

      return Service::redirect()->redirect(site_url($hrefRedirect))->send();
    }

    return true;
  }
}