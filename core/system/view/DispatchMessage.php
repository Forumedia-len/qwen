<?php
namespace AC\core\system\view;

class DispatchMessage
{
  private $message = array();

  /** Добавить сообщение об ошибке
   *
   * @param array|string $error_message - сообщение об ошибке
   */
  public function setErrorMessage($error_message)
  {
    if (is_array($error_message)) {
      $messages = $error_message;
    } else {
      $messages[] = $error_message;
    }
    foreach ($messages as $message) {
      $this->setDispatchMessage($message, 'error');
    }
  }

  /** Есть сообщения данного типа
   *
   * @param $type
   *
   * @return bool
   */
  public function isMessageByType($type)
  {
    return isset($this->message[$type]);
  }

  /** Установить сообщение по его типу
   *
   * @param        $message
   * @param string $type
   */
  public function setDispatchMessage($message, $type = 'notify')
  {
    $this->message[$type][] = $message;
  }

  /** Получить массив сообщений заданого типа
   *
   * @param string $type
   *
   * @return array
   */
  public function getMessageByType($type = 'notify')
  {
    return $this->isMessageByType($type) ? $this->message[$type] : array();
  }

  /** Получить сообщения данного типа в html формате
   *
   * @param string $type
   *
   * @return string
   */
  public function renderMessageAsHtmlByType($type = 'notify')
  {
    $_message = '';
    foreach ($this->getMessageByType($type) as $message) {
      $_message .= '<span class="message message' . '-' . $type . '">' . $message . '</span>';
    }

    return $_message;
  }

  /** Получит все типы сообщений
   * @return array
   */
  public function getTypeMessage()
  {
    return array_keys($this->message);
  }

  /** Добавить сообщение об ошибке по его коду
   *
   * @param array|string $error_code - код ошибки
   */
  public function setErrorCode($error_code)
  {
    if (is_array($error_code)) {
      $codes = $error_code;
    } else {
      $codes[] = $error_code;
    }
    foreach ($codes as $code) {
      $this->setDispatchMessage($this->getErrorCodesToString($code), 'error');
    }
  }


  /** Выдать все сообщения об ошибках
   * @return string
   */
  public function getErrors()
  {
    return $this->renderMessageAsHtmlByType('error');
  }

  /** Выдать сообщение об ошибке по его коду
   *
   * @param string $error_code
   *
   * @return string
   */
  private function getErrorCodesToString($error_code)
  {
    switch ((string)$error_code) {
      case '1': // Дата или время на валидны для данной площадки
        $error = lang('date_time is unavailable for this area', 'message_error');
        break;
      case '2':
        $error = lang('period unavailable', 'message_error');
        break;
      case '3':
        $error = lang('Holiday');
        break;
      case '4' :
        $error = lang('period blocked', 'message_error');
        break;
      case '5':
        $error = lang('period already ordered', 'message_error');
        break;
      case '6':
        $error = lang('period already requested by ticket', 'message_error');
        break;
      case 'error_area_id': // нет такого id площадки
        $error = lang('area_id invalid', 'message_error');
        break;
      case 'error_o_n_f': // нет такого бронирования
        $error = lang('order not found', 'message_error');
        break;
      case 'error_authorization': // ошибка авторизации
        $error = lang('Please log in first!', 'message_error');
        break;
      case 'error_not_data': // не все данные пришли
        $error = lang('not all input data received', 'message_error');
        break;
      case 'null_interval': // не выбрано время
        $error = lang('Please select a time first', 'message_error');
        break;
      case 's1':
        $error = lang('stock not found', 'message_error');
        break;
      case 'p1':
        return lang('error_p1_r', 'message_error');
        break;
      default:
        $error = $error_code;
    }

    return $error;
  }

  public function getMessagesAsString()
  {
    $message = '';
    foreach ($this->getTypeMessage() as $type) {
      $message .= $this->renderMessageAsHtmlByType($type);
    }

    return $message;
  }

  public function saveInSession()
  {
    \Service::session()->set('messages', $this->getMessagesAsString());
  }

  public function getOfSession()
  {
    $message = \Service::session()->get('messages');
    $this->clearMessageOfSession();

    return $message;
  }

  public function clearMessageOfSession()
  {
    \Service::session()->delete('messages');
  }

  /**
   * Проверяем есть ли сообщения в сессии или добавленные
   * @return bool
   */
  public function issetMessage()
  {
    return \Service::session()->exists('messages') || count($this->message);
  }


}