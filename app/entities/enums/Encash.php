<?php

namespace AC\app\entities\enums;

enum Encash: string
{
  case Cash = '0';
  case Invoice = '1';
  case PrivateAccount = '2';
  case PayOnline = '3';
  case Card = '4';

  public function label(): string
  {
    return match ($this) {
      self::Cash           => lang('Cash payment'),
      self::Invoice        => lang('Invoice payment'),
      self::PrivateAccount => lang('Private account payment'),
      self::PayOnline      => config('payment')->usePayonePayment() ? lang('Payone payment') : lang('Paypal payment'),
      self::Card           => lang('Card payment'),
    };
  }

  public function alias(): string
  {
    return match ($this) {
      self::Cash           => 'cash',
      self::Invoice        => 'invoice',
      self::PrivateAccount => 'private_account',
      self::PayOnline      => config('payment')->usePayonePayment() ? 'payone' : 'paypal',
      self::Card           => 'card',
    };
  }

  public function shortLabel(): string
  {
    return lang('short_label_payment_' . strtolower($this->alias()));
  }

  public function shortAlias(): string
  {
    return match ($this) {
      self::Cash           => 'BR',
      self::Invoice        => 'RE',
      self::PrivateAccount => 'GH',
      self::PayOnline      => config('payment')->usePayonePayment() ? 'PO' : 'PP',
      self::Card           => 'EC',
      default              => 'UNK'
    };
  }
}
