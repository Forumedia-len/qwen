<?php

namespace AC\core\modules\reports\actions;

use AC\core\modules\reports\actions\reports\PaymentPayOnlineReport;
use AC\core\modules\reports\actions\reports\PrivateAccountClientsReport;
use AC\core\modules\reports\actions\reports\ReservationsForDayBarReport;
use AC\core\modules\reports\actions\reports\ReservationsForDayReport;
use AC\core\modules\reports\actions\reports\ReservationsForMonthReport;
use AC\core\modules\reports\actions\reports\ReservationsForPeriodReport;
use AC\core\modules\reports\actions\reports\ReservationsAsTableReport;
use AC\core\modules\reports\actions\reports\ReservationsExportReport;

class FormsReport
{
  public function showForms(): array
  {
    $forms = [
      (new ReservationsForDayReport())->form(),      // дневной отчет
      (new ReservationsForMonthReport())->form(),    // месячный отчет
      (new ReservationsForPeriodReport())->form(),   // отчет по периоду
      (new ReservationsAsTableReport())->form(),     // табличный отчет
      (new ReservationsExportReport())->form(),      // экспорт
      (new PrivateAccountClientsReport())->form(),   // отчет по личным счетам клиентов
      (new ReservationsForDayBarReport())->form(),   // отчет наличным платежам
      (new PaymentPayOnlineReport())->form(),        // отчет по оплате PayOne
    ];

    return $forms;
  }

  public function getData($report): array
  {
    return match ($report) {
      'reservations_for_day'     => (new ReservationsForDayReport())->getData(),
      'reservations_for_month'   => (new ReservationsForMonthReport())->getData(),
      'reservations_for_period'  => (new ReservationsForPeriodReport())->getData(),
      'reservations_as_table'    => (new ReservationsAsTableReport())->getData(),
      'reservations_export'      => (new ReservationsExportReport())->getData(),
      'private_account_clients'  => (new PrivateAccountClientsReport())->getData(),
      'payment_pay_online'       => (new PaymentPayOnlineReport())->getData(),
      'reservations_for_day_bar' => (new ReservationsForDayBarReport())->getData(),
    };
  }
}