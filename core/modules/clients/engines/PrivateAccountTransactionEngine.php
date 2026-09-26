<?php

namespace AC\core\modules\clients\engines;

use AC\core\engines\ClientsEngine;
use AC\core\modules\clients\entities\dto\PrivateAccountTransactionDto;
use AC\core\modules\clients\models\PrivateAccountTransactionModel;
use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\UniqHelper;
use PDO;
use Service;
use Throwable;

/**
 * Класс для управления транзакциями личных счетов клиентов.
 * Реализует добавление, списание, пополнение и получение транзакций
 * по клиенту.
 * Todo: Реализовать возможность изменять статус транзакции через external_id для API
 */
class PrivateAccountTransactionEngine extends BaseEngine
{
  /**
   * Массив допустимых статусов транзакций.
   *
   * @var array[string]
   */
  private array $allowedStatuses = ['succeeded', 'cancelled', 'pending', 'failed', 'reversed'];

  /**
   * Конструктор.
   * Инициализирует модель транзакций.
   */
  public function __construct()
  {
    parent::__construct(new PrivateAccountTransactionModel());
  }

  /**
   * Добавить универсальную транзакцию.
   *
   * @param PrivateAccountTransactionDto $transactionDto DTO-объект с данными транзакции
   * @param array                        $errors
   *
   * @return int ID вставленной транзакции
   */
  public function addTransaction(PrivateAccountTransactionDto $transactionDto, array &$errors = []): int
  {
    $transactionDto->id         = null;
    $transactionDto->created_at = date('Y-m-d H:i:s');
    $transactionDto->status     = 'pending';

    /** @var PrivateAccountTransactionModel $transaction */
    if ($transaction = $this->setModelByDto($transactionDto, $errors)) {
      return $this->insert($transaction);
    }

    return 0;
  }

  /**
   * Пополнение счета клиента.
   * Устанавливает направление транзакции как "in".
   *
   * @param PrivateAccountTransactionDto $transactionDto DTO-объект с данными транзакции
   * @param array                        $errors
   *
   * @return int ID вставленной транзакции
   */
  public function deposit(PrivateAccountTransactionDto $transactionDto, array &$errors = []): int
  {
    if (($transactionId = $this->addDeposit($transactionDto, $errors))
      && $this->succeeded($transactionId, $errors)) {
      return $transactionId;
    }

    return 0;
  }

  /**
   * Установление транзакции на пополнение счета клиента.
   * Устанавливает направление транзакции как "in".
   *
   * @param PrivateAccountTransactionDto $transactionDto DTO-объект с данными транзакции
   * @param array                        $errors
   *
   * @return int ID вставленной транзакции
   */
  public function addDeposit(PrivateAccountTransactionDto $transactionDto, array &$errors = []): int
  {
    $transactionDto->type_direction = 'in';

    if ($transactionId = $this->addTransaction($transactionDto, $errors)) {
      return $transactionId;
    }

    return 0;
  }

  /**
   * Списание средств со счета клиента.
   * Устанавливает направление транзакции как "out".
   *
   * @param PrivateAccountTransactionDto $transactionDto DTO-объект с данными транзакции
   * @param array                        $errors
   *
   * @return int ID вставленной транзакции
   */
  public function withdraw(PrivateAccountTransactionDto $transactionDto, array &$errors = []): int
  {
    if (($transactionId = $this->addWithdraw($transactionDto, $errors))
      && $this->succeeded($transactionId, $errors)) {
      return $transactionId;
    }

    return 0;
  }

  /**
   * Установление транзакции на списание средств со счета клиента.
   * Устанавливает направление транзакции как "out".
   *
   * @param PrivateAccountTransactionDto $transactionDto DTO-объект с данными транзакции
   * @param array                        $errors
   *
   * @return int ID вставленной транзакции
   */
  public function addWithdraw(PrivateAccountTransactionDto $transactionDto, array &$errors = []): int
  {
    $transactionDto->type_direction = 'out';

    if ($transactionId = $this->addTransaction($transactionDto, $errors)) {
      return $transactionId;
    }

    return 0;
  }


  /**
   * Получить транзакции по клиенту.
   * Поддерживает фильтрацию по типу (все, только пополнения, только списания).
   *
   * @param int|null    $clientId      ID клиента
   * @param string|null $typeDirection Тип транзакции ('in', 'out') или null для всех
   * @param ?string     $status
   * @param string|null $dateStart
   * @param string|null $dateFinish
   *
   * @return array Массив транзакций с ключами 'all', 'in', 'out'
   */
  public function getTransactionsByClient(
    ?int $clientId,
    ?string $typeDirection = 'all',
    ?string $status = null,
    ?string $dateStart = null,
    ?string $dateFinish = null,
  ): array {
    $out        = ['all' => [], 'in' => [], 'out' => [], 'first' => $this->getFirstTransactionDate()];
    $conditions = [];
    if (!empty($clientId)) {
      $conditions['client_id'] = $clientId;
    }
    if (in_array($typeDirection, ['in', 'out'])) {
      $conditions['type_direction'] = $typeDirection;
    }
    if (in_array($status, $this->allowedStatuses, true)) {
      $conditions['status'] = $status;
    }
    $dateStart  = $dateStart ? date('Y-m-d H:i:s', strtotime($dateStart)) : null;
    $dateFinish = $dateFinish ? date('Y-m-d H:i:s', strtotime($dateFinish)) : null;
    if ($dateStart && $dateFinish) {
      $conditions[] = 'created_at BETWEEN "' . $dateStart . '" AND "' . $dateFinish . '"';
    } elseif ($dateStart && !$dateFinish) {
      $conditions[] = 'created_at >= "' . $dateStart . '"';
    } elseif (!$dateStart && $dateFinish) {
      $conditions[] = 'created_at <= "' . $dateFinish . '"';
    }

    $rows = $this->all($conditions, ['created_at DESC'],
      [
        'style' => PDO::FETCH_ASSOC,
      ]);
    foreach ($rows as &$row) {
      $row                         = $this->getPrivateAccountTransactionDtoByRow($row);
      $out[$row->type_direction][] = $row;
      $out['all'][]                = $row;
    }

    return $typeDirection && isset($out[$typeDirection]) ? $out[$typeDirection] : $out;
  }

  public function getTransaction(array $params = []): array
  {
    $where       = [];
    $whereParams = [];
    foreach (['client_id', 'type_direction', 'type_code', 'related_id', 'status'] as $item) {
      if (!empty($params[$item])) {
        $where[]       = $item . '=?';
        $whereParams[] = $params[$item];
      }
    }
    $query = 'SELECT * FROM ' . Query::tableName($this->tableName()) . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "");

    return Query::sqlQuery($query, $whereParams, true, ['onlyOne' => true]);
  }

  /**
   * @param array $row
   *
   * @return PrivateAccountTransactionDto
   */
  protected function getPrivateAccountTransactionDtoByRow(array $row): PrivateAccountTransactionDto
  {
    return PrivateAccountTransactionDto::fromArray($row);
  }

  /**
   * Изменить связанные данные транзакции.
   *
   * @param int   $transactionId
   * @param array $data
   *
   * @return bool
   */
  public function changeRelated(int $transactionId, array $data = []): bool
  {
    if ($transactionDto = $this->getTransactionById($transactionId)) {
      if (isset($data['related_id'])) {
        $transactionDto->related_id = $data['related_id'];
      }
      if (isset($data['related_data'])) {
        $transactionDto->related_data = array_merge($transactionDto->related_data, $data['related_data']);
      }

      if ($transaction = $this->setModelByDto($transactionDto)) {
        $this->update($transaction);
      }
    }

    return true;
  }

  /**
   * Получить модель транзакции по DTO.
   *
   * @param PrivateAccountTransactionDto $transactionDto
   * @param array                        $errors
   *
   * @return PrivateAccountTransactionModel|null
   */
  private function setModelByDto(PrivateAccountTransactionDto $transactionDto, array &$errors = []): ?PrivateAccountTransactionModel
  {
    if (!in_array($transactionDto->type_direction, ['in', 'out'])) {
      $errors[] = lang('Invalid transaction direction', 'add_transaction_error');

      return null;
    }
    if (!$transactionDto->client_id || !getEngine('clients')?->isClient($transactionDto->client_id)) {
      $errors[] = lang('The client was not found', 'add_transaction_error');

      return null;
    }

    if (abs($transactionDto->amount) < 0.001) {
      $errors[] = lang('The amount must be greater than zero', 'add_transaction_error');

      return null;
    }

    /** @var PrivateAccountTransactionModel $transaction */
    $transaction                 = $this->getModel();
    $transaction->id             = $transactionDto->id ?? null;
    $transaction->client_id      = $transactionDto->client_id;
    $transaction->type_direction = $transactionDto->type_direction;
    $transaction->type_code      = $transactionDto->type_code ?? 'unknown_' . ($transactionDto->type_direction === 'in' ? 'deposit' : 'withdraw');
    $transaction->amount         = round(abs($transactionDto->amount), 2);
    $transaction->created_at     = $transactionDto->created_at ?? date('Y-m-d H:i:s');
    $transaction->created_user   = $transactionDto->created_user ?? Service::auth()->getTypeUserAndId();
    $transaction->related_data   = $transactionDto->related_data ? addslashes(JsonHelper::encode($transactionDto->related_data,
      JSON_UNESCAPED_UNICODE)) : null;
    $transaction->related_id     = $transactionDto->related_id ?? null;
    $transaction->status         = $transactionDto->status ?? 'pending';
    $transaction->external_id    = $transactionDto->external_id ?? UniqHelper::generateUuid();

    if ($transaction->validate()) {
      return $transaction;
    }
    $errors = $transaction->getErrors();

    return null;
  }

  public function getTransactionById(int $id): ?PrivateAccountTransactionDto
  {
    return $this->getPrivateAccountTransactionDtoByRow($this->one($id, ['style' => PDO::FETCH_ASSOC,]));
  }

  /**
   * Завершить транзакцию.
   *
   * @param int   $transactionId
   * @param array $errors
   *
   * @return bool
   */
  public function succeeded(int $transactionId, array &$errors = []): bool
  {
    return $this->updateTransactionStatus($transactionId, 'succeeded', $errors);
  }

  /**
   * Отменить транзакцию.
   *
   * @param int   $transactionId ID транзакции
   * @param array $errors        Массив для ошибок
   *
   * @return bool Успешность операции
   */
  public function cancel(int $transactionId, array &$errors = []): bool
  {
    return $this->updateTransactionStatus($transactionId, 'cancelled', $errors);
  }

  /**
   * Обозначить транзакцию как неуспешную.
   *
   * @param int   $transactionId ID транзакции
   * @param array $errors        Массив для ошибок
   *
   * @return bool Успешность операции
   */
  public function failed(int $transactionId, array &$errors = []): bool
  {
    return $this->updateTransactionStatus($transactionId, 'failed', $errors);
  }

  /**
   * Обратная транзакция (например, откат).
   *
   * @param int   $transactionId ID транзакции
   * @param array $errors        Массив для ошибок
   *
   * @return bool Успешность операции
   */
  public function reversed(int $transactionId, array &$errors = []): bool
  {
    if ($this->updateTransactionStatus($transactionId, 'reversed', $errors) && ($transactionDto = $this->getTransactionById($transactionId))) {
      $external_id                    = $transactionDto->external_id;
      $transactionDto->type_direction = $transactionDto->type_direction === 'in' ? 'out' : 'in';
      $transactionDto->status         = 'pending';
      $transactionDto->external_id    = UniqHelper::generateUuid();
      $transactionDto->related_data   = array_merge($transactionDto->related_data,
        ['original_id' => $transactionId, 'original_external_id' => $external_id]);
      if (($id = $this->addTransaction($transactionDto, $errors)) && $this->succeeded($id, $errors)) {
        $this->changeRelated($transactionId, ['related_data' => ['reversed_id' => $id, 'reserved_external_id' => $transactionDto->external_id]]);

        return true;
      }
    }

    return false;
  }

  /**
   * Изменить статус транзакции.
   *
   * @param int    $transactionId ID транзакции
   * @param string $newStatus     Новый статус ('succeeded', 'cancelled', 'pending', 'failed', 'reversed')
   * @param array  $errors        Массив для ошибок
   *
   * @return bool Успешность операции
   */
  private function updateTransactionStatus(int $transactionId, string $newStatus, array &$errors = []): bool
  {
    if (!in_array($newStatus, $this->getAllowedStatuses())) {
      $errors[] = lang('Invalid transaction status', 'update_status_error');

      return false;
    }

    if (!$currentTransaction = $this->getTransactionById($transactionId)) {
      $errors[] = lang('Transaction not found', 'update_status_error');

      return false;
    }

    if (!$this->checkOrderStatuses($currentTransaction->status, $newStatus)) {
      $errors[] = lang('Incorrect status change operation', 'update_status_error');

      return false;
    }

    if ($newStatus === 'succeeded') {
      return $this->succeedTransaction($transactionId, $errors);
    }

    return Query::sqlQuery('update ' . Query::tableName('private_account_transactions') . ' set status = :status where id = :id',
      [':status' => $newStatus, ':id' => $transactionId], false);
  }

  /**
   * Завершить транзакцию и сохранить баланс клиента до и после операции.
   *
   * @param int   $transactionId
   * @param array $errors
   *
   * @return bool
   */
  private function succeedTransaction(int $transactionId, array &$errors = []): bool
  {
    $ownsTransaction = !Query::inTransaction();
    $savepoint        = 'private_account_succeeded_' . $transactionId;

    try {
      if ($ownsTransaction) {
        Query::beginTransaction();
      } else {
        Query::sqlQuery('SAVEPOINT ' . $savepoint, [], false);
      }

      $transactionRow = Query::sqlQuery(
        'select * from ' . Query::tableName('private_account_transactions') . ' where id = :id for update',
        [':id' => $transactionId],
        true,
        ['onlyOne' => true]
      );
      if (!$transactionRow) {
        $errors[] = lang('Transaction not found', 'update_status_error');

        return $this->finishSucceededTransaction($ownsTransaction, $savepoint, false);
      }

      $transactionDto = $this->getPrivateAccountTransactionDtoByRow($transactionRow);
      if (!$this->checkOrderStatuses($transactionDto->status, 'succeeded')) {
        $errors[] = lang('Incorrect status change operation', 'update_status_error');

        return $this->finishSucceededTransaction($ownsTransaction, $savepoint, false);
      }

      $clientRow = Query::sqlQuery(
        'select prepayment_sum from ' . Query::tableName('clients') . ' where client_id = :client_id for update',
        [':client_id' => $transactionDto->client_id],
        true,
        ['onlyOne' => true]
      );
      if (!$clientRow) {
        $errors[] = lang('The client was not found', 'change_balance_error');

        return $this->finishSucceededTransaction($ownsTransaction, $savepoint, false);
      }

      $balanceBefore = round((float)$clientRow['prepayment_sum'], 2);
      if (!$this->changeBalance($transactionDto, $errors)) {
        return $this->finishSucceededTransaction($ownsTransaction, $savepoint, false);
      }

      $clientRow = Query::sqlQuery(
        'select prepayment_sum from ' . Query::tableName('clients') . ' where client_id = :client_id',
        [':client_id' => $transactionDto->client_id],
        true,
        ['onlyOne' => true]
      );
      if (!$clientRow) {
        $errors[] = lang('database_transaction_error', 'database_errors');

        return $this->finishSucceededTransaction($ownsTransaction, $savepoint, false);
      }

      $transactionDto->related_data = array_merge($transactionDto->related_data ?? [], [
        'balance_before' => $balanceBefore,
        'balance_after'  => round((float)$clientRow['prepayment_sum'], 2),
      ]);
      $updated = Query::sqlQuery(
        'update ' . Query::tableName('private_account_transactions') . '
          set status = :status, related_data = :related_data
          where id = :id',
        [
          ':status'       => 'succeeded',
          ':related_data' => JsonHelper::encode($transactionDto->related_data, JSON_UNESCAPED_UNICODE),
          ':id'           => $transactionId,
        ],
        false
      );
      if (!$updated) {
        $errors[] = lang('database_transaction_error', 'database_errors');

        return $this->finishSucceededTransaction($ownsTransaction, $savepoint, false);
      }

      return $this->finishSucceededTransaction($ownsTransaction, $savepoint, true);
    } catch (Throwable $exception) {
      $this->finishSucceededTransaction($ownsTransaction, $savepoint, false);
      Service::logger('private_account')->logError('Failed to complete private account transaction.', [
        'transaction_id' => $transactionId,
        'exception'      => $exception->getMessage(),
      ]);
      $errors[] = lang('database_transaction_error', 'database_errors');

      return false;
    }
  }

  /**
   * Завершить собственную транзакцию или savepoint вызывающего кода.
   *
   * @param bool   $ownsTransaction
   * @param string $savepoint
   * @param bool   $success
   *
   * @return bool
   */
  private function finishSucceededTransaction(bool $ownsTransaction, string $savepoint, bool $success): bool
  {
    if ($ownsTransaction) {
      $success ? Query::commit() : Query::rollBack();

      return $success;
    }

    Query::sqlQuery(($success ? 'RELEASE' : 'ROLLBACK TO') . ' SAVEPOINT ' . $savepoint, [], false);

    return $success;
  }

  /**
   * Проверить возможность изменения статуса.
   * stateDiagram-v2
   * [*] --> pending
   * pending --> succeeded: Успешное выполнение
   * pending --> cancelled: Отмена до завершения
   * pending --> failed: Ошибка
   * succeeded --> reversed: Возврат средств
   *
   * @param string $fromStatus
   * @param string $toStatus
   *
   * @return bool
   */
  protected function checkOrderStatuses(string $fromStatus, string $toStatus): bool
  {
    return match (true) {
      $fromStatus === 'pending' && in_array($toStatus, ['succeeded', 'cancelled', 'failed']),
        $fromStatus === 'succeeded' && $toStatus === 'reversed' => true,
      default                                                   => false,
    };
  }

  /**
   * Изменить баланс клиента.
   *
   * @param PrivateAccountTransactionDto $transactionsDto
   * @param array                        $errors
   *
   * @return bool
   */
  private function changeBalance(PrivateAccountTransactionDto $transactionsDto, array &$errors = []): bool
  {
    $error_code = 0;
    /** @var ClientsEngine $clientsEngine */
    $clientsEngine = getEngine('clients');

    $out = match ($transactionsDto->type_direction) {
      'in'  => $clientsEngine?->setClientPrepaymentSum($transactionsDto->client_id, $transactionsDto->amount, $error_code),
      'out' => $clientsEngine?->getClientPrepaymentSum($transactionsDto->client_id, $transactionsDto->amount, $error_code),
    };

    $errors = match ($error_code) {
      1       => [lang('The client was not found', 'change_balance_error')],
      2       => [lang('Problem with the deposit amount', 'change_balance_error')],
      3       => [lang('Database error', 'change_balance_error')],
      default => [],
    };

    return $out;
  }

  /**
   * Получить массив допустимых статусов транзакций.
   *
   * @return array|string[]
   */
  public function getAllowedStatuses(): array
  {
    return $this->allowedStatuses;
  }

  public function getFirstTransactionDate(): ?string
  {
    return Query::sqlQuery('select min(created_at) as min from ' . Query::tableName('private_account_transactions'), [], true,
      ['onlyOne' => true])['min'] ?? null;
  }
}
