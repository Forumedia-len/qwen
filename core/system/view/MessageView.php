<?php

namespace AC\core\system\view;

class MessageView
{
  /**
   * @var DispatchMessage
   */
  protected $message = false;

  public function __construct()
  {
    $this->message = new DispatchMessage();
  }

  /**
   * Добавить сообщение
   *
   * @param        $message - добавляемое сообщение
   * @param string $type    тип сообщения, по умолчанию "уведомление"
   */
  public function addMessage($message, $type = 'notify')
  {
    $this->message->setDispatchMessage($message, $type);
  }

  /**
   *  Получить все сообщения добавленные ранее, в формате строки
   * @return string
   */
  public function getMessages()
  {
    $message = $this->message->getMessagesAsString();
    if (!$message) {
      $message = $this->message->getOfSession();
    }

    return $message;
  }

  /**
   *  Получить все сообщения типа ошибка добавленные ранее, в формате строки
   * @return string
   */
  public function getMessagesTypeError()
  {
    return $this->message->renderMessageAsHtmlByType('error');
  }

  /**
   *  Получить все сообщения типа ошибка добавленные ранее, в формате строки
   * @return string
   */
  public function getMessagesTypeIsNotError()
  {
    $message = '';
    foreach ($this->message->getTypeMessage() as $type) {
      if ($type !== 'error') {
        $message .= $this->message->renderMessageAsHtmlByType($type);
      }
    }

    return $message;
  }

  /**
   *  Добавить несколько в сообщении одного типа и назначение им типа
   *
   * @param array  $messages
   * @param string $type
   */
  public function addMessages($messages, $type = 'notify')
  {
    foreach ($messages as $message) {
      $this->addMessage($message, $type);
    }
  }

  /** Проверить есть сообщения
   * @return bool
   * @var bool|string $type
   */
  public function issetMessages($type = false)
  {
    if ($type) {
      return $this->message->isMessageByType($type);
    }

    return $this->message->issetMessage();
  }

  /**
   * Сохранить сообщения в сессии
   */
  public function saveMessageInSession()
  {
    $this->message->saveInSession();
  }

  public function getMessageOfSession()
  {
    return $this->message->getOfSession();
  }

}