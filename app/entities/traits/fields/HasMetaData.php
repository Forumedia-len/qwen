<?php

namespace AC\app\entities\traits\fields;

trait HasMetaData
{
  /**
   * @var array<array>
   */
  private array $metaData = [];
  
  protected function getData($key): array { return $this->metaData[$key] ?? []; }
  
  protected function setData($key, $data): void { $this->metaData[$key] = $data; }
  
  public function getKeys(): array
  {
    return array_keys($this->metaData);
  }
  
  public function hasKey($key): bool
  {
    return isset($this->metaData[$key]);
  }
}