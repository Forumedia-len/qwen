<?php

namespace AC\core\engines;

use AC\app\entities\enums\AccountType;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\db\Query;
use AC\core\system\engine\InstallTableIfNotExist;
use AC\core\system\helpers\JsonHelper;
use RuntimeException;
use Service;
use Throwable;

useClass('engines\accounts');

class AccountsEngine extends \accounts
{
  protected string $tableItems = '';

  use InstallTableIfNotExist;

  public function __construct()
  {
    $this->initialize();
    $this->checkAndInstallTables();
  }

  public function checkAboAccountByTicketId($ticket_id)
  {
    $q    = 'select account_id from ' . Query::tableName('accounts') . ' where account_type = "1" and ticket_id = "' . $ticket_id . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      return true;
    }

    return false;
  }

  /**
   * Существуют ли счета нового типа для онлайн-оплаты.
   */
  public function hasOnlinePaymentInvoices(): bool
  {
    return (bool)Query::sqlQuery(
      'SELECT account_id FROM ' . $this->table . ' WHERE account_type = :account_type LIMIT 1',
      ['account_type' => AccountType::OnlinePayment->value],
      true,
      ['onlyOne' => true]
    );
  }

  /**
   * Создан ли онлайн-счёт хотя бы для одного из указанных бронирований.
   *
   * Проверяется сохранённый снимок счёта без JOIN к таблице reservations.
   *
   * @param int|list<int> $reservationIds Идентификаторы созданных бронирований.
   */
  public function hasOnlinePaymentInvoiceForReservations(int|array $reservationIds): bool
  {
    $reservationIds = array_values(array_unique(array_filter(
      array_map('intval', (array)$reservationIds),
      static fn(int $reservationId): bool => $reservationId > 0
    )));
    if ($reservationIds === []) {
      return false;
    }

    $placeholders = implode(',', array_fill(0, count($reservationIds), '?'));

    return (bool)Query::sqlQuery(
      'SELECT account.account_id
        FROM ' . $this->table . ' account
        INNER JOIN ' . $this->table_reservations . ' account_reservation
          ON account_reservation.account_id = account.account_id
        WHERE account.account_type = ?
          AND account_reservation.sys_reservation_id IN (' . $placeholders . ')
        LIMIT 1',
      [AccountType::OnlinePayment->value, ...$reservationIds],
      true,
      ['onlyOne' => true]
    );
  }

  /**
   * Разрешено ли создать новый онлайн-счёт для указанных бронирований.
   *
   * @param int|list<int> $reservationIds Идентификаторы созданных бронирований.
   */
  public function canCreateOnlinePaymentInvoice(int|array $reservationIds): bool
  {
    return config('account')->useOnlinePaymentInvoice()
      && !$this->hasOnlinePaymentInvoiceForReservations($reservationIds);
  }

  /**
   * Создать счет для успешно оплаченных онлайн-бронирований.
   *
   * Ошибка создания счета не должна изменять результат уже проведенной оплаты.
   *
   * @param int|list<int> $reservationIds Идентификаторы созданных бронирований.
   * @param string $paymentReference Reference платёжной операции.
   * @param string $transactionId Номер подтверждённой транзакции.
   * @param string|null $paymentType Способ оплаты Payone.
   * @param string|null $paymentProfileTypeKey Ключ профиля оплаты на момент операции.
   * @param array{name?: string, surname?: string, address?: string, post_code?: string, city?: string, email?: string, number?: string} $payerData
   *   Данные плательщика, которых нет в бронировании гостя.
   */
  public function createOnlinePaymentInvoiceForReservations(
    int|array $reservationIds,
    string $paymentReference,
    string $transactionId,
    ?string $paymentType = null,
    ?string $paymentProfileTypeKey = null,
    array $payerData = [],
  ): ?int
  {
    $reservationIds = array_values(array_unique(array_filter(
      array_map('intval', (array)$reservationIds),
      static fn(int $reservationId): bool => $reservationId > 0
    )));
    if ($reservationIds === [] || !$this->canCreateOnlinePaymentInvoice($reservationIds)) {
      return null;
    }

    $paymentReference      = trim($paymentReference);
    $transactionId         = trim($transactionId);
    $paymentType           = trim((string)$paymentType);
    $paymentProfileTypeKey = trim((string)$paymentProfileTypeKey);
    if ($paymentReference === '' || $transactionId === '') {
      Service::logger('accounts')->logError(
        'Online payment invoice transaction data is incomplete.',
        [
          'reservation_ids'         => $reservationIds,
          'has_payment_reference'   => $paymentReference !== '',
          'has_transaction_id'      => $transactionId !== '',
        ]
      );

      return null;
    }

    // Формат совместим с данными онлайн-пополнения личного счёта.
    $priceInfo = 'paypal|' . $paymentReference . '|' . $transactionId;
    if ($paymentType !== '') {
      $priceInfo .= '|' . $paymentType;
    }
    $priceInfo .= OnlineGatewayService::encodePrepaymentPriceInfoTypeKeyTail($paymentProfileTypeKey);

    $transactionStarted = false;
    try {
      Query::beginTransaction();
      $transactionStarted = true;

      $placeholders = implode(',', array_fill(0, count($reservationIds), '?'));
      $reservations = Query::sqlQuery(
        'SELECT
          r.*,
          area.title AS area_title,
          area_type.title AS type_title,
          area_sport.title AS sport_title
        FROM ' . Query::tableName('reservations') . ' r
        INNER JOIN ' . Query::tableName('areas') . ' area ON area.area_id = r.area_id
        INNER JOIN ' . Query::tableName('areas_types') . ' area_type ON area_type.type_id = area.type_id
        INNER JOIN ' . Query::tableName('areas_sports') . ' area_sport ON area_sport.sport_id = area.sport_id
        WHERE r.reservation_id IN (' . $placeholders . ')
        ORDER BY r.start
        FOR UPDATE',
        $reservationIds
      );
      if (!is_array($reservations) || count($reservations) !== count($reservationIds)) {
        throw new RuntimeException('Not all reservations exist.');
      }

      // На открытых кортах стоимость всей группы хранится в основном бронировании.
      $invoiceReservations = array_values(array_filter(
        $reservations,
        static fn(array $reservation): bool => empty($reservation['main_reservation_id'])
          && (int)$reservation['encash'] === 3
          && (float)$reservation['price'] > 0
      ));
      if ($invoiceReservations === []) {
        throw new RuntimeException('Online payment invoice has no paid main reservation with a positive price.');
      }

      // В счёте остаётся одна строка группы, но её время охватывает все связанные периоды.
      $periodRanges = [];
      foreach ($reservations as $reservation) {
        $mainReservationId = (int)($reservation['main_reservation_id'] ?: $reservation['reservation_id']);
        if (!isset($periodRanges[$mainReservationId])) {
          $periodRanges[$mainReservationId] = [
            'start'  => $reservation['start'],
            'finish' => $reservation['finish'],
          ];
          continue;
        }
        $periodRanges[$mainReservationId]['start'] = min(
          $periodRanges[$mainReservationId]['start'],
          $reservation['start']
        );
        $periodRanges[$mainReservationId]['finish'] = max(
          $periodRanges[$mainReservationId]['finish'],
          $reservation['finish']
        );
      }
      foreach ($invoiceReservations as &$invoiceReservation) {
        $mainReservationId = (int)$invoiceReservation['reservation_id'];
        if (isset($periodRanges[$mainReservationId])) {
          $invoiceReservation['start']  = $periodRanges[$mainReservationId]['start'];
          $invoiceReservation['finish'] = $periodRanges[$mainReservationId]['finish'];
        }
      }
      unset($invoiceReservation);

      // Блокировка бронирований сериализует повторные callback одного платежа.
      if ($this->hasOnlinePaymentInvoiceForReservations($reservationIds)) {
        Query::commit();

        return null;
      }

      $clientIds = array_values(array_unique(array_map(
        static fn(array $reservation): int => (int)($reservation['client_id'] ?? 0),
        $invoiceReservations
      )));
      if (count($clientIds) !== 1) {
        throw new RuntimeException('Online payment invoice reservations must belong to one customer.');
      }

      $client = [];
      if ($clientIds[0] > 0) {
        if (!Service::engines()->clients->getClientData($clientIds[0], $client, true)) {
          throw new RuntimeException('Online payment invoice client was not found.');
        }
      } else {
        $guestNames = array_values(array_unique(array_map(
          static fn(array $reservation): string => trim((string)(($reservation['client_name'] ?? '') ?: ($payerData['name'] ?? '')))
            . "\0" . trim((string)(($reservation['client_surname'] ?? '') ?: ($payerData['surname'] ?? ''))),
          $invoiceReservations
        )));
        if (count($guestNames) !== 1) {
          throw new RuntimeException('Online payment invoice reservations must belong to one guest.');
        }

        $name    = trim((string)(($invoiceReservations[0]['client_name'] ?? '') ?: ($payerData['name'] ?? '')));
        $surname = trim((string)(($invoiceReservations[0]['client_surname'] ?? '') ?: ($payerData['surname'] ?? '')));
        if ($name === '' || $surname === '') {
          throw new RuntimeException('Online payment invoice guest name is incomplete.');
        }
        $client = [
          'client_id' => null,
          'name'      => $name,
          'surname'   => $surname,
          'address'   => trim((string)($payerData['address'] ?? '')),
          'post_code' => trim((string)($payerData['post_code'] ?? '')),
          'city'      => trim((string)($payerData['city'] ?? '')),
          'email'     => trim((string)($payerData['email'] ?? '')),
          'number'    => trim((string)($payerData['number'] ?? '')),
          'nds_rate'  => $this->getDefaultNdsRate(),
        ];
      }

      $accountClientId = $client['client_id'] === null
        ? $this->insertOnlinePaymentGuestClient($client)
        : $this->insertAccountClient(
          $client['client_id'],
          $client['name'],
          $client['surname'],
          $client['address'],
          $client['post_code'],
          $client['city'],
          $client['email'],
          $client['number'],
          [
            $client['bank_iban'] ?? '',
            $client['bank_bic'] ?? '',
            $client['bank_sepa_referenz'] ?? '',
            $client['bank_sepa_mandat'] ?? '',
            $client['sepa_type'] ?? '',
            $client['sepa_standart'] ?? '',
          ],
          [
            $client['account_owner'] ?? '',
            $client['account_number'] ?? '',
            $client['bank_index'] ?? '',
            $client['bank_name'] ?? '',
          ]
        );
      if (!$accountClientId) {
        throw new RuntimeException('Online payment invoice client snapshot was not created.');
      }

      $accountNumber = $this->generateAccountNumber(AccountType::OnlinePayment->value);
      if ($accountNumber === false) {
        throw new RuntimeException('Online payment invoice number was not generated.');
      }

      $dateFinish = max(array_column($invoiceReservations, 'finish'));
      $accountInserted = Query::sqlQuery(
        'INSERT INTO ' . $this->table . ' SET
          client_id = :client_id,
          account_type = :account_type,
          number = :number,
          date_start = NOW(),
          date_finish = :date_finish,
          date_creation = NOW(),
          text_config = 0,
          price_info = :price_info,
          nds = :nds,
          area_id = :area_id',
        [
          'client_id'    => $accountClientId,
          'account_type' => AccountType::OnlinePayment->value,
          'number'       => $accountNumber,
          'date_finish'  => $dateFinish,
          'price_info'   => $priceInfo,
          'nds'          => $client['nds_rate'] ?? 0,
          'area_id'      => $invoiceReservations[0]['area_id'],
        ],
        false
      );
      $accountId = Query::getLastId();
      if (!$accountInserted || !$accountId) {
        throw new RuntimeException('Online payment invoice was not created.');
      }

      $insertReservationSql = 'INSERT INTO ' . $this->table_reservations . ' SET
        account_id = :account_id,
        type = :type,
        area = :area,
        date = :date,
        time_start = :time_start,
        time_finish = :time_finish,
        price = :price,
        light_price = :light_price,
        heating_price = :heating_price,
        net_price = :net_price,
        sys_reservation_id = :sys_reservation_id';
      foreach ($invoiceReservations as $reservation) {
        $lineInserted = Query::sqlQuery(
          $insertReservationSql,
          [
            'account_id'         => $accountId,
            'type'               => $reservation['type_title'] . ' - ' . $reservation['sport_title'],
            'area'               => $reservation['area_title'],
            'date'               => date('Y-m-d', strtotime($reservation['start'])),
            'time_start'         => date('H:i:s', strtotime($reservation['start'])),
            'time_finish'        => date('H:i:s', strtotime($reservation['finish'])),
            'price'              => $reservation['price'],
            'light_price'        => $reservation['light_state'] ? $reservation['light_price'] : 0,
            'heating_price'      => $reservation['heating_state'] ? $reservation['heating_price'] : 0,
            'net_price'          => $reservation['net_state'] ? $reservation['net_price'] : 0,
            'sys_reservation_id' => $reservation['reservation_id'],
          ],
          false
        );
        if (!$lineInserted) {
          throw new RuntimeException('Online payment invoice reservation snapshot was not created.');
        }
      }

      Query::commit();

      return $accountId;
    } catch (Throwable $exception) {
      if ($transactionStarted) {
        try {
          Query::rollBack();
        } catch (Throwable) {
        }
      }
      Service::logger('accounts')->logError(
        'Online payment invoice creation failed.',
        [
          'reservation_ids' => $reservationIds,
          'exception'       => get_class($exception),
          'message'         => $exception->getMessage(),
        ]
      );

      return null;
    }
  }

  /**
   * Сохранить независимый снимок гостя для онлайн-счёта.
   *
   * @param array{name: string, surname: string, address: string, post_code: string, city: string, email: string, number: string} $client
   */
  private function insertOnlinePaymentGuestClient(array $client): int|false
  {
    $inserted = Query::sqlQuery(
      'INSERT INTO ' . $this->table_clients . ' SET
        sys_client_id = NULL,
        name = :name,
        surname = :surname,
        address = :address,
        post_code = :post_code,
        city = :city,
        email = :email,
        number = :number,
        sepa_iban = "",
        sepa_bic = "",
        sepa_mndtid = "",
        sepa_dtofsgntr = NULL,
        account_owner = "",
        account_number = "",
        bank_index = "",
        bank_name = ""',
      [
        'name'      => $client['name'],
        'surname'   => $client['surname'],
        'address'   => $client['address'],
        'post_code' => $client['post_code'],
        'city'      => $client['city'],
        'email'     => $client['email'],
        'number'    => $client['number'],
      ],
      false
    );

    return $inserted ? (int)Query::getLastId() : false;
  }

  /**
   * Получить ставку НДС по умолчанию для гостевого онлайн-счёта.
   */
  private function getDefaultNdsRate(): int
  {
    $defaultNdsRate = Service::engines()->nds->getDefaultNdsRate();
    if ($defaultNdsRate === false) {
      throw new RuntimeException('Default VAT rate was not found.');
    }

    return $defaultNdsRate;
  }

  public function updatePriceInfoAndCountGameInAllAboAccounts()
  {
    $need_update = false;
    // изменяем столбцы на longtext
    $q    = 'SHOW COLUMNS FROM ' . Query::tableName('accounts');
    $temp = Query::sqlQuery($q);
    foreach ($temp as $row) {
      if (($row['Field'] == 'price_info' || $row['Field'] == 'count_game') && $row['Type'] == 'text') {
        $need_update = true;
        $q           = 'ALTER TABLE ' . Query::tableName('accounts') . ' CHANGE `' . $row['Field'] . '` `' . $row['Field'] . '` LONGTEXT NULL;';
        Query::sqlQuery($q, [], false);
      }
    }
    if ($need_update) { // если нужны изменения то делаем
      $t = new TicketsEngine();
      if ($accounts = $this->getAboAccounts()) {
        foreach ($accounts as $account_id => $account) {
          $t->getPriceInfoAndCountGame($account['ticket_id'], $price_info, $count_game);
          $q     = 'update ' . Query::tableName('accounts') . ' set price_info= :price_info,count_game= :count_game where account_id= :account_id';
          $param = ['price_info' => serialize($price_info), 'count_game' => serialize($count_game), 'account_id' => $account_id];
          Query::sqlQuery($q, $param, false);
        }
      }
    }
  }

  function getAccountTextFieldsById($account_id)
  {
    $q = 'SELECT text_fields FROM ' . $this->table . ' WHERE account_id = "' . $account_id . '"';
    if ($item = Query::sqlQuery($q, [], true, ['onlyOne' => true])) {
      return JsonHelper::decode($item['text_fields'], true) ? : [];
    }

    return false;
  }

  function changeAccountTextFields($account_id, $textFields)
  {
    foreach ($textFields as $key => $value) {
      if (empty($value)) {
        unset($textFields[$key]);
      }
    }
    if (Query::sqlQuery(
      'UPDATE ' . $this->table . ' SET text_fields = "' . addslashes(empty($textFields) ? ''
        : JsonHelper::encode($textFields)) . '" WHERE account_id = "' . $account_id . '"'
    )) {
      return true;
    }

    return false;
  }

  function getDataAccountTextFields($alias = null, $key = null)
  {
    $nameText = [
      'checkout_sign' => [
        'title'  => lang('checkout_sign', 'accounts_view'),
        'editor' => 'text',
      ],
    ];

    return $alias ? ($key ? $nameText[$alias][$key] : $nameText[$alias]) : $nameText;
  }

  public function fullRemoveAccount($accountId)
  {
    $this->saveDeleteAccount($accountId);
    if (Query::sqlQuery('DELETE FROM ' . $this->table . ' WHERE account_id = :account_id',
      ['account_id' => $accountId], false)) {
      $this->removeItemsAccount($accountId);
    }
  }

  protected function removeItemsAccount($accountId)
  {
  }

}
