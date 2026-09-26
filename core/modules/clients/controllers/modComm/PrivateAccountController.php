<?php

namespace AC\core\modules\clients\controllers\modComm;

use AC\core\modules\clients\engines\PrivateAccountTransactionEngine;
use AC\core\modules\clients\entities\dto\PrivateAccountTransactionDto;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;
use Exception;
use Service;

class PrivateAccountController extends ModCommController
{
  /**
   * Конструктор - загружаем переводы модуля
   */
  public function __construct()
  {
    parent::__construct();
    Service::lang()->addFile('message', paths()->modulesDir . 'clients\\' . paths()->getLangDir());
    Service::lang()->addFile('exception', paths()->modulesDir . 'clients\\' . paths()->getLangDir());
    Service::lang()->addFile('private_account', paths()->modulesDir . 'clients\\' . paths()->getLangDir());
  }
  
  /**
   * Инициализация пользовательских методов
   */
  protected function initializeCustomMethods(): void
  {
    // Добавляем методы с их конфигурацией
//    $this->addMethod('transactions', [
//      'description'     => 'Получить все транзакции лицевого счета клиента',
//      'requires_auth'   => true,
//      'required_params' => ['clientId'],
//      'optional_params' => ['typeDirection']
//    ]);
//
//    $this->addMethod('deposit', [
//      'description'     => 'Пополнение лицевого счета',
//      'requires_auth'   => true,
//      'required_params' => ['clientId', 'typeCode', 'amount'],
//      'optional_params' => ['relatedId', 'commentArr']
//    ]);
//
//    $this->addMethod('withdraw', [
//      'description'     => 'Списание с лицевого счета',
//      'requires_auth'   => true,
//      'required_params' => ['clientId', 'typeCode', 'amount'],
//      'optional_params' => ['relatedId', 'commentArr']
//    ]);
//
//    $this->addMethod('listIn', [
//      'description'     => 'Получить только пополнения',
//      'requires_auth'   => true,
//      'required_params' => ['clientId'],
//      'optional_params' => []
//    ]);
//
//    $this->addMethod('listOut', [
//      'description'     => 'Получить только списания',
//      'requires_auth'   => true,
//      'required_params' => ['clientId'],
//      'optional_params' => []
//    ]);
    
    // Добавляем правила авторизации
    $this->addAuthorizationRule('transactions', ['admin', 'manager', 'user']);
    $this->addAuthorizationRule('deposit', ['admin', 'manager']);
    $this->addAuthorizationRule('withdraw', ['admin', 'manager']);
    $this->addAuthorizationRule('listIn', ['admin', 'manager', 'user']);
    $this->addAuthorizationRule('listOut', ['admin', 'manager', 'user']);
    
    // Добавляем правила валидации
    $this->addValidationRule('transactions', [
      'clientId'      => ['type' => 'integer', 'min' => 1, 'optional' => true],
      'typeDirection' => ['type' => 'string', 'in' => ['in', 'out'], 'optional' => true],
    ]);
    
    $this->addValidationRule('deposit', [
      'clientId'   => ['type' => 'integer', 'min' => 1, 'optional' => true],
      'typeCode'   => ['type' => 'string', 'min_length' => 1],
      'amount'     => ['type' => 'float', 'min' => 0.01],
      'relatedId'  => ['type' => 'integer', 'optional' => true],
      'commentArr' => ['type' => 'array', 'optional' => true],
    ]);
    
    $this->addValidationRule('withdraw', [
      'clientId'   => ['type' => 'integer', 'min' => 1],
      'typeCode'   => ['type' => 'string', 'min_length' => 1],
      'amount'     => ['type' => 'float', 'min' => 0.01],
      'relatedId'  => ['type' => 'integer', 'optional' => true],
      'commentArr' => ['type' => 'array', 'optional' => true],
    ]);
    
    $this->addValidationRule('listIn', [
      'clientId' => ['type' => 'integer', 'min' => 1],
    ]);
    
    $this->addValidationRule('listOut', [
      'clientId' => ['type' => 'integer', 'min' => 1],
    ]);
  }
  
  /**
   * Получить все транзакции лицевого счета клиента
   */
  public function transactions(ModCommRequest $request): ModCommResponse
  {
    $clientId      = $request->getDataValue('clientId');
    $typeDirection = $request->getDataValue('typeDirection', 'all');
    $status        = $request->getDataValue('status', 'succeeded');
    $dateStart     = $request->getDataValue('dateStart');
    $dateFinish    = $request->getDataValue('dateFinish');
    
    try {
      $transactions = $this->getEngine()?->getTransactionsByClient($clientId, $typeDirection, $status, $dateStart, $dateFinish);
      $this->addHistoryEvent('PrivateAccount:transaction', lang('transactions_retrieved_successfully', 'success_operations'), [
        'action'             => lang('action_transactions', 'action_parameters'),
        'client_id'          => $clientId,
        'type_direction'     => $typeDirection,
        'transactions_count' => count($transactions ?? []),
      ]);
      
      return ModCommHelper::success([
        'transactions'  => $transactions,
        'clientId'      => $clientId,
        'typeDirection' => $typeDirection,
      ], lang('transactions_retrieved_message', 'user_messages'));
    } catch (Exception $e) {
      
      $this->addErrorInHistory(lang('error_retrieving_transactions', 'operation_errors'),
        [
          'action'         => lang('action_transactions', 'action_parameters'),
          'client_id'      => $clientId,
          'type_direction' => $typeDirection,
        ], $e);
      
      return ModCommHelper::error(lang('error_retrieving_transactions', 'operation_errors') . ': ' . $e->getMessage(), 500);
    }
  }

  public function transaction(ModCommRequest $request): ModCommResponse
  {
    $transaction = $this->getEngine()?->getTransaction($request->getData());

    return ModCommHelper::success($transaction ?? [], lang('transactions_retrieved_message', 'user_messages'));
  }
  
  /**
   * Пополнение лицевого счета с завершением транзакции
   */
  public function deposit(ModCommRequest $request): ModCommResponse
  {
    return $this->addDeposit($request, true);
  }
  
  /**
   * Создать транзакцию на пополнение лицевого счета
   */
  public function addDeposit(ModCommRequest $request, $succeeded = false): ModCommResponse
  {
    return $this->sendTransaction($request, 'deposit', 'in', $succeeded);
  }
  
  /**
   * Списание с лицевого счета с завершением транзакции
   */
  public function withdraw(ModCommRequest $request): ModCommResponse
  {
    return $this->addWithdraw($request, true);
  }
  
  /**
   * Создать транзакцию на списание с лицевого счета
   */
  public function addWithdraw(ModCommRequest $request, $succeeded = false): ModCommResponse
  {
    return $this->sendTransaction($request, 'withdraw', 'out', $succeeded);
  }
  
  public function changeStatus(ModCommRequest $request): ModCommResponse
  {
    $errors = [];
    $status = $request->getDataValue('status');
    if ($this->getEngine()?->$status($request->getDataValue('transactionId'), $errors)) {
      
      if ($request->hasDataKey('relatedId') || $request->hasDataKey('relatedData')) {
        $this->getEngine()?->changeRelated($request->getDataValue('transactionId'), $request->getData());
      }
      $this->addHistoryEvent('PrivateAccount:changeStatus', lang('status_change_successfully', 'success_operations'), []);
      return ModCommHelper::success([], lang('status_change_message', 'user_messages', ['status' => $status]));
    }
    $this->addErrorInHistory(lang('error_status_change', 'operation_errors'), ['errors' => $errors]);
    
    return ModCommHelper::error(lang('error_status_change', 'operation_errors'), 500, ['errors' => $errors]);
    
  }
  
  protected function sendTransaction(ModCommRequest $request, $typeTransaction, $direction, $succeeded = true): ModCommResponse
  {
    $errors = [];
    $request->addDataValue('type_direction', $direction);
    $method = $succeeded ? $typeTransaction : 'add' . ucfirst($typeTransaction);
    try {
      if ($id = $this->getEngine()?->$method(PrivateAccountTransactionDto::forCreate($request->getData()), $errors)) {
        $this->addHistoryEvent('PrivateAccount:' . $typeTransaction, lang('account_' . $typeTransaction . '_successful', 'success_operations'), [
          'action'         => 'PersonalAccount:' . $typeTransaction,
          'transaction_id' => $id,
          'request'        => $request->getData(),
        ]);
        
        return ModCommHelper::success([
          'transaction_id' => $id,
          'request'        => $request->getData(),
        ], lang('account_' . $typeTransaction . '_message', 'user_messages', [], false, $request->getLocale()));
      }
      
      $this->addErrorInHistory(lang('error_processing_account_' . $typeTransaction, 'operation_errors'), ['errors' => $errors]);
      
      return ModCommHelper::error(lang('error_processing_account_' . $typeTransaction, 'operation_errors'), 500, ['errors' => $errors]);
      
    } catch (Exception $e) {
      $this->addErrorInHistory(lang('error_processing_account_' . $typeTransaction, 'operation_errors'), [
        'action'  => lang('action_' . $typeTransaction, 'action_parameters'),
        'request' => $request->getData(),
        'errors'  => $errors,
      ], $e);
      
      return ModCommHelper::error(lang('error_processing_account_' . $typeTransaction, 'operation_errors') . ': ' . $e->getMessage(), 500,
        ['errors' => $errors]);
    }
  }
  
  /**
   * Получить только пополнения
   */
  public function listIn(ModCommRequest $request): ModCommResponse
  {
    $clientId = $request->getDataValue('clientId');
    
    try {
      $transactions = $this->getEngine()?->getTransactionsByClient($clientId, 'in');
      $this->addHistoryEvent('PrivateAccount:listIn', lang('deposits_retrieved_successfully', 'success_operations'), [
        'action'             => lang('action_list_in', 'action_parameters'),
        'client_id'          => $clientId,
        'transactions_count' => count($transactions),
      ]);
      
      return ModCommHelper::success([
        'transactions' => $transactions,
        'clientId'     => $clientId,
        'type'         => 'in',
      ], lang('deposits_retrieved_message', 'user_messages'));
    } catch (Exception $e) {
      $this->addErrorInHistory(lang('error_retrieving_deposits', 'operation_errors'), [
        'action'    => lang('action_list_in', 'action_parameters'),
        'client_id' => $clientId,
      ], $e);
      
      return ModCommHelper::error(lang('error_retrieving_deposits', 'operation_errors') . ': ' . $e->getMessage(), 500);
    }
  }
  
  /**
   * Получить только списания
   */
  public function listOut(ModCommRequest $request): ModCommResponse
  {
    $clientId = $request->getDataValue('clientId');
    
    try {
      $transactions = $this->getEngine()?->getTransactionsByClient($clientId, 'out');
      $this->addHistoryEvent('PrivateAccount:listOut', lang('withdrawals_retrieved_successfully', 'success_operations'), [
        'action'             => lang('action_list_out', 'action_parameters'),
        'client_id'          => $clientId,
        'transactions_count' => count($transactions),
      ]);
      
      return ModCommHelper::success([
        'transactions' => $transactions,
        'clientId'     => $clientId,
        'type'         => 'out',
      ], lang('withdrawals_retrieved_message', 'user_messages'));
    } catch (Exception $e) {
      $this->addErrorInHistory(lang('error_retrieving_withdrawals', 'operation_errors'), [
        'action'    => lang('action_list_out', 'action_parameters'),
        'client_id' => $clientId,
      ], $e);
      
      return ModCommHelper::error(lang('error_retrieving_withdrawals', 'operation_errors') . ': ' . $e->getMessage(), 500);
    }
  }
  
  protected function getEngine(): ?PrivateAccountTransactionEngine
  {
    return getEngine('privateAccountTransaction');
  }
  
} 