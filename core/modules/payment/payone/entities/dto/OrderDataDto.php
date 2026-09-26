<?php

namespace AC\core\modules\payment\payone\entities\dto;

use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\RedisHelper;

class OrderDataDto
{
  public ?float $amount;
  public string $currency;

  public array $it = [];
  public array $id = [];
  public array $pr = [];
  public array $no = [];
  public array $de = [];
  public array $va = [];

  public string  $clearingtype = 'evl';
  public ?string $wallettype;
  public ?string $financingtype;
  public array   $add_paydata  = [];
  private int    $number       = 1;

  public static function fromArray(array $data): self
  {
    $dto = new self();
    $dto->setAmount($data['amount'] ?? 0);
    $dto->currency      = $data['currency'] ?? CURR_VALUTE_PP;
    $dto->it            = $data['it'] ?? [];
    $dto->id            = $data['id'] ?? [];
    $dto->pr            = $data['pr'] ?? [];
    $dto->no            = $data['no'] ?? [];
    $dto->de            = $data['de'] ?? [];
    $dto->va            = $data['va'] ?? [];
    $dto->clearingtype  = $data['clearingtype'] ?? 'evl';
    $dto->wallettype    = $data['wallettype'] ?? null;
    $dto->financingtype = $data['financingtype'] ?? null;
    $dto->add_paydata   = isset($data['add_paydata']) && is_array($data['add_paydata']) ? $data['add_paydata'] : [];

    return $dto;
  }

  public function toArray(): array
  {
    $array = [
      'amount'        => $this->amount,
      'currency'      => $this->currency,
      'clearingtype'  => $this->clearingtype,
      'wallettype'    => $this->wallettype,
      'financingtype' => $this->financingtype,
    ];

    foreach ($this->getOrderParams(false) as $param) {
      $this->getOrderParamAsData($param, $array);
    }

    if (!empty($this->add_paydata) && is_array($this->add_paydata)) {
      foreach ($this->add_paydata as $k => $v) {
        if (!is_scalar($k)) {
          continue;
        }
        $kk = trim((string)$k);
        if ($kk === '') {
          continue;
        }
        if (!is_scalar($v)) {
          continue;
        }
        $array["add_paydata[{$kk}]"] = (string)$v;
      }
    }

    return $array;
  }

  public function getOrderParamAsData(string $keyParam, &$data)
  {
    foreach ($this->$keyParam as $key => $value) {
      $data["{$keyParam}[{$key}]"] = $value;
    }
  }

  public function typePayment(): string
  {
    $ct = strtolower(trim((string)$this->clearingtype));
    if ($ct === 'cc') {
      return 'Card';
    }
    if ($ct === 'wlt') {
      $wt = strtoupper(trim((string)($this->wallettype ?? '')));
      // PAYONE: for Wero wallet `wallettype` is fixed to `WRO` (docs.payone.com).
      return in_array($wt, ['WRO', 'WERO'], true) ? 'Wero' : 'PayPal';
    }
    if ($ct === 'fnc' || trim((string)($this->financingtype ?? '')) !== '') {
      return 'BNPL';
    }

    return 'PayPal';
  }

  public function setItemOrderProperty(string $alias, ?string $value, ?int $number = null): bool
  {
    if (isset($this->$alias) && is_array($this->$alias)) {
      $number                  = $number ?? $this->number;
      $this->{$alias}[$number] = $value ?? "";

      return true;
    }

    return false;
  }

  public function addOrder(array $order = []): void
  {
    if (!empty($order)) {
      foreach ($this->getOrderParams(false) as $alias) {
        $this->setItemOrderProperty($alias, $order[$alias] ?? null);
      }
      ++$this->number;
    }
  }

  public function addOrders(array $orders): void
  {
    if (!empty($orders)) {
      foreach ($orders as $order) {
        $this->addOrder($order);
      }
    }
  }

  public function setAmount(float $amount): void
  {
    $this->amount = $amount * 100;
  }

  public function getAmountAsString(): float
  {
    return NumberHelper::float($this->amount / 100);
  }

  public function getOrderParams($required = true): array
  {
    $params = ['it', 'id', 'pr', 'no', 'de'];
    if (!$required) {
      $params[] = 'va';
    }

    return $params;
  }
}