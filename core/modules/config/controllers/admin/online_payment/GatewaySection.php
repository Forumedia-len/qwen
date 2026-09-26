<?php

namespace AC\core\modules\config\controllers\admin\online_payment;

use AC\core\modules\config\controllers\admin\ConfigOnlinePaymentController;
use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\services\OnlineGatewayService;
use Service;

final readonly class GatewaySection
{
  public function __construct(
    private ConfigOnlinePaymentController $host,
  ) {
  }

  public function show(): string
  {
    return $this->host->render('_online_gateway_form', [
      'current'          => OnlineGatewayService::primaryGateway(),
      'gateways'         => OnlineGatewayService::availableGateways(),
      'onlinePaymentUse' => OnlineGatewayService::onlinePaymentUse(),
      'baseHref'         => Service::structure()->getPageHrefByKey('config_online_payment_tab_gateway'),
    ]);
  }

  public function save(): mixed
  {
    $paymentPost = Service::request()->_('payment');
    if (is_array($paymentPost)) {
      $use = array_key_exists('online_payment_use', $paymentPost) ? (int)(bool)$paymentPost['online_payment_use'] : 0;
      getEngine('config', false)->saveItem('online_payment_use', $use, 'payment');

      if (array_key_exists('online_gateway_primary', $paymentPost)) {
        $g = strtolower(trim((string)$paymentPost['online_gateway_primary']));
        $gw = OnlineGatewayService::isSupportedGateway($g)
          ? $g
          : OnlineGateway::PAYPAL->value;
        getEngine('config', false)->saveItem('online_gateway_primary', $gw, 'payment');
      }
    }
    $this->host->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');

    return $this->host->redirectDefaultAction();
  }

  public function isAvailableAction(?string $action = null): bool
  {
    return !$action || in_array($action, array_keys($this->availableActions()), true);
  }

  public function getActionName(?string $action = null): ?string
  {
    return $this->availableActions()[$action] ?? null;
  }

  private function availableActions(): array
  {
    return [
      'saveGateway' => 'save',
    ];
  }
}