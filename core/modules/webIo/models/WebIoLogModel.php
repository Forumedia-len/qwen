<?php

namespace AC\core\modules\webIo\models;

use AC\app\config\WebIOStateConfig;
use AC\core\system\db\Query;

class WebIoLogModel
{
  protected static $table = 'light_logs';

  public $log_id;
  public $date;
  public $mode;
  public $output;
  public $status;
  public $result;
  public $comment;
  public $webio_id;

  public function __construct()
  {
  }

  /**
   *
   * @return bool
   */
  public function save()
  {
    Query::sqlQuery(
      'insert into ' . Query::tableName(self::$table) . ' set
				    date = :date,
            mode = :mode,
				    output = :output,
				    status = :status,
				    result = :result,
				    comment = :comment,
				    webio_id = :webio_id',
      array(
        ':date'     => $this->date,
        ':mode'     => $this->mode,
        ':output'   => $this->output,
        ':status'   => $this->status,
        ':result'   => $this->result,
        ':comment'  => $this->comment,
        ':webio_id' => $this->webio_id
      ),
      false
    );

    return true;
  }

  public function load($data = array())
  {
    foreach ($data as $key => $value) {
      if (property_exists($this, $key)) {
        $this->{$key} = $value;
      }
    }
  }

  public static function getLogList($area_id, $date)
  {
    $outputs = array('0');
    foreach (WebIOStateConfig::instance()->getAreaPorts($area_id) as $output) {
      if (!in_array($output->webio_id, $outputs)) {
        $outputs[] = (string)$output->webio_id;
      }
    }

    return Query::sqlQuery(
      'SELECT * FROM ' . Query::tableName(self::$table) . ' WHERE DATE_FORMAT(date, \'%Y-%m-%d\') = :date and webio_id in (' . implode(
        ', ',
        $outputs
      ) . ') ORDER BY date',
      array(':date' => $date)
    );
  }

}