<?php

namespace AC\core\modules\clients\controllers\admin;

use AC\app\controllers\AdminController;
use AC\app\entities\enums\Encash;
use AC\app\locators\Service;
use AC\core\modules\clients\entities\dto\ClientPrivateAccountDto;
use AC\core\modules\clients\entities\dto\PaymentRowDto;
use AC\core\modules\clients\entities\dto\PrivateAccountTransactionDto;
use AC\core\modules\clients\helpers\PaymentRowDtoHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

class ClientsPrivateAccountController extends AdminController
{
  public $default_template = 'private_account';
  
  public function show()
  {
    $transactionData = [];
    /** @var array $clientData */
    if (!($clientId = (int)Service::request()->_get('client_id')) || !getEngine('clients')?->getClientData($clientId, $clientData)) {
      return $this->renderError('Client not found');
    }
    if (($response = ModCommHelper::callSafe('clients', 'privateAccount/transactions',
        ['clientId' => $clientId, 'typeDirection' => 'full'])) && $response->isSuccess()) {
      $transactionData = $response->getData();
    }
    $sections = $this->buildSections($clientData, $clientId, $transactionData);
    
    return $this->render('show', ['sections' => $sections]);
  }
  
  /**
   * Строит структуру секций для отображения
   * @param array $clientData
   * @param int   $clientId
   * @param array $transactionData
   * @return array
   */
  private function buildSections(array $clientData, int $clientId, array $transactionData = []): array
  {
    
    return [
      'playable_amount'   => $this->buildPlayableAmountSection($clientData),
      'payment_access'    => $this->buildIncomingPaymentsSection($clientId, $transactionData['transactions']['in'], $transactionData['transactions']['first']),
      'outgoing_payments' => $this->buildOutgoingPaymentsSection($clientId, $transactionData['transactions']['out'], $transactionData['transactions']['first']),
    ];
  }
  
  /**
   * Секция "Доступная сумма"
   */
  private function buildPlayableAmountSection(array $clientData): ClientPrivateAccountDto
  {
    return new ClientPrivateAccountDto(
      title: lang('The playable amount', 'clients'),
      rows : [
        new PaymentRowDto(
          date   : '',
          label  : StringHelper::shield($clientData['name'] . ' ' . $clientData['surname']),
          amount : PaymentRowDtoHelper::amount($clientData['prepayment_sum']),
          icon   : '',
          comment: ''
        )
      ]
    );
  }
  
  /**
   * Секция "Входящие платежи"
   * @param int   $clientId
   * @param array $transactionData
   * @param null  $first
   * @return ClientPrivateAccountDto
   */
  private function buildIncomingPaymentsSection(int $clientId, array $transactionData = [], $first = null): ClientPrivateAccountDto
  {
    $section = new ClientPrivateAccountDto(
      title: lang('Payment access', 'clients'),
      rows : $this->transactionDataByPrivateAccountDto($transactionData)
    );
    $this->renderPaymentAccountData($section, $clientId, 'in', $first);
    usort($section->rows, static fn($a, $b) => strtotime($b->date) <=> strtotime($a->date));
    
    return $section;
  }
  
  /**
   * Секция "Исходящие платежи"
   * @param int   $clientId
   * @param array $transactionData
   * @param null  $first
   * @return ClientPrivateAccountDto
   */
  private function buildOutgoingPaymentsSection(int $clientId, array $transactionData = [], $first = null): ClientPrivateAccountDto
  {
    $section = new ClientPrivateAccountDto(
      title: lang('Outgoing payments', 'clients'),
      rows : $this->transactionDataByPrivateAccountDto($transactionData)
    );
    
    $reservationEngine = Service::engines();
    if ($reservationEngine->getReservationDataByClient($clientId, 2, $reservationData)) {
      foreach ($reservationData as $reservation) {
        if (Encash::from($reservation['encash']) === Encash::PrivateAccount) {
          $createdAt = $first ?? $transactionData[count($transactionData) - 1]->created_at;
          if (!empty($transactionData) && $createdAt < $reservation['ordered']) {
            continue;
          }
          $section->rows[] = new PaymentRowDto(
            date   : PaymentRowDtoHelper::date($reservation['ordered']),
            label  : lang('reservation_created_private_account', 'transaction_label'),
            amount : PaymentRowDtoHelper::amount($reservation['price']),
            icon   : '',
            comment: PaymentRowDtoHelper::getTitleArea($reservation['area_id']) . ' | ' . date('d.m.Y H:i',
              strtotime($reservation['start'])) . ' - ' . date('H:i', strtotime($reservation['finish']))
          );
        }
      }
    }
    $this->renderPaymentAccountData($section, $clientId, 'out');
    usort($section->rows, static fn($a, $b) => strtotime($b->date) <=> strtotime($a->date));
    
    return $section;
  }
  
  private function renderPaymentAccountData(ClientPrivateAccountDto $section, int $clientId, string $type_direction, $first = null): void
  {
    static $accounts;
    if (empty($accounts) && $items = getEngine('accounts', false)?->getPrepaymentAccountsByClientId($clientId, null, $first)) {
      foreach ($items as $item) {
        if(config('payment')->usePayonePayment()){
          $item['type_code'] = str_replace('paypal', 'payone', $item['type_code']);
        }
        $direction = 'in';
        if ( $item['sum'] < 0) {
          $direction = 'out';
        }
        if (abs($item['sum']) < 0.009) {
          continue;
        }
        $accounts[$direction][] = new PaymentRowDto(
          date   : PaymentRowDtoHelper::date($item['date_creation']),
          label  : PaymentRowDtoHelper::langLabel($item['type_code']),
          amount : PaymentRowDtoHelper::amount(abs($item['sum'])),
          icon   : $this->iconImage($item['type_code']),
          comment: StringHelper::shield(config('AccountView')->getNumberAccount($item['a_number'], PREPAYMENT_ACCOUNT_NUMBER))
        );
      }
    }
    $section->rows = array_merge($section->rows, $accounts[$type_direction] ?? []);
  }
  
  private function transactionDataByPrivateAccountDto(array $transactionData): array
  {
    $rows = [];
    /** @var PrivateAccountTransactionDto $transaction */
    foreach ($transactionData as $transaction) {
      if(config('payment')->usePayonePayment()){
        $transaction->type_code = str_replace('paypal', 'payone', $transaction->type_code);
      }
      $rows[] = new PaymentRowDto(
        date   : PaymentRowDtoHelper::date($transaction->created_at),
        label  : PaymentRowDtoHelper::label($transaction),
        amount : PaymentRowDtoHelper::amount($transaction->amount),
        icon   : $this->iconImage($transaction->type_code),
        comment: PaymentRowDtoHelper::getComment($transaction->type_code, $transaction->related_data)
      );
    }
    
    return $rows;
  }
  
  private function iconImage(string $alias): string
  {
    return match ($alias) {
      'paypal', 'paypal_deposit', 'paypal_deposit_removed' => useLayout()->render('pp', [], 'common'),
      'payone', 'payone_deposit', 'payone_deposit_removed' => useLayout()->render('po', [], 'common'),
      default                                              => '',
    };
  }
  
  /**
   * Вывод ошибки с редиректом
   */
  private function renderError(string $message): string
  {
    $this->view->addMessage($message, 'error');
    return $this->render('show', ['sections' => []]);
  }
}