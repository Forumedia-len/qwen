<?php

namespace AC\app\entities\traits\fields;

use DateTimeImmutable;
use DateTimeInterface;

trait HasDate
{
  public DateTimeInterface $date;
  
  public function getDateTime(string $format = 'Y-m-d H:i:s'): ?string { return $this->date?->format($format); }
  
  public function setDateTime(?string $date): void { $this->date = new DateTimeImmutable($date ?? date('Y-m-d H:i:s')); }
  
  public function getTimestamp(): int { return $this->date->getTimestamp(); }
  
  public function getDate(string $format = 'Y-m-d'): ?string { return $this->date?->format($format); }
  
  public function getTime(string $format = 'H:i:s'): ?string { return $this->date?->format($format); }
}