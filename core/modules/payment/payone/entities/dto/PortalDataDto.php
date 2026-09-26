<?php

namespace AC\core\modules\payment\payone\entities\dto;

class PortalDataDto
{
  public string  $mid;
  public string  $aid;
  public string  $portalid;
  public string  $key;
  public string  $api_version;
  public string  $mode;
  public string  $encoding;
  public string  $param;
  public ?string $txid;
  public int     $sequencenumber;
  public string  $reference;

  public static function fromArray(array $data): self
  {
    $dto                 = new self();
    $dto->mid            = $data['mid'];
    $dto->aid            = $data['aid'];
    $dto->portalid       = $data['portalid'];
    $dto->key            = $data['key'];
    $dto->api_version    = $data['api_version'];
    $dto->mode           = $data['mode'];
    $dto->encoding       = $data['encoding'];
    $dto->param          = $data['param'];
    $dto->txid           = $data['txid'] ?? null;
    $dto->sequencenumber = $data['sequencenumber'] ?? 0;
    $dto->reference      = $data['reference'] ?? uniqid();

    return $dto;
  }

  public function toArray(): array
  {
    return [
      'mid'            => $this->mid,
      'aid'            => $this->aid,
      'portalid'       => $this->portalid,
      'key'            => $this->key,
      'api_version'    => $this->api_version,
      'mode'           => $this->mode,
      'encoding'       => $this->encoding,
      'param'          => $this->param,
      'txid'           => $this->txid,
      'sequencenumber' => $this->sequencenumber,
      'reference'      => $this->reference,
    ];
  }
}