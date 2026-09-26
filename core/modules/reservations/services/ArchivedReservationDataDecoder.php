<?php

declare(strict_types=1);

namespace AC\core\modules\reservations\services;

use RuntimeException;
use Throwable;

/** Декодирует данные удалённой брони и восстанавливает старый повреждённый JSON. */
final class ArchivedReservationDataDecoder
{
  /**
   * @return array<string, mixed>
   *
   * @throws RuntimeException
   * @throws Throwable
   */
  public function decode(string $json): array
  {
    try {
      $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
      $data = $this->repairBrokenStreetFriends($json, $exception);
    }

    if (!is_array($data)) {
      throw new RuntimeException('Archived reservation data must be a JSON object.');
    }

    $data['street_friends'] = $this->normalizeStreetFriends($data['street_friends'] ?? null);

    return $data;
  }

  /**
   * @return array<string, mixed>
   *
   * @throws RuntimeException
   * @throws Throwable
   */
  private function repairBrokenStreetFriends(string $json, Throwable $previous): array
  {
    $property = '"street_friends":';
    $propertyPosition = strpos($json, $property);
    if ($propertyPosition === false) {
      throw new RuntimeException('Archived reservation data does not contain street_friends.', 0, $previous);
    }

    $valueStart = $this->skipWhitespace($json, $propertyPosition + strlen($property));
    if (($json[$valueStart] ?? null) === '"') {
      $serializedStart = $valueStart + 1;
      $valueFinish = $this->findSerializedValueFinish($json, $serializedStart);
      if ($valueFinish !== null) {
        try {
          $serialized = substr($json, $serializedStart, $valueFinish - $serializedStart);
          $fixedJson = substr($json, 0, $valueStart)
            . json_encode($serialized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
            . substr($json, $valueFinish + 1);
          $data = json_decode($fixedJson, true, 512, JSON_THROW_ON_ERROR);
          if (is_array($data)) {
            return $data;
          }
        } catch (Throwable $exception) {
          $previous = $exception;
        }
      }
    }

    return $this->recoverRequiredDataBeforeStreetFriends($json, $propertyPosition, $previous);
  }

  /**
   * @return array{start: mixed, finish: mixed, encash: mixed, price: mixed}
   *
   * @throws RuntimeException
   */
  private function recoverRequiredDataBeforeStreetFriends(
    string $json,
    int $propertyPosition,
    Throwable $previous
  ): array {
    $prefix = rtrim(substr($json, 0, $propertyPosition));
    if (str_ends_with($prefix, ',')) {
      $prefix = rtrim(substr($prefix, 0, -1));
    }

    try {
      $recoveredJson = $prefix . chr(125);
      $data = json_decode($recoveredJson, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
      throw new RuntimeException(
        'Archived reservation data before street_friends could not be recovered.',
        0,
        $exception
      );
    }

    if (!is_array($data)) {
      throw new RuntimeException('Recovered archived reservation data must be a JSON object.', 0, $previous);
    }

    foreach (['start', 'finish', 'encash', 'price'] as $requiredField) {
      if (!array_key_exists($requiredField, $data)) {
        throw new RuntimeException('Recovered archived reservation data does not contain required fields.', 0, $previous);
      }
    }

    return [
      'start' => $data['start'],
      'finish' => $data['finish'],
      'encash' => $data['encash'],
      'price' => $data['price'],
    ];
  }

  private function findSerializedValueFinish(string $json, int $serializedStart): ?int
  {
    $searchOffset = $serializedStart;
    while (($candidateFinish = strpos($json, '"', $searchOffset)) !== false) {
      $nextTokenPosition = $this->skipWhitespace($json, $candidateFinish + 1);
      $nextToken = $json[$nextTokenPosition] ?? null;
      if (($nextToken === ',' || $nextToken === '}')
        && $this->unserializeArray(substr($json, $serializedStart, $candidateFinish - $serializedStart)) !== null) {
        return $candidateFinish;
      }

      $searchOffset = $candidateFinish + 1;
    }

    return null;
  }

  /**
   * @return array<int|string, mixed>
   */
  private function normalizeStreetFriends(mixed $value): array
  {
    if (is_array($value)) {
      return $value;
    }
    if (!is_string($value) || $value === '') {
      return [];
    }

    return $this->unserializeArray($value) ?? [];
  }

  /**
   * @return array<int|string, mixed>|null
   */
  private function unserializeArray(string $value): ?array
  {
    if (!str_starts_with($value, 'a:')) {
      return null;
    }

    try {
      $data = @unserialize($value, ['allowed_classes' => false]);
    } catch (Throwable) {
      return null;
    }

    return is_array($data) ? $data : null;
  }

  private function skipWhitespace(string $value, int $offset): int
  {
    $length = strlen($value);
    while ($offset < $length && str_contains(" \t\r\n", $value[$offset])) {
      $offset++;
    }

    return $offset;
  }
}
