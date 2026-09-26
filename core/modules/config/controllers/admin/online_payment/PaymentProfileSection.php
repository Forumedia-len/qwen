<?php

namespace AC\core\modules\config\controllers\admin\online_payment;

use AC\core\engines\ConfigEngine;
use AC\core\modules\config\controllers\admin\ConfigOnlinePaymentController;
use AC\core\modules\config\helpers\BindingConfigTypeHelper;
use AC\core\modules\config\models\ConfigModel;
use AC\core\modules\payment\models\ProfileContextModel;
use AC\core\system\helpers\StringHelper;
use Service;

/**
 * Общая CRUD-секция для провайдерных профилей онлайн-оплаты (PayPal/Payone).
 *
 * Хранилище: таблица `config` с типами вида `{prefix}` и `{prefix}|...`.
 */
abstract readonly class PaymentProfileSection
{
  protected string $structureKey;

  public function __construct(
    protected ConfigOnlinePaymentController $host,
  ) {
    $this->structureKey = 'config_online_payment_tab_gateway';
  }

  public function dispatch(): mixed
  {
    $action = trim((string)Service::request()->_('action', ''));

    return match ($action) {
      'create' => $this->update(true),
      'update' => $this->update(),
      'active' => $this->active(),
      'remove' => $this->remove(),
      default  => array_values(array_filter(
        [$this->showList(), $this->form()],
        static fn(?string $s): bool => $s !== null
      )),
    };
  }

  public function showList(): ?string
  {
    $ctx = $this->contextModel();
    if (($rows = $ctx::listProfileRows()) !== []) {
      return $this->host->render($this->listTemplate(), [
        'contextRows' => $rows,
        'gatewayHref' => Service::structure()->getPageHrefByKey($this->structureKey),
      ]);
    }

    return null;
  }

  public function update($new = false): mixed
  {
    if (!Service::request()->isPost()) {
      return [$this->form(!$new ? $this->profileContextForEdit() : null), $this->backButton()];
    }

    $post = Service::request()->_($this->postRootKey());
    if (!is_array($post)) {
      return $this->host->redirectDefaultAction();
    }

    $ctx        = $this->contextModel();
    $fields     = $ctx::profileFields();
    $typeKeyRaw = rawurldecode(trim((string)($post['configTypeKey'] ?? '')));
    if (!$ctx::isValidContextType($typeKeyRaw)) {
      return $this->host->redirectDefaultAction();
    }

    $prev = ConfigModel::getByType($typeKeyRaw);
    $normalized = $this->normalizeProfilePost($post, $fields);
    if ($normalized === []) {
      return $this->host->redirectDefaultAction();
    }
    foreach ($fields as $rk => $meta) {
      if (empty($meta['required'])) {
        continue;
      }
      $nv = (string)($normalized[$rk] ?? '');
      if ($nv !== '') {
        continue;
      }

      $masked = $meta['masked'] ?? null;
      if (is_array($masked) && is_object($prev)) {
        $old = isset($prev->$rk) ? (string)$prev->$rk : '';
        if ($old !== '') {
          continue;
        }
      }

      return $this->host->redirectDefaultAction();
    }
    if (is_object($prev)) {
      $normalized = $this->applyMaskedFieldsForSave($normalized, (array)$prev, $fields);
    }

    /** @var ConfigEngine $engine */
    $engine = getEngine('config', false);
    foreach (array_keys($fields) as $k) {
      if (array_key_exists($k, $normalized)) {
        $engine->saveItem($k, $normalized[$k], $typeKeyRaw);
      }
    }
    $this->host->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');

    return $this->host->redirectDefaultAction();
  }

  public function remove(): mixed
  {
    $ctx = $this->contextModel();
    if (($config_type = $this->getConfigTypeKey())
      && $ctx::isValidContextType($config_type)) {
      $ctx::deleteContextType($config_type);
      $this->host->view->addMessage(lang('message_element_base_remove', 'message_success'), 'success');
    }

    return $this->host->redirectDefaultAction();
  }

  public function active(): mixed
  {
    $ctx = $this->contextModel();
    if (($config_type = $this->getConfigTypeKey())
      && $ctx::isValidContextType($config_type)) {
      $active = Service::request()->_('active');
      if ($active !== null) {
        $fields = $ctx::profileFields();
        $key    = $this->useToggleFieldKey();
        if (isset($fields[$key])) {
          getEngine('config', false)->saveItem($key, (int)(bool)$active, $config_type);
          $this->host->view->addMessage(lang('message_element_base_active', 'message_success'), 'success');
        }
      }
    }

    return $this->host->redirectDefaultAction();
  }

  protected function form(?array $row = []): ?string
  {
    $ctx = $this->contextModel();
    $prefix        = (string)$ctx::CORRELATION_PREFIX;
    $usedConfigTypes = $ctx::listAllConfigTypes();

    // Если глобального профиля ещё нет (type = "{prefix}") — даём создать только его.
    // Контекстные профили (type = "{prefix}|...") доступны только после создания глобального.
    if (empty($row) && !in_array($prefix, $usedConfigTypes, true)) {
      $all = BindingConfigTypeHelper::bindingOptionMap($prefix);
      $availableBindingTypes = isset($all[$prefix]) ? [$prefix => $all[$prefix]] : [$prefix => lang('All')];
    } else {
      $availableBindingTypes = BindingConfigTypeHelper::availableBindingConfigTypes(
        $usedConfigTypes,
        $prefix
      );
    }

    if (!empty($row) || $availableBindingTypes !== []) {
      return $this->host->render($this->formTemplate(), [
        'profileBindingOptions' => BindingConfigTypeHelper::listTypesSports($this->bindingSelectName(),
          $availableBindingTypes),
        'contextEdit'           => $row,
        'gatewayHref'           => Service::structure()->getPageHrefByKey($this->structureKey),
      ]);
    }

    return null;
  }

  protected function profileContextForEdit(): ?array
  {
    $ctx     = $this->contextModel();
    $typeKey = $this->getConfigTypeKey();

    return $ctx::profileRow($typeKey);
  }

  /**
   * @param array<string, mixed> $post
   * @param array<string, array> $fields
   *
   * @return array<string, string|int>
   */
  protected function normalizeProfilePost(array $post, array $fields): array
  {
    $out = [];
    foreach ($fields as $k => $meta) {
      $v = $post[$k] ?? null;
      // Чекбоксы с массивом значений (например methods[] = ['cc','pp']) сохраняем как строку.
      if (is_array($v) && ($meta['type'] ?? null) === 'string') {
        $v = implode(';', array_values(array_filter($v, static fn($x): bool => is_scalar($x) && trim((string)$x) !== '')));
      }
      $out[$k] = match ($meta['type']) {
        'bool'  => (int)(bool)$v,
        'int'   => (int)$v,
        default => $meta['trim'] ? trim((string)$v) : (string)$v,
      };
    }

    return $out;
  }

  /**
   * Если пользователь прислал замаскированное значение или то же самое — сохраняем старое.
   *
   * @param array<string, string|int> $new
   * @param array<string, mixed>      $oldRow (ConfigModel as array)
   * @param array<string, array>      $fields
   *
   * @return array<string, string|int>
   */
  protected function applyMaskedFieldsForSave(array $new, array $oldRow, array $fields): array
  {
    foreach ($fields as $field => $meta) {
      if (empty($meta['masked'])) {
        continue;
      }
      $old = isset($oldRow[$field]) ? (string)$oldRow[$field] : '';
      $nv  = (string)($new[$field] ?? '');

      $mask = $meta['masked'];
      if ($nv === '' || $nv === $old || $nv === StringHelper::mask($old, $mask[0], $mask[1])) {
        $new[$field] = $old;
      }
    }

    return $new;
  }

  protected function backButton(): string
  {
    return useLayout()->render('backButton',
      [
        'backButtonUrl' => Service::structure()->getPageHrefByKey($this->structureKey),
      ]);
  }

  public function getConfigTypeKey(): string
  {
    return rawurldecode(trim((string)Service::request()->_('config_type')));
  }

  protected function providerPrefix(): string
  {
    $ctx = $this->contextModel();

    return (string)$ctx::CORRELATION_PREFIX;
  }

  /** @return class-string<ProfileContextModel> */
  abstract protected function contextModel(): string;

  protected function useToggleFieldKey(): string
  {
    return $this->providerPrefix() . '_use';
  }

  protected function postRootKey(): string
  {
    return $this->providerPrefix() . '_profile';
  }

  protected function bindingSelectName(): string
  {
    return $this->postRootKey() . '[configTypeKey]';
  }

  protected function listTemplate(): string
  {
    return $this->providerPrefix() . '/_list';
  }

  protected function formTemplate(): string
  {
    return '/' . $this->providerPrefix() . '/_form';
  }
}

