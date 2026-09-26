<?php

namespace AC\core\modules\payment\payone\http\request\method;

use AC\core\modules\payment\payone\http\request\AbstractRequest;

class CurlRequestMethod extends AbstractMethodRequest
{
  public function sendRequest(AbstractRequest $request, $data)
  {
    unset($data['hash']);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $request->getEndpoint());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return response instead of outputting it directly
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);

    $response = curl_exec($ch);

    if (!$response && curl_errno($ch)) {
      $response = ['status' => 'ERROR', 'errorcode' => curl_errno($ch), 'errormessage' => curl_error($ch)];
    }
    curl_close($ch);
    return $response;
  }
}