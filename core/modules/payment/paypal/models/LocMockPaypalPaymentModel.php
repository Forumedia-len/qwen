<?php

namespace AC\core\modules\payment\paypal\models;

use AC\core\system\http\response\Response;
use Service;


class LocMockPaypalPaymentModel extends PaypalPaymentModel
{
  
  function hashCall($methodName, $nvpStr, &$errors = null)
  {
    $this->setNvpReqArray($this->getNvpReqString($methodName, $nvpStr));
    
    return match ($methodName) {
      'SetExpressCheckout'        => [
        'TOKEN'         => 'EC-1LY77998L6280581K',
        'TIMESTAMP'     => date('Y-m-d\TH:i:s\Z'),
        'CORRELATIONID' => '9629937799344',
        'ACK'           => 'Success',
        'VERSION'       => '53.0',
        'BUILD'         => '59055355'
      ],
      'GetExpressCheckoutDetails' => [
        'TOKEN'                          => 'EC-1LY77998L6280581K',
        'BILLINGAGREEMENTACCEPTEDSTATUS' => '0',
        'TIMESTAMP'                      => date('Y-m-d\TH:i:s\Z'),
        'CORRELATIONID'                  => '79d376efd9e5f',
        'ACK'                            => 'Success',
        'VERSION'                        => '53.0',
        'BUILD'                          => '59055355',
        'EMAIL'                          => 'pptest@forumedia.com',
        'PAYERID'                        => 'YH6TMF8WWZR5J',
        'PAYERSTATUS'                    => 'verified',
        'FIRSTNAME'                      => 'Oliver',
        'LASTNAME'                       => 'Schönle',
        'COUNTRYCODE'                    => 'DE',
        'ADDRESSSTATUS'                  => 'Confirmed',
        'CURRENCYCODE'                   => 'EUR',
        'AMT'                            => Service::session()->get('nvpReqArray')['AMT'] ?? '0.01',
        'ITEMAMT'                        => Service::session()->get('nvpReqArray')['AMT'] ?? '0.01',
        'SHIPPINGAMT'                    => '0.00',
        'HANDLINGAMT'                    => '0.00',
        'TAXAMT'                         => '0.00',
        'DESC'                           => 'Abspielbetrag 0,01 €|Tennis Platz 3 22.09.2025 21:00-22:00',
        'INSURANCEAMT'                   => '0.00',
        'SHIPDISCAMT'                    => '0.00'
      ],
      'DoExpressCheckoutPayment'  => [
        'TOKEN'           => 'EC-1LY77998L6280581K',
        'TIMESTAMP'       => date('Y-m-d\TH:i:s\Z'),
        'CORRELATIONID'   => '4bf3555a4f49f',
        'ACK'             => 'Success',
        'VERSION'         => '53.0',
        'BUILD'           => '59055355',
        'TRANSACTIONID'   => '24M39692GN110141W',
        'TRANSACTIONTYPE' => 'expresscheckout',
        'PAYMENTTYPE'     => 'instant',
        'ORDERTIME'       => '2025-09-22T16:01:50Z',
        'AMT'             => Service::session()->get('nvpReqArray')['AMT'] ?? '0.01',
        'FEEAMT'          => Service::session()->get('nvpReqArray')['AMT'] ?? '0.01',
        'TAXAMT'          => '0.00',
        'CURRENCYCODE'    => 'EUR',
        'PAYMENTSTATUS'   => 'Completed',
        'PENDINGREASON'   => 'None',
        'REASONCODE'      => 'None'
      ],
    };
  }
  
  public function redirectToPayPal($returnURL, $params = []): Response
  {
    return  Service::redirect()->redirect($returnURL . '&token=' . $params['TOKEN'] . '&PayerID=YH6TMF8WWZR5J')->send();
  }
}