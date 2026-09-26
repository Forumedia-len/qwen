<?php

namespace AC\core\modules\payment\payone\http\request;

class FrontendPreauthorizationRequest extends FrontendAuthorizationRequest
{
  protected $request_code = 'preauthorization';
}