<?php

namespace AC\app\entities\traits\fields;

trait HasEntryId
{
  public ?int $entryId = null;
  
  public function getEntryId(): ?int { return $this->entryId; }
  
  public function setEntryId(int $entryId): void { $this->entryId = $entryId; }
}