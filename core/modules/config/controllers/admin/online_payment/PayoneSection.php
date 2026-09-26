<?php

namespace AC\core\modules\config\controllers\admin\online_payment;

use AC\core\modules\config\models\ConfigModel;
use AC\core\modules\payment\payone\models\PayoneProfileContext;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\helpers\JsonHelper;
use Service;

final readonly class PayoneSection extends PaymentProfileSection
{

  /**
   * @return class-string<PayoneProfileContext>
   */
  protected function contextModel(): string
  {
    return PayoneProfileContext::class;
  }

  /**
   * Payone-профиль хранит доп. параметры методов в одном поле `payone_method_params` (JSON).
   *
   * @param array<string, mixed> $post
   * @param array<string, array> $fields
   *
   * @return array<string, string|int>
   */
  protected function normalizeProfilePost(array $post, array $fields): array
  {
    $out        = parent::normalizeProfilePost($post, $fields);
    $action     = trim((string)Service::request()->_('action', ''));
    $typeKeyRaw = rawurldecode(trim((string)($post['configTypeKey'] ?? '')));

    // Enabled methods
    $enabledRaw = $out['payone_methods'] ?? '';
    $enabled    = array_values(array_unique(array_map(
      static fn(string $m): string => strtolower(trim($m)),
      array_values(array_filter(array_map('trim', explode(';', (string)$enabledRaw))))
    )));

    // Previous JSON for update: empty means "keep old"
    $params = [];
    if ($action === 'update' && $typeKeyRaw !== '') {
      $prev = ConfigModel::getByType($typeKeyRaw);
      if (is_object($prev) && isset($prev->payone_method_params)) {
        $decoded = JsonHelper::decode((string)$prev->payone_method_params, true);
        $params  = is_array($decoded) ? $decoded : [];
      }
    }

    $catalog            = OnlineGatewayService::payoneConfig()->availableMethods();
    $postedMethodParams = $post['payone_method_params'] ?? null;

    // Merge posted params into JSON by method meta (no hardcoded bnpl).
    if (is_array($postedMethodParams)) {
      foreach ($enabled as $code) {
        if (!isset($catalog[$code]) || !is_array($catalog[$code])) {
          continue;
        }
        $meta  = $catalog[$code];
        $extra = $meta['extra_params'] ?? null;
        if (!is_array($extra) || $extra === []) {
          continue;
        }
        $pm = isset($postedMethodParams[$code]) && is_array($postedMethodParams[$code]) ? $postedMethodParams[$code] : [];
        foreach ($extra as $key => $rule) {
          if (!is_array($rule)) {
            continue;
          }
          $key = (string)$key;
          if ($key === '' || !array_key_exists($key, $pm)) {
            continue;
          }
          $raw = $pm[$key];
          if (!is_scalar($raw)) {
            // invalid type => treat as empty (and required validation will fail)
            $raw = '';
          }
          $nv = trim((string)$raw);
          if ($nv === '') {
            continue; // keep old
          }
          if (!isset($params[$code]) || !is_array($params[$code])) {
            $params[$code] = [];
          }
          $params[$code][$key] = $nv;
        }
      }
    }

    // Remove params for disabled methods (root key = method code)
    foreach (array_keys($catalog) as $methodCode) {
      $mc = strtolower((string)$methodCode);
      if (!in_array($mc, $enabled, true)) {
        unset($params[$mc]);
      }
    }

    // Validate requirements from meta (required extra_params)
    foreach ($enabled as $code) {
      $meta  = $catalog[$code] ?? null;
      $extra = is_array($meta) ? ($meta['extra_params'] ?? []) : [];
      if (!is_array($extra) || $extra === []) {
        continue;
      }
      $mp = $params[$code] ?? null;
      foreach ($extra as $key => $rule) {
        if (!is_array($rule) || empty($rule['required'])) {
          continue;
        }
        $key = (string)$key;
        $v   = (is_array($mp) && array_key_exists($key, $mp)) ? $mp[$key] : '';
        if (!is_scalar($v) || trim((string)$v) === '') {
          $this->host->view->addMessage(
            lang('Incomplete data has been submitted', 'message_error', ['error' => 'Payone method params: ' . (string)$code . '.' . (string)$key]),
            'error'
          );
          return [];
        }
      }
    }
    if ($params !== []) {
      $json = JsonHelper::encode($params);
      if ($json !== false && array_key_exists('payone_method_params', $fields)) {
        $out['payone_method_params'] = $json;
      }
    } else {
      unset($out['payone_method_params']);
    }

    return $out;
  }
//
//  protected function profileContextForEdit(): ?array
//  {
//    $ctx        = $this->contextModel();
//    $typeKey    = $this->getConfigTypeKey();
//    $profileRow = $ctx::profileRow($typeKey);
//    Debug($profileRow);
//    if (isset($profileRow['payone_method_params']) && !empty($profileRow['payone_method_params']['useValue'])) {
//      foreach ($profileRow['payone_method_params']['useValue'] as $methodName => $params) {
//        if($extraParams = $ctx::availableMethods()[$methodName]['extra_params'] ?? null) {
//          Debug($extraParams, $params);
//          foreach ($params as $filed => $value) {
//          }
//        }
//      }
//    }
//    return $profileRow;
//  }
}
