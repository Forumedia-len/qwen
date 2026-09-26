<?php

namespace AC\core\modules\payment\payone\http\response;

class PayoneResponse
{
  public function success(string $message, array $params = []): string
  {
    return self::view('success', ['message' => $message, ...$params]);
  }

  public function wait(array $parmas = []): string
  {
    return self::view('wait', $parmas);
  }

  public function error(string $message): string
  {
    return self::view('error', ['message' => $message]);
  }

  private function view(string $path, array $vars = []): string
  {
    return view()->render('output/' . $path, $vars);
  }
}