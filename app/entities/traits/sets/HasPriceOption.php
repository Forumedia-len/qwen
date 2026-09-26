<?php

namespace AC\app\entities\traits\sets;

use AC\app\entities\traits\fields\HasId;

trait HasPriceOption
{
  use HasId;
  public string  $code           = '';
  public string  $title          = '';
  public float   $rate           = 0.0;
  public ?string $typeSport      = null;
  public bool    $forAll         = false;
  public bool    $duration       = false;
  public ?string $durationStart  = null;   // format: 'Y-m-d'
  public ?string $durationFinish = null;   // format: 'Y-m-d'
  public array   $durations      = [];
}