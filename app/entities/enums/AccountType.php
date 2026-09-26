<?php

namespace AC\app\entities\enums;

//0-счет, 1-abo, 2-специальный, 3-приватный личный, 4-членские взносы, 5-онлайн-оплата
enum AccountType: string
{
  case Individual     = '0';
  case Abo            = '1';
  case Special        = '2';
  case PrivateAccount = '3';
  case MembershipFees = '4';
  case OnlinePayment  = '5';
  
  public function alias(): string
  {
    return match ($this) {
      self::Individual     => 'individual',
      self::Abo            => 'abo',
      self::Special        => 'special',
      self::PrivateAccount => 'private_account',
      self::MembershipFees => 'membership_fees',
      self::OnlinePayment  => 'online_payment',
    };
  }
  
  public function nameTextTitle(): string
  {
    return match ($this) {
      self::Individual     => lang('Individual invoices', 'account_pdf_template'),
      self::Abo            => lang('Abo invoices', 'account_pdf_template'),
      self::Special        => lang('Special invoices', 'account_pdf_template'),
      self::PrivateAccount => lang('Private account invoices', 'account_pdf_template'),
      self::MembershipFees => lang('Membership fees invoices', 'account_pdf_template'),
      self::OnlinePayment  => lang('Online payment invoices', 'account_pdf_template'),
    };
  }
  
  public static function fromAlias(string $alias): ?self
  {
    return match ($alias) {
      'individual'      => self::Individual,
      'abo'             => self::Abo,
      'special'         => self::Special,
      'private_account' => self::PrivateAccount,
      'membership_fees' => self::MembershipFees,
      'online_payment'  => self::OnlinePayment,
      default           => null,
    };
  }
}
