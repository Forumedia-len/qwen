<?php

namespace AC\core\modules\payment\payone\entities\dto;

use AC\core\modules\payment\payone\services\PayoneDataService;
use Service;

class UrlDataDto
{
  public ?string $successurl;
  public ?string $backurl;
  public ?string $errorurl;

  public static function fromArray(array $data): self
  {
    $dto = new self();

    if (isset($data['successurl'])) {
      $dto->successurl = $data['successurl'];
    }
    if (isset($data['backurl'])) {
      $dto->backurl = $data['backurl'];
    }
    if (isset($data['errorurl'])) {
      $dto->errorurl = $data['errorurl'];
    }

    if (isset($data['useUrlCreation']) && $data['useUrlCreation']) {
      $dto->createUrls();
    }

    return $dto;
  }

  public function toArray(): array
  {
    return [
      'successurl' => $this->successurl,
      'backurl'    => $this->backurl,
      'errorurl'   => $this->errorurl,
    ];
  }

  public function createUrls($overwrite = false): void
  {
    foreach (['success', 'back', 'error'] as $status) {
      if (empty($this->{$status . 'url'}) || $overwrite) {
        $this->{$status . 'url'} = site_url() . Service::structure()->getPageHrefByKey('payment_payone') . '/' . $status;
      }
    }
  }
}