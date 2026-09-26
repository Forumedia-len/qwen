<?php

namespace AC\core\system\modules\modComm;

use AC\core\system\modules\modComm\exceptions\ModCommException;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

/**
 * Интерфейс модулей с возможностью общаться между собой.
 *
 * Этот интерфейс определяет базовый контракт для всех модулей,
 * которые поддерживают межмодульное взаимодействие через систему
 * запросов и ответов.
 *
 * @package AC\core\system\modules\modComm
 * @since   1.0.0
 */
interface ModCommModuleInterface
{
  /**
   * Обрабатывает запрос от другого модуля.
   *
   * Метод должен реализовать логику обработки входящего запроса
   * от другого модуля системы. Возвращает структурированный ответ,
   * содержащий результат обработки запроса.
   *
   * @param ModCommRequest $request Объект запроса с данными от другого модуля
   * @return ModCommResponse Объект ответа с результатом обработки запроса
   * @throws ModCommException При ошибке обработки запроса
   */
  public function handleModuleRequest(ModCommRequest $request): ModCommResponse;
}
