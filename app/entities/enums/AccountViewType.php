<?php

namespace AC\app\entities\enums;

/**
 * Технический тип представления счета в административной части и PDF.
 */
enum AccountViewType: int
{
  case Individual     = 1;
  case Abo            = 2;
  case Special        = 3;
  case PrivateAccount = 4;
  case MembershipFees = 5;
  case AboFitness     = 6;
  case OnlinePayment  = 7;
  case AboLight       = 21;

  /**
   * Получить соответствующий тип записи в `accounts.account_type`.
   */
  public function accountType(): AccountType
  {
    return match ($this) {
      self::Individual                            => AccountType::Individual,
      self::Abo, self::AboFitness, self::AboLight => AccountType::Abo,
      self::Special                               => AccountType::Special,
      self::PrivateAccount                        => AccountType::PrivateAccount,
      self::MembershipFees                        => AccountType::MembershipFees,
      self::OnlinePayment                         => AccountType::OnlinePayment,
    };
  }

  /**
   * Получить основной тип представления по типу записи счета.
   */
  public static function fromAccountType(AccountType $accountType): self
  {
    return match ($accountType) {
      AccountType::Individual     => self::Individual,
      AccountType::Abo            => self::Abo,
      AccountType::Special        => self::Special,
      AccountType::PrivateAccount => self::PrivateAccount,
      AccountType::MembershipFees => self::MembershipFees,
      AccountType::OnlinePayment  => self::OnlinePayment,
    };
  }

  /**
   * Получить префикс номера счета.
   */
  public function numberPrefix(): string
  {
    return match ($this) {
      self::Individual                            => ACCOUNT_NUMBER,
      self::Abo, self::AboFitness, self::AboLight => ABO_ACCOUNT_NUMBER,
      self::Special                               => OTHER_ACCOUNT_NUMBER,
      self::PrivateAccount                        => PREPAYMENT_ACCOUNT_NUMBER,
      self::MembershipFees                        => MEMBERSHIP_FEES_ACCOUNT_NUMBER,
      self::OnlinePayment                         => ONLINE_PAYMENT_ACCOUNT_NUMBER,
    };
  }
}
