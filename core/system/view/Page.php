<?php

namespace AC\core\system\view;

class Page extends View
{
  public $_content;
  public $_header;
  public $_footer;
  public $_style;
  public $menu;
  public $_class = 'order';
  public $_back_href = false;
  public $show_date;
  public $mobile_calendar;
  public $title_block;
  public $icon;
  
  
  public function __construct()
  {
    parent::__construct();
    $this->setInfoBlockContent();
  }

  public function __get($name)
  {
    if (isset($this->{$name})) {
      return $this->{$name};
    } else {
      return false;
    }

  }

  public function __set($name, $value = null)
  {
    if ($value != null) {
      $this->{$name} = $value;

      return true;
    }

    return false;
  }
  
  public function __isset(string $name): bool
  {
    // TODO: Implement __isset() method.
  }
  
  public function setInfoBlockContent($header = '', $content = '', $footer = '', $back_href = false, $style = '')
  {
    $this->_header    = $header;
    $this->_content   = $content;
    $this->_footer    = $footer;
    $this->_back_href = $back_href;
    $this->_style     = $style;
  }

  /**общие функции, типа тем-билдер
   *
   * @param null|string $header
   * @param null|string $footer
   * @param null|string $back_href
   * @param null|string $content
   * @param null|string $style
   * @param null|string $class
   *
   * @return string
   */
  public function getInfoBlockContent($header = null, $content = null, $footer = null, $back_href = null, $style = null, $class = null)
  {
    $params = array(
      'header'    => ($header !== null) ? $header : $this->_header,
      'content'   => ($content !== null) ? $content : $this->_content,
      'footer'    => ($footer !== null) ? $footer : $this->_footer,
      'back_href' => ($back_href !== null) ? $back_href : $this->_back_href,
      'style'     => ($style !== null) ? $style : $this->_style,
      'class'     => ($class !== null) ? $class : ($this->_class ? $this->_class : 'order-success'),
    );

    if ($this->key == 'error') {
      $params = array(
        'header'    => ($header !== null) ? $header : lang('text_error'),
        'content'   => $this->message->getErrors(),
        'footer'    => ($footer !== null) ? $footer : '',
        'back_href' => ($back_href !== null) ? $back_href : $this->_back_href,
        'style'     => ($style !== null) ? $style : $this->_style,
        'class'     => 'order-error',
      );
    }
    $this->template = '';

    return $this->render('base/info_block_content', $params);
  }

}