<?php

namespace AC\core\system\helpers;

use Service;

/**
 * Вспомогательный класс для работы с JSON.
 */
class JsonHelper
{
  /**
   * Декодирует JSON-строку в массив или объект.
   *
   * @param ?string $json          JSON-строка для декодирования
   * @param bool    $isAssociative Если true — возвращает ассоциативный массив, иначе объект
   * @param int     $depth         Максимальная глубина рекурсии
   * @param int     $flags         Флаги json_decode
   * @return mixed|object|array Декодированный JSON или исходная строка в случае ошибки
   */
  public static function decode(?string $json, bool $isAssociative = false, int $depth = 512, int $flags = 0): mixed
  {
    try {
      return json_decode($json, $isAssociative, $depth, JSON_THROW_ON_ERROR | $flags);
    } catch (\JsonException $e) {
      if (!empty($json)) {
        Service::logger('json')->logException(
          $e,
          'JSON Decode Error',
          ['json_string' => $json]
        );
      }
      return $json; // Возвращаем исходную строку как fallback
    }
  }
  
  /**
   * Кодирует данные в JSON-строку.
   *
   * @param mixed $value Данные для кодирования
   * @param int   $flags Флаги json_encode
   * @param int   $depth Максимальная глубина рекурсии
   * @return string|false JSON-строка или false в случае ошибки
   */
  public static function encode(mixed $value, int $flags = 0, int $depth = 512): string|false
  {
    try {
      return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | $flags, $depth);
    } catch (\JsonException $e) {
      Service::logger('json')->logException(
        $e,
        'JSON Encode Error',
        ['encoded_value' => $value]
      );
      return false;
    }
  }
  
  /**
   * Проверяет, является ли строка корректным JSON.
   *
   * @param string $string Строка для проверки
   * @return bool true, если строка — корректный JSON
   */
  public static function validate(string $string): bool
  {
    try {
      self::decode($string);
      return true;
    } catch (\JsonException $e) {
      Service::logger('json')->logException(
        $e,
        'JSON Validation Error',
        ['validation_string' => $string],
        'warning'
      );
      return false;
    }
  }
  
  /**
   * Отправляет успешный JSON-ответ.
   *
   * @param array $data Данные для отправки
   * @return void
   */
  public static function success(array $data = []): void
  {
    self::sendResponse(['success' => true, 'data' => $data]);
  }
  
  /**
   * Отправляет JSON-ответ об ошибке.
   *
   * @param array $data       Данные об ошибке
   * @param int   $httpStatus Код HTTP-статуса (по умолчанию 400)
   * @return void
   */
  public static function error(array $data = [], int $httpStatus = 400): void
  {
    self::sendResponse(['success' => false, 'error' => $data], $httpStatus);
  }
  
  /**
   * Общая функция для отправки JSON-ответа.
   *
   * @param array $response   Массив данных для ответа
   * @param int   $httpStatus Код HTTP-статуса (по умолчанию 200)
   * @return void
   */
  private static function sendResponse(array $response, int $httpStatus = 200): void
  {
    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=UTF-8');
    
    try {
      $encoded = self::encode($response, JSON_UNESCAPED_UNICODE);
      if ($encoded === false) {
        throw new \RuntimeException('Failed to encode JSON response');
      }
      echo $encoded;
    } catch (\JsonException $e) {
      Service::logger('json')->logException(
        $e,
        'JSON Response Encoding Error',
        ['response_data' => $response],
        'critical'
      );
      self::handleCriticalError();
    } catch (\Exception $e) {
      Service::logger('json')->logException(
        $e,
        'Unexpected Error in JSON Response',
        ['exception_data' => $e->getMessage()],
        'critical'
      );
      self::handleCriticalError();
    }
    
    exit;
  }
  
  /**
   * Обрабатывает критические ошибки при отправке JSON-ответа.
   *
   * @return void
   */
  private static function handleCriticalError(): void
  {
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'error'   => ['message' => 'Internal server error during JSON encoding']
    ]);
  }
}
