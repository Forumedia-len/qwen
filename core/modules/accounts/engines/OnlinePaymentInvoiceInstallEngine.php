<?php

namespace AC\core\modules\accounts\engines;

use AC\app\entities\enums\AccountType;
use AC\core\system\db\Query;
use RuntimeException;

/**
 * Проверяет и при необходимости обновляет структуру типа онлайн-счетов.
 */
class OnlinePaymentInvoiceInstallEngine
{
  /**
   * Подготовить поле типа счета для хранения онлайн-счетов.
   */
  public function ensureInstalled(): void
  {
    $column = $this->getAccountTypeColumn();
    if ($column === null) {
      throw new RuntimeException('The accounts.account_type column does not exist.');
    }

    if ($this->columnSupportsOnlinePayment($column)) {
      return;
    }

    $type = strtolower((string)($column['Type'] ?? ''));
    if (!str_starts_with($type, 'enum(')) {
      throw new RuntimeException('The accounts.account_type column has an unsupported type: ' . $type);
    }

    $this->addOnlinePaymentEnumValue($column);

    $column = $this->getAccountTypeColumn();
    if ($column === null || !$this->columnSupportsOnlinePayment($column)) {
      throw new RuntimeException('The accounts.account_type column is not ready for online payment invoices after migration.');
    }
  }

  /**
   * Готова ли структура таблицы счетов к новому типу.
   */
  public function isInstalled(): bool
  {
    $column = $this->getAccountTypeColumn();

    return $column !== null && $this->columnSupportsOnlinePayment($column);
  }

  /**
   * @return array<string, mixed>|null
   */
  private function getAccountTypeColumn(): ?array
  {
    if (!in_array('accounts', Query::getDB()->getNameTables(false), true)) {
      return null;
    }

    $column = Query::sqlQuery(
      'SHOW FULL COLUMNS FROM ' . Query::tableName('accounts') . " LIKE 'account_type'",
      [],
      true,
      ['onlyOne' => true]
    );

    return is_array($column) && $column !== [] ? $column : null;
  }

  /**
   * @param array<string, mixed> $column
   */
  private function columnSupportsOnlinePayment(array $column): bool
  {
    $type = strtolower((string)($column['Type'] ?? ''));
    if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)\b/', $type) === 1) {
      return true;
    }

    if (preg_match('/^(?:var)?char\((\d+)\)/', $type, $matches) === 1) {
      return (int)$matches[1] >= strlen(AccountType::OnlinePayment->value);
    }

    if (!str_starts_with($type, 'enum(')) {
      return false;
    }

    return in_array(AccountType::OnlinePayment->value, $this->parseEnumValues($type), true);
  }

  /**
   * @param array<string, mixed> $column
   */
  private function addOnlinePaymentEnumValue(array $column): void
  {
    $type = (string)$column['Type'];
    $closingBracketPosition = strrpos($type, ')');
    if ($closingBracketPosition === false) {
      throw new RuntimeException('Invalid accounts.account_type enum definition.');
    }

    $newType = substr($type, 0, $closingBracketPosition)
      . ',\'' . AccountType::OnlinePayment->value . '\''
      . substr($type, $closingBracketPosition);

    Query::sqlQueryWithExtendedPrivileges(
      'ALTER TABLE ' . Query::tableName('accounts') . ' MODIFY COLUMN `account_type` '
      . $newType . $this->getColumnAttributesSql($column),
      [],
      false
    );
  }

  /**
   * @return list<string>
   */
  private function parseEnumValues(string $type): array
  {
    $start = strpos($type, '(');
    $finish = strrpos($type, ')');
    if ($start === false || $finish === false || $finish <= $start) {
      return [];
    }

    return str_getcsv(substr($type, $start + 1, $finish - $start - 1), ',', "'", '\\');
  }

  /**
   * @param array<string, mixed> $column
   */
  private function getColumnAttributesSql(array $column): string
  {
    $nullable = ($column['Null'] ?? 'NO') === 'YES';
    $sql = '';

    $collation = (string)($column['Collation'] ?? '');
    if ($collation !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $collation) === 1) {
      $sql .= ' COLLATE ' . $collation;
    }

    $sql .= $nullable ? ' NULL' : ' NOT NULL';

    if ($column['Default'] !== null) {
      $sql .= ' DEFAULT ' . $this->quoteSqlLiteral((string)$column['Default']);
    } elseif ($nullable) {
      $sql .= ' DEFAULT NULL';
    }

    if (!empty($column['Comment'])) {
      $sql .= ' COMMENT ' . $this->quoteSqlLiteral((string)$column['Comment']);
    }

    return $sql;
  }

  private function quoteSqlLiteral(string $value): string
  {
    return "'" . str_replace("'", "''", $value) . "'";
  }
}
