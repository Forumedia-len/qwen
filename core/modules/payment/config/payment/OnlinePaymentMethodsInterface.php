<?php

namespace AC\core\modules\payment\config\payment;

interface OnlinePaymentMethodsInterface
{
  /** @return list<string> */
  public function enabledMethods(): array;

  /** @return array<string, mixed> */
  public function methodParams(string $methodCode): array;

  /** @return array<string, mixed> */
  public function availableMethods(): array;

  public function renderMethods(string $context): string;
}

