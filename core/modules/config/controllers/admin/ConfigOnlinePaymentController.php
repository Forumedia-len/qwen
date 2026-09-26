<?php

namespace AC\core\modules\config\controllers\admin;

use AC\core\modules\config\controllers\admin\online_payment\GuthabenSection;
use AC\core\modules\config\controllers\admin\online_payment\GatewaySection;
use AC\core\modules\config\controllers\admin\online_payment\PayoneSection;
use AC\core\modules\config\controllers\admin\online_payment\PaypalSection;
use AC\core\modules\config\controllers\ConfigControllerAdmin;
use AC\core\modules\config\models\ConfigPPModel;
use AC\core\modules\payment\services\OnlineGatewayService;
use Service;

class ConfigOnlinePaymentController extends ConfigControllerAdmin
{
  protected $base_model       = 'ConfigOnlinePaymentModel';
  public    $default_template = 'online_payment';
  public    $default_action   = 'show';
  protected $useBaseModel     = false;
  /**
   * @var ConfigPPModel
   */
  public $model;

  /**
   * Вкладки Guthaben и gateway (PayPal/Payone): соответствующие action обрабатываются в секциях.
   */
  public function start()
  {
    if ($action = $this->gatewayFormSection()->getActionName(Service::request()->_('action'))) {
      return $this->gatewayFormSection()->$action();
    }

    if (!OnlineGatewayService::onlinePaymentUse()) {
      return parent::start();
    }

    return match ($this->currentTab()) {
      'guthaben' => $this->guthabenSection()->dispatch(),
      'gateway'  => $this->dispatchGatewayProviderBlock(),
      default    => parent::start(),
    };
  }

  public function show()
  {
    return $this->gatewayFormSection()->show();
  }

  public function redirectDefaultAction($url = null)
  {
    $url    = $url ?? $this->getDefaultUrl();
    $params = array_merge(
      (array)Service::request()->load(['mode', 'component', 'module']),
      ['tab' => $this->currentTab()]
    );
    if ($this->model && $this->model->isErrors()) {
      $this->view->addMessages($this->model->getErrors(), 'error');
    }
    if ($this->view->issetMessages()) {
      $this->view->saveMessageInSession();
    }
    $this->redirect($url, $params);
  }


  private function dispatchGatewayProviderBlock(): mixed
  {
    $providerContent = match (OnlineGatewayService::primaryGateway()) {
      'payone' => $this->payoneSection()->dispatch(),
      'paypal' => $this->paypalSection()->dispatch(),
    };
    if (!$this->gatewayFormSection()->isAvailableAction(Service::request()->_('action'))) {
      return $providerContent;
    }

    return [
      $this->gatewayFormSection()->show(),
      ...$providerContent,
    ];
  }


  private function gatewayFormSection(): GatewaySection
  {
    return new GatewaySection($this);
  }

  private function paypalSection(): PaypalSection
  {
    return new PaypalSection($this);
  }

  private function payoneSection(): PayoneSection
  {
    return new PayoneSection($this);
  }

  private function guthabenSection(): GuthabenSection
  {
    return new GuthabenSection($this);
  }

  /**
   * Вкладки: gateway и Guthaben — view->content_menu → menu/_content_menu.php.
   */
  protected function setView($key = null)
  {
    parent::setView($key);
    $this->view->content_menu = [0 => $this->buildOnlinePaymentContentMenuItems()];
  }

  /**
   * @return array<string, array{href:string,title:string,active:bool}>
   */
  protected function buildOnlinePaymentContentMenuItems(): array
  {
    $active = $this->currentTab();
    $s      = Service::structure();

    $gateway = [
      'href'   => $s->getPageHrefByKey('config_online_payment_tab_gateway'),
      'title'  => lang('tab_gateway', 'config_online_payment'),
      'active' => $active === 'gateway',
    ];
    if (!OnlineGatewayService::onlinePaymentUse()) {
      return ['gateway' => $gateway];
    }

    return [
      'gateway'  => $gateway,
      'guthaben' => [
        'href'   => $s->getPageHrefByKey('config_online_payment_tab_guthaben'),
        'title'  => lang('tab_guthaben', 'config_online_payment'),
        'active' => $active === 'guthaben',
      ],
    ];
  }

  private function currentTab(): string
  {
    if (!OnlineGatewayService::onlinePaymentUse()) {
      return 'gateway';
    }
    $t = trim((string)Service::request()->_('tab', ''));

    return in_array($t, ['gateway', 'guthaben'], true) ? $t : 'gateway';
  }
}
