<?php

namespace AC\core\system\debug;

use SplFileObject;


/**
 * Класс VDebugsCli — аналог VDebugs, адаптированный под CLI.
 */
class VDebugsCli extends VDebugs
{
  
  /**
   * Форматирует массив аргументов в текстовый дамп для CLI.
   *
   * @param array $arguments_array
   * @param bool  $full_trace
   * @param bool  $tracePrint
   * @param bool  $useWrapper
   * @return string Отформатированный текст
   */
  protected static function getArgumentArrayPlainTextDamp(
    array $arguments_array,
    bool $full_trace = false,
    bool $tracePrint = true,
    bool $useWrapper = false
  ): string {
    $c = static::getDebugBackTrace($tracePrint, $full_trace, $useWrapper);
    if (count($arguments_array) > 0) {
      $c[] = static::varDamp($arguments_array);
    } else {
      $c[] = 'Error: no arguments received';
    }
    return implode("\n\n", $c);
  }
  
  /**
   * Получает трассировку стека вызовов для CLI.
   *
   * @param bool $tracePrint
   * @param bool $full_trace
   * @param bool $useWrapper
   * @return array Массив трассировки
   */
  protected static function getDebugBackTrace(bool $tracePrint = true, bool $full_trace = false, bool $useWrapper = true): array
  {
    $trace     = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $trace_out = [];
    $lastCall  = null;
    
    foreach ($trace as $i => $tr) {
      if (!str_ends_with($tr['file'] ?? '', 'VDebugs.php')
//        && !str_ends_with($tr['file'] ?? '', 'VDebugsCli.php')
        && !str_ends_with($tr['file'] ?? '', 'main.php')) {
        $file = $tr['file'] ?? 'unknown';
        
        if ($full_trace) {
          if(empty($trace_out)) {
            $trace_out[] = "#\tFile\tLine\tCode";;
          }
          try {
            $fileObj = new SplFileObject($file);
            $fileObj->seek($tr['line'] - 1);
            $codeLine = trim($fileObj->current());
          } catch (\Exception $e) {
            $codeLine = '--- (could not read line)';
          }
          // Форматируем строку как таблицу
          $trace_out[] = "#$i\t$file\t{$tr['line']}\t\"$codeLine\"";
        }
        
        if ($lastCall === null) {
          $lastCall = $tr;
        }
      }
    }
    
    if ($tracePrint) {
      
      $output = [
        date("d.m.Y H:i:s"),
        $full_trace ? implode("\n", $trace_out) : '',
        "Last call path: " . ($lastCall['file'] ?? '')
        . ($lastCall['line'] ? " | Line: " . $lastCall['line'] : '')
      ];
      
      return $output;
    }
    
    return [];
  }
  
  
  /**
   * Выводит цветной текст в консоль (если поддерживается).
   *
   * @param string $text  Текст
   * @param string $color Цвет текста (ansi)
   * @return string
   */
  protected static function colorText(string $text, string $color): string
  {
    if (stripos(PHP_OS, 'win') === false) {
      // Linux / macOS
      return "\033[38;2;100;100;255m$text\033[0m"; // Синий текст
    }
    
    return $text;
  }
}
