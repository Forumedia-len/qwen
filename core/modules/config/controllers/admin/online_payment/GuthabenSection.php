<?php

namespace AC\core\modules\config\controllers\admin\online_payment;

use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\config\controllers\admin\ConfigOnlinePaymentController;
use AC\core\modules\config\models\ConfigPPModel;
use AC\core\system\helpers\ObjectHelper;
use Service;

final class GuthabenSection
{
  private string $structureKey = 'config_online_payment_tab_guthaben';
  public function __construct(
    private readonly ConfigOnlinePaymentController $host,
  ) {
  }

  /** Точка входа для всех запросов с tab=guthaben (маршрутизация по action). */
  public function dispatch(): mixed
  {
    $action = trim((string)Service::request()->_('action', 'show'));

    return match ($action) {
      'create' => $this->update(true),
      'update' => $this->update(),
      'remove' => $this->remove(),
      default => [$this->show(), $this->update(true)],
    };
  }

  public function show(): mixed
  {
    (new ConfigPPModel())->getListData($data);
    $models = ObjectHelper::createObject(array_keys($data));
    $models->loadParams($data);

    return $this->host->render(
      'guthaben/_list',
      [
        'models'       => $models,
        'active_type'  => AreasModel::selectActiveType(),
        'guthabenHref' => Service::structure()->getPageHrefByKey($this->structureKey),
      ]
    );
  }

  /** Создание / правка предложения Guthaben (config_pp). */
  public function update($new = false)
  {
    $ppId  = $new ? null : Service::request()->_('pp_id');
    $model = $new ? new ConfigPPModel() : new ConfigPPModel($ppId);
    if (!$new && $model->hasErrors()) {
      return $this->host->redirectDefaultAction();
    }

    $post = Service::request()->_post();
    if (!empty($post)) {
      if ($model->load($post)) {
        if ($model->save()) {
          $this->host->view->addMessage(
            lang('message_element_base_' . ($new ? 'create' : 'update'), 'message_success'),
            'success'
          );

          return $this->host->redirectDefaultAction();
        }
      }
      if ($model->isErrors()) {
        $this->host->view->addMessages($model->getErrors(), 'error');
      }
    }
    $out = $this->host->render('guthaben/_form', ['model' => $model]);

    if (Service::request()->_('action') !== null) {
      $out = [$out, $this->backButton()];
    }

    return $out;
  }

  /**
   * Удаление записи; для dispatch() возвращает пустую строку после редиректа (совместимость с runController).
   */
  public function remove(): string
  {
    $model = new ConfigPPModel(Service::request()->_('pp_id'));
    if (!$model->hasErrors() && $model->remove()) {
      $this->host->view->addMessage(lang('message_element_base_remove', 'message_success'), 'success');
    }

    return $this->host->redirectDefaultAction();
  }


  private function backButton(): string
  {
    return useLayout()->render('backButton',
      [
        'backButtonUrl' => Service::structure()->getPageHrefByKey($this->structureKey),
      ]);
  }
}
