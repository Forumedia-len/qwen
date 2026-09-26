<?php

namespace AC\core\modules\payment\paypal\models;

use AC\core\modules\payment\config\PaypalConfig;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\http\response\Response;
use Exception;
use Service;

class PaypalPaymentModel
{
  /**
   * @var PaypalConfig
   */
  protected PaypalConfig $config;
  
  
  public function __construct()
  {
    $this->config = OnlineGatewayService::paypalConfig();
  }
  
  public function hashCall($methodName, $nvpStr, &$errors = null)
  {
    $options = [
      'baseURI' => $this->config->endpoint,
    ];
    $nvpreq  = $this->getNvpReqString($methodName, $nvpStr);

    $curl = Service::curl($options);
    $curl->setBody($nvpreq);
    try {
      $response = $curl->post($this->config->endpoint);
      //convrting NVPResponse to an Associative Array
      $nvpResArray = $this->deformatNVP($response?->getBody());
      $this->setNvpReqArray($nvpreq);
      
      return $nvpResArray;
    } catch (Exception $e) {
      
      [$errors['number'], $errors['message']] = explode(' : ', $e->getMessage());
      Service::session()->set('errors', $errors);
      
      return false;
    }
  }
  
  public function redirectToPayPal($returnURL, $params = []): Response
  {
    return  Service::redirect()->redirect($this->config->api_url . urldecode($params['TOKEN']))->send();
  }
  
  protected function getNvpReqString($methodName, $nvpStr): string
  {
    return 'METHOD=' . urlencode($methodName) . '&VERSION=' . urlencode($this->config->api_version)
      . '&PWD=' . urlencode($this->config->api_password) . '&USER=' . urlencode($this->config->api_username)
      . '&SIGNATURE=' . urlencode($this->config->api_signature) . $nvpStr;
  }
  
  protected function setNvpReqArray($nvpreq): void
  {
    $nvpReqArray = $this->deformatNVP($nvpreq);
    Service::session()->set('nvpReqArray', $nvpReqArray);
  }
  
  protected function deformatNVP($nvpstr)
  {
    parse_str(urldecode($nvpstr), $nvpArray);
    
    return $nvpArray;
  }
}