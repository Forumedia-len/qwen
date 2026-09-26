<?php

namespace AC\core\modules\payment\config\payment;

use AC\core\modules\payment\models\ProfileContextModel;

interface PaymentProfileInterface
{
  /**
   * Ини
   *
   * @param array|null $params
   *
   * @return self
   */
  public function initData(?array $params = []): self;

  public function getData(): array;

  /**
   * Описание редактируемых полей профиля + правила валидации/нормализации.
   *
   * @return array<string, array<string, mixed>>
   */
  public function profileFields(): array;

  /** Включён ли профиль (переключатель), без проверки реквизитов */
  public function useProfile(): bool;

  /** Есть ли все обязательные реквизиты */
  public function checkData(): bool;

  /** Профиль включён и реквизиты заполнены */
  public function isOperational(): bool;

  /**
   * Получить модель контекста профиля оплаты
   *
   * @return ProfileContextModel
   */
  public function profileContext(): ProfileContextModel;
}

