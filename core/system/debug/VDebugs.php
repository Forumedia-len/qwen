<?php

namespace AC\core\system\debug;

use AC\core\system\helpers\JsonHelper;
use RuntimeException;
use Service;
use SplFileObject;

defined('ROOT_PATH') || define('ROOT_PATH', dirname(__FILE__, 4));

/**
 * Класс для отладки, логгирования и форматированного вывода данных.
 *
 * Предоставляет набор статических методов для дампа переменных, сохранения логов,
 * генерации HTML-таблиц и работы с файлами.
 */
class VDebugs
{
  /**
   * Выводит дамп аргументов с краткой трассировкой стека.
   *
   * @param mixed ...$args Переменное количество аргументов для дампа
   * @return void
   *
   * @example VDebugs::dvD($user, $request);
   */
  public static function dvD(): void
  {
    static::dumpArguments(func_get_args(), false);
  }
  
  /**
   * Выводит дамп аргументов с полной трассировкой стека.
   *
   * @param mixed ...$args Переменное количество аргументов для дампа
   * @return void
   *
   * @example VDebugs::dvF($errorData);
   */
  public static function dvF(): void
  {
    static::dumpArguments(func_get_args(), true);
  }
  
  /**
   * Выводит дамп аргументов и завершает выполнение скрипта.
   *
   * @param mixed ...$args Переменное количество аргументов для дампа
   * @return void
   *
   * @example VDebugs::dvDD($exception);
   */
  public static function dvDD(): void
  {
    static::dumpArguments(func_get_args(), false);
    die;
  }
  
  /**
   * Выводит дамп аргументов с полной трассировкой и завершает выполнение скрипта.
   *
   * @param mixed ...$args Переменное количество аргументов для дампа
   * @return void
   *
   * @example VDebugs::dvFD($requestData);
   */
  public static function dvFD(): void
  {
    static::dumpArguments(func_get_args(), true);
    die;
  }
  
  /**
   * Сохраняет аргументы в файл без форматирования.
   *
   * @param mixed ...$args Переменное количество аргументов для сохранения
   * @return void
   *
   * @example VDebugs::sv($data);
   */
  public static function sv(): void
  {
    static::saveToFile(func_get_args(), false, false);
  }
  
  /**
   * Сохраняет аргументы в файл с полным форматированием.
   *
   * @param mixed ...$args Переменное количество аргументов для сохранения
   * @return void
   *
   * @example VDebugs::svF($responseData);
   */
  public static function svF(): void
  {
    static::saveToFile(func_get_args(), true, false);
  }
  
  /**
   * Сохраняет вывод `echo` в файл.
   *
   * @param mixed ...$args Переменное количество аргументов для сохранения
   * @return void
   *
   * @example VDebugs::svE($output);
   */
  public static function svE(): void
  {
    static::saveToFile(func_get_args(), false, true);
  }
  
  /**
   * Выводит данные с разрывами строк.
   *
   * @param mixed ...$args Переменное количество аргументов для вывода
   * @return void
   *
   * @example VDebugs::dEH($message);
   */
  public static function dEH(): void
  {
    echo static::formatEchoOutput(func_get_args(), "\n");
  }
  
  /**
   * Выводит данные с разрывами строк.
   *
   * @param mixed ...$args Переменное количество аргументов для вывода
   * @return void
   *
   * @example VDebugs::dEH($message);
   */
  public static function dBresult($message, $bgColor = '#eee'): void
  {
    echo '<pre style="background: ' . $bgColor
      . ';padding: 10px;display: block;z-index: 1000;position: relative; max-width: 100vw;  word-wrap: break-word;
          overflow-wrap: anywhere;word-break: break-word;white-space: pre-line">'
      . static::formatEchoOutput($message,
        "\n") . '</pre>';
  }

  /**
   * Выводит данные в `<pre>`-теге с HTML-экранированием.
   *
   * @param mixed ...$args Переменное количество аргументов для вывода
   * @return void
   *
   * @example VDebugs::dE($arrayData);
   */
  public static function dE(): void
  {
    $pStyle = 'style="background:#eee;padding:10px;display:block;z-index:1000;position:relative;"';
    echo '<pre style="word-wrap: break-word; overflow-wrap: anywhere;word-break: break-word;white-space: pre-line"><p '.$pStyle.'>'
      . static::formatEchoOutput(func_get_args(), "</p><p ".$pStyle.">", false, true) . '</p></pre>';
  }
  
  /**
   * Выводит данные с ключами в `<pre>`-теге с HTML-экранированием.
   *
   * @param mixed ...$args Переменное количество аргументов для вывода
   * @return void
   *
   * @example VDebugs::dEK($assocArray);
   */
  public static function dEK(): void
  {
    echo '<pre>' . static::formatEchoOutput(func_get_args(), "", true, true) . '</pre>';
  }
  
  /**
   * Общий метод для дампа аргументов.
   *
   * @param array $arguments Массив аргументов для дампа
   * @param bool  $fullTrace Флаг: использовать полную трассировку стека
   * @return void
   */
  protected static function dumpArguments(array $arguments, bool $fullTrace): void
  {
    $output = static::getArgumentArrayPlainTextDamp($arguments, $fullTrace);
    echo $output . "\n";
  }
  
  /**
   * Общий метод для сохранения аргументов в файл.
   *
   * @param array $arguments  Массив аргументов для сохранения
   * @param bool  $fullFormat Флаг: использовать полное форматирование
   * @param bool  $asEcho     Флаг: сохранять вывод `echo`
   * @return void
   */
  protected static function saveToFile(array $arguments, bool $fullFormat, bool $asEcho): void
  {
    $content = $asEcho ? static::getArgumentsEchoPlainText($arguments) : static::getArgumentsArrayPlainText($arguments, $fullFormat, true, false);
    static::saveF($content);
  }
  
  /**
   * Форматирует массив аргументов в текстовый дамп.
   *
   * @param array $arguments_array Массив аргументов
   * @param bool  $full_trace      Использовать полную трассировку
   * @param bool  $tracePrint      Выводить трассировку
   * @param bool  $useWrapper      Использовать HTML-обёртку
   * @return string Отформатированный текст
   */
  protected static function getArgumentArrayPlainTextDamp(
    array $arguments_array,
    bool $full_trace = false,
    bool $tracePrint = true,
    bool $useWrapper = true
  ): string {
    $c = static::getDebugBackTrace($tracePrint, $full_trace, $useWrapper);
    if (count($arguments_array) > 0) {
      $arguments_array = count($arguments_array) > 1 ? [$arguments_array] : $arguments_array;
      $c               = [...$c, ...array_map(fn($arg) => static::varDamp($arg), $arguments_array)];
    } else {
      $c[] = 'Error: no arguments received';
    }
    $c[] = '';
    
    return '<pre style="background: #eee;padding: 10px;display: block;z-index: 1000;position: relative;
          margin-bottom: 7px;max-width: 100vw; white-space: pre-wrap;">'
      . implode("", $c) . '</pre>';
  }
  
  /**
   * Выполняет `var_dump` и возвращает результат как строку.
   *
   * @param mixed $value Значение для дампа
   * @return string Результат `var_dump`
   */
  protected static function varDamp(mixed $value): string
  {
    ob_start();
    var_dump($value);
    $result = str_replace('class=\'xdebug-var-dump\'', 'class=\'xdebug-var-dump\' style=\'white-space: pre-wrap;\'', ob_get_clean());
    return preg_replace('/[A-Z]:\\\\[^:\r\n]*:\d+:\s*/', '', trim($result));
  }
  
  /**
   * Форматирует массив аргументов в текстовый массив.
   *
   * @param array $arguments_array Массив аргументов
   * @param bool  $full_trace      Использовать полную трассировку
   * @param bool  $tracePrint      Выводить трассировку
   * @param bool  $useWrapper      Использовать HTML-обёртку
   * @return string Отформатированный текст
   */
  protected static function getArgumentsArrayPlainText(
    array $arguments_array,
    bool $full_trace = false,
    bool $tracePrint = true,
    bool $useWrapper = true
  ): string {
    $c = static::getDebugBackTrace($tracePrint, $full_trace, $useWrapper);
    if (count($arguments_array) > 0) {
      foreach ($arguments_array as $b_number => $b_value) {
        $c[] = ($tracePrint ? $b_number . ":\n" : '') . static::indent(print_r($b_value, true));
      }
    } else {
      $c[] = 'Error: no arguments received';
    }
    $c[] = '';
    return join("\n", $c);
  }
  
  /**
   * Добавляет отступ к строке.
   *
   * @param string|null $text Строка для форматирования
   * @return string Строка с отступами
   */
  protected static function indent(?string $text): string
  {
    return "	" . str_replace("\n", "\n	", $text ?? '');
  }
  
  /**
   * Получает трассировку стека вызовов.
   *
   * @param bool $tracePrint Выводить трассировку
   * @param bool $full_trace Использовать полную трассировку
   * @param bool $useWrapper Использовать HTML-обёртку
   * @return array Массив трассировки
   */
  protected static function getDebugBackTrace(bool $tracePrint = true, bool $full_trace = false, bool $useWrapper = true): array
  {
    $trace     = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $trace_out = [];
    $lastCall  = null;
    
    foreach ($trace as $i => $tr) {
      if (!str_ends_with($tr['file'] ?? '', 'VDebugs.php') && !str_ends_with($tr['file'] ?? '', 'main.php')) {
        $file = $tr['file'] ?? 'unknown';
        
        if ($full_trace) {
          try {
            $fileObj = new SplFileObject($file);
            $fileObj->seek($tr['line'] - 1);
            $codeLine = trim($fileObj->current());
            $codeLine = htmlspecialchars($codeLine);
          } catch (\Exception $e) {
            $codeLine = '--- (could not read line)';
          }
          
          $code        = ($useWrapper
            ? "<span style='color: #0000cc'>\"$codeLine\"</span>"
            : "\"$codeLine\"");
          $trace_out[] = [
            '#' . $i,
            $file,
            $tr['line'] ?? '-',
            $code
          ];
        }
        
        
        if ($lastCall === null) {
          $lastCall = $tr;
        }
      }
    }
    
    if ($tracePrint) {
      $output = [
        date("d.m.Y H:i:s"),
        ($full_trace ? static::renderTraceTable($trace_out) : '') .
        ($useWrapper ? "\n" . '<span style=\'color: #0000cc\'>Last call path: ' : 'Last call path: ')
        . ($lastCall['file'] ?? '')
        . ($lastCall['line'] ? ' | Line: ' . $lastCall['line'] : '')
        . ($useWrapper ? '</span>' : '')
      ];
      
      return $output;
    }
    
    return [];
  }
  
  
  /**
   * Форматирует аргументы для вывода `echo`.
   *
   * @param array  $arguments Массив аргументов
   * @param string $separator Разделитель между элементами
   * @param bool   $viewKeys  Выводить ключи
   * @return string Отформатированный текст
   */
  protected static function getArgumentsEchoPlainText(
    array $arguments,
    string $separator = "",
    bool $viewKeys = false,
    $htmlspecialchars = false
  ): string {
    $result = [];
    if(is_array($arguments) && count($arguments) == 1) {
      $arguments = $arguments[0];
    }
    if (is_array($arguments) && count($arguments) > 0) {
      foreach ($arguments as $key => $value) {
        $formattedValue = is_array($value) || is_object($value)
          ? static::getArgumentsEchoPlainText($value, /*$separator . */($viewKeys ? "  " : ""), $viewKeys)
          : $value;
        $prefix  = $viewKeys ? $separator : "";
        $keyPart = $viewKeys ? $key . " => " : "";
        
        $openBrace  = $viewKeys && (is_array($value) || is_object($value)) ? "[\n" . "  " : "";
        $closeBrace = $viewKeys && (is_array($value) || is_object($value)) ? $separator . "]\n" : "";
        $valueRes   = $prefix . $keyPart . $openBrace . $formattedValue . "\n" . $closeBrace;
        $result[]   = ($htmlspecialchars ? htmlspecialchars($valueRes) : $valueRes);
      }
    }
    
    return implode($separator, $result);
  }
  
  /**
   * Форматирует аргументы для `echo`.
   *
   * @param array  $arguments Массив аргументов
   * @param string $separator Разделитель между элементами
   * @param bool   $viewKeys  Выводить ключи
   * @return string Отформатированный текст
   */
  protected static function formatEchoOutput(array $arguments, string $separator, bool $viewKeys = false, $htmlspecialchars = false): string
  {
    return static::getArgumentsEchoPlainText($arguments, $separator, $viewKeys, $htmlspecialchars);
  }
  
  /**
   * Сохраняет данные в XML-файл.
   *
   * @param mixed $params Объект с методом `save`
   * @return void
   *
   * @throws \Exception Если объект не поддерживает метод `save`
   */
  public static function saveAsXml(mixed $params): void
  {
    if (is_object($params) && method_exists($params, 'save')) {
      $params->save(ROOT_PATH . 'debugs.xml');
    }
  }
  
  /**
   * Генерирует HTML-таблицу из данных.
   *
   * @param array ...$args Переменные количества массивов данных
   * @return string HTML-таблица
   *
   * @example echo VDebugs::eT(['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']);
   */
  public static function eT(array ...$args): string
  {
    $result = '<table style="background: #fff;margin: -2px;width: 100%">';
    foreach ($args as $item) {
      foreach ($item as $key => $value) {
        $result .= '<tr style="margin-bottom: 7px">
                   <th style="min-width: ' . strlen((string)$key) . 'px;background: #ddd; margin-right: 2px">' . $key . '</th>
                   <td style="max-width: 30%;background: #eee; padding: 0">' . (is_array($value) ? static::eT($value) : static::brText($value)) . '</td>
                </tr>';
      }
    }
    $result .= '</table>';
    return $result;
  }
  
  /**
   * Генерирует HTML-таблицу с ключами.
   *
   * @param array  $args    Массив данных
   * @param array  $fields  Поля для отображения (по умолчанию определяются автоматически)
   * @param string $keyName Название ключа (по умолчанию 'Id')
   * @return string HTML-таблица
   *
   * @example echo VDebugs::eTv($users, ['name', 'email'], 'User ID');
   */
  public static function eTv(array $args, array $fields = [], string $keyName = 'Id'): string
  {
    $out = '<table style="background: #fff;margin: auto">';
    if (empty($fields)) {
      foreach (array_keys(current($args)) as $keyI) {
        $fields[$keyI] = ucfirst($keyI);
      }
    }
    $out .= '<tr style="margin-bottom: 7px"><th>' . $keyName . '</th><th>' . implode('</th><th>', $fields) . '</th></tr>';
    foreach ($args as $keyItem => $items) {
      $out .= '<tr style="margin-bottom: 7px"><td style="background: #eee; padding: 3px 7px">' . ($keyItem + 1) . '</td>';
      foreach (array_keys($fields) as $key) {
        $out .= '<td style="background: #eee; padding: 3px 7px">' . (is_array($items[$key]) ? static::eTv($items[$key]) : static::brText($items[$key])) . '</td>';
      }
      $out .= '</tr>';
    }
    $out .= '</table>';
    return $out;
  }
  
  /**
   * Разбивает длинный текст на строки с переносом.
   *
   * @param string $string Текст для форматирования
   * @param int    $length Максимальная длина строки (по умолчанию 100)
   * @return string Отформатированный текст
   *
   * @example echo VDebugs::brText($longText, 80);
   */
  public static function brText(string $string, int $length = 100): string
  {
    if ($string && strlen($string) > $length) {
      return substr($string, 0, $length) . '<br>' . static::brText(substr($string, $length), $length);
    }
    return $string;
  }
  
  /**
   * Сохраняет строку в файл.
   *
   * @param mixed  $string Строка или массив для сохранения
   * @param string $path   Путь к директории (по умолчанию ROOT_PATH/logs/)
   * @param string $file   Имя файла (по умолчанию debug.txt)
   * @param int    $flags  Флаги для file_put_contents (по умолчанию FILE_APPEND)
   * @return string путь к сохранённому файлу
   *
   * @throws RuntimeException Если не удалось создать директорию
   *
   * @example VDebugs::saveF($logMessage, '/custom/path/', 'custom.log');
   */
  public static function saveF(mixed $string, string $path = ROOT_PATH . 'logs/', string $file = 'debug.txt', int $flags = FILE_APPEND): string
  {
    $filePath = explode('/', pathAs($file, 'url'));
    $path     = pathAs(rtrim($path, '\\/')) . DIRECTORY_SEPARATOR;
    if (count($filePath) > 1) {
      $file = array_pop($filePath);
      $path .= implode(DIRECTORY_SEPARATOR, $filePath) . DIRECTORY_SEPARATOR;
    }
    if (!is_string($string)) {
      $string = static::getArgumentsEchoPlainText($string);
    }
    
    if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
      throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
    }
    
    file_put_contents($path . $file, $string . PHP_EOL, $flags);
    
    return $path . $file;
  }
  
  /**
   * Записывает лог в файл.
   *
   * @param string $message Сообщение для логгирования
   * @param string $file    Имя файла (по умолчанию common)
   * @param string $level   Уровень лога (например, error, info)
   * @param string $prefix  Префикс для имени файла (по умолчанию err)
   * @return void
   *
   * @example VDebugs::log("Ошибка авторизации", "auth", "error");
   */
  public static function log(string $message, string $file = 'common', string $level = 'error', string $prefix = 'err'): void
  {
    static $_message;
    if ($_message !== $message) {
      $_message      = $message;
      $dateFormatted = (new \DateTime())->format('Y-m-d H:i:s');
      $message       = sprintf('[%s] %s: %s', $dateFormatted, $level, $message);
      static::saveF($message, ROOT_PATH . 'logs/', $file . ($prefix ? '.' . $prefix : '') . '.log');
    }
  }
  
  /**
   * Записывает лог в файл JSON.
   *
   * @param string $message Сообщение для логгирования
   * @param string $file    Имя файла (по умолчанию common)
   * @param string $prefix  Префикс для имени файла (по умолчанию err)
   * @return void
   *
   * @example VDebugs::logJson(json_encode($data), "api");
   */
  public static function logJson(string $message, string $file = 'common', string $prefix = 'err'): void
  {
    static::saveF($message, ROOT_PATH . 'logs/', $file . ($prefix ? '.' . $prefix : '') . '.json.log');
  }
  
  public static function logAsJson(): void
  {
    static::saveF(JsonHelper::encode(func_get_args() ?? [], JSON_PRETTY_PRINT), ROOT_PATH . 'logs/', 'debug.json');
  }
  
  /**
   * Сохраняет email в файл.
   *
   * @param string $to      Адрес получателя
   * @param string $subject Тема письма
   * @param string $message Тело письма
   * @param string $headers Заголовки письма
   * @return void
   *
   * @example VDebugs::saveMail("user@example.com", "Test Subject", "Hello!", "From: admin@example.com");
   */
  public static function saveMail(string $to, string $subject, string $message, string $headers): void
  {
    $dir = ROOT_PATH . '../mails';
    if (defined('SAVE_EMAIL_TO_FILE') && is_string(SAVE_EMAIL_TO_FILE)) {
      $dir = SAVE_EMAIL_TO_FILE;
    }
    $dir = rtrim($dir, '/') . '/';
    
    $file = date('Y-m-d_H-i-s_') . substr(microtime(true), -4) . ".txt";
    $out  = "To :\n" . $to . "\n";
    $out  .= "Subject :\n" . $subject . "\n" . "\n";
    $out  .= "Headers :\n" . $headers . "\n";
    $out  .= "Message :\n" . $message . "\n";
    static::saveF($out, $dir, $file);
    chmod($dir . $file, 0755);
  }
  
  /**
   * Отправляет отладочное сообщение на email.
   *
   * @param string $message Тело сообщения
   * @param string $email
   * @return void
   */
  public static function sendDebugEmail(string $message, string $email = 'developer@forumedia.com'): void
  {
    $subject = 'Debug Alert "' . base_url() . '": ' . date('d.m.Y H:i:s');
    $mail = Service::mailer();
    $mail->isHTML();
    $mail->Subject = $subject;
    $mail->msgHTML('<pre>' . $message . '</pre>');
    $mail->addAddress($email);
    $mail->_mail($email);
  }
  
  private static function renderTraceTable(array $rows): string
  {
    $html = "<table style='border-collapse: collapse; font-size: 13px; width: auto; margin: 8px 0;'>";
    
    // Шапка таблицы
    $html .= "<tr style='background: #f0f0f0;'>";
    $html .= "<th style='padding: 4px 6px; border: 1px solid #ccc;'>#</th>";
    $html .= "<th style='padding: 4px 6px; border: 1px solid #ccc;'>File</th>";
    $html .= "<th style='padding: 4px 6px; border: 1px solid #ccc;'>Line</th>";
    $html .= "<th style='padding: 4px 6px; border: 1px solid #ccc;'>Code</th>";
    $html .= "</tr>";
    
    // Содержимое
    foreach ($rows as $row) {
      $html .= "<tr>";
      $html .= "<td style='padding: 2px 4px; border: 1px solid #ccc;'>{$row[0]}</td>";
      $html .= "<td style='padding: 2px 4px; border: 1px solid #ccc;'>{$row[1]}</td>";
      $html .= "<td style='padding: 2px 4px; border: 1px solid #ccc;'>{$row[2]}</td>";
      $html .= "<td style='padding: 2px 4px; border: 1px solid #ccc;'>{$row[3]}</td>";
      $html .= "</tr>";
    }
    
    $html .= "</table>";
    return $html;
  }
  
}
