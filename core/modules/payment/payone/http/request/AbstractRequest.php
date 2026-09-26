<?php

namespace AC\core\modules\payment\payone\http\request;

use AC\core\modules\payment\payone\http\request\method\AbstractMethodRequest;

abstract class AbstractRequest
{
  protected $parameters;
  protected $request_code = 'undefined';

  protected $hash_fields
    = [
      // From the SDK
      'mid',
      'amount',
      'productid',
      'aid',
      'currency',
      'accessname',
      'portalid',
      'due_time',
      'accesscode',
      'mode',
      'storecarddata',
      'access_expiretime',
      'request',
      'checktype',
      'access_canceltime',
      'responsetype',
      'addresschecktype',
      'access_starttime',
      'reference',
      'consumerscoretype',
      'access_period',
      'userid',
      'invoiceid',
      'access_aboperiod',
      'customerid',
      'invoiceappendix',
      'access_price',
      'param',
      'invoice_deliverymode',
      'access_aboprice',
      'narrative_text',
      'eci',
      'access_vat',
      'successurl',
      'settleperiod',
      'errorurl',
      'settletime',
      'backurl',
      'vaccountname',
      'exiturl',
      'vreference',
      'clearingtype',
      'encoding',
      //
      //Special remarks - Recurring transactions credit card (https://docs.payone.com/display/public/PLATFORM/Special+remarks+-+Recurring+transactions+credit+card#expand-SampleInitialRequest)
      'customer_is_present',
      'recurrence',
      //
      // Listed in documentation only, either in a dedicated list or
      // or marked as requiring a hash in the field tables.
      'amount_recurring',
      'period_length_recurring',
      'period_unit_recurring',
      //
      'amount_trail',
      'period_length_trail',
      'period_unit_trail',
      //
      'api_version',
      'display_name',
      'display_address',
      'autosubmit',
      'targetwindow',
      'frontend_description',
      //
      'booking_date',
      'document_date',
      'ecommercemode',
      'getusertoken',
      'mandate_identification',
      'settleaccount',
      //
      'invoice_deliverydate',
      'invoice_deliveryenddate',
      //
      // Cart items, where [x] matches a wildcard.
      'pr[x]',
      'id[x]',
      'it[x]',
      'ti[x]',
      'de[x]',
      'va[x]',
      'no[x]',
      //
      'pr_recurring[x]',
      'id_recurring[x]',
      'ti_recurring[x]',
      'va_recurring[x]',
      'no_recurring[x]',
      'de_recurring[x]',
      //
      'pr_trail[x]',
      'id_trail[x]',
      'ti_trail[x]',
      'de_trail[x]',
      'va_trail[x]',
      'no_trail[x]',
    ];

//  const PAYONE_FRONTEND_URL = 'https://frontend.pay1.de/frontend/v2/';
  const PAYONE_FRONTEND_URL = 'https://frontend.pay1.de/frontend/v2/';
  const PAYONE_CLASSIC_URL  = 'https://secure.pay1.de/frontend/';
  const PAYONE_SERVER_URL   = 'https://api.pay1.de/post-gateway/';
  const PAYONE_CLIENT_URL   = 'https://secure.pay1.de/client-api/';

  /**
   * @var
   */
  protected $endpoint;

  abstract protected function setEndpoint();

  public function __construct($parameters)
  {
    $this->parameters = $parameters;
    $this->setEndpoint();
  }

  protected function getParam($alias, $type = 'portal',$default = '')
  {
    return isset($this->parameters[$type .'Data'][$alias]) ? $this->parameters[$type .'Data'][$alias] : $default;
  }

  /**
   * @return mixed
   */
  public function getEndpoint()
  {
    return $this->endpoint;
  }

  protected function getParamsType($type)
  {
    return isset($this->parameters[$type .'Data']) ? $this->parameters[$type .'Data'] : [];
  }

  abstract public function getData();

  public function send()
  {
    $data = $this->getData();
    $data['hash']  = $this->hash($data);
//    Debug()::dvDD($_SESSION, $data);
    return $this->sendData($data);
  }

  /**
   * The response to sending the request is a text list of name=value pairs.
   * The output data is a mix of the sent data with the received data appended.
   */
  public function sendData($data)
  {
    return $this->createMethodRequest()->sendRequest($this, $data);
  }


  /**
   *
   * @return AbstractMethodRequest
   */
  abstract protected function createMethodRequest();

  protected function filterHashFields($data)
  {
    foreach ($data as $key => $value) {
      // If the key is an array element then normalise it, e.g. pr[1] => pr[x]
      if (strpos($key, '[')) {
        $normalised_key = preg_replace('/\[[0-9]*\]/', '[x]', $key);
      } else {
        $normalised_key = $key;
      }

      // If the normalised key is not in the list of hashable keys,
      // then remove that element from the supplied data.
      if (! in_array($normalised_key, $this->hash_fields)) {
        unset($data[$key]);
      }
    }

    // Return the data array with non-hashable elements removed.
    return $data;
  }

  public function hash($data)
  {
    $hash_data = $this->filterHashFields($data);

    ksort($hash_data, SORT_NATURAL);

    return strtolower(hash_hmac("sha384", implode('', $hash_data), $this->getParam('key')));
  }

}