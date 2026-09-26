<?php

namespace AC\core\system\helpers;

class UniqHelper
{
  /**
   * Генерирует уникальный идентификатор
   *
   * @param string $prefix Префикс
   * @return string Уникальный ID
   */
  public static function generateUniqId(string $prefix = 'at'): string
  {
    return uniqid($prefix . '_', true) . '_' . microtime(true);
  }
  
  /**
   * Генерирует UUID v4 с использованием random_bytes
   *
   * @return string UUID в формате 8-4-4-4-12
   */
  public static function generateUuid(): string
  {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Версия 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Вариант RFC 4122
    
    $hex = bin2hex($data);
    
    // Разбиваем на части по правилам UUID v4 (8-4-4-4-12)
    $parts = [
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    ];
    
    return implode('-', $parts);
  }
}
