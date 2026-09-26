<?php

namespace AC\core\system\deploy;

use AC\app\config\DeployConfig;
use AC\core\system\helpers\LogHelper;

class DeployFile
{
  private $dir;
  private $fileName = 'deploy';

  /**
   * @var DeployConfig
   */
  private $_config;

  public function __construct($dir = null, $fileName = null)
  {
    $this->dir = $dir ? : pathAs(ROOT_PATH . paths()->getTmpFilesDir());

    if ($fileName !== null) {
      $this->fileName = $fileName;
    }

    $this->_config = config('deploy');
  }

  /**
   * @return string
   */
  public function getDir()
  {
    return $this->dir;
  }

  /**
   * @param string $dir
   */
  public function setDir($dir)
  {
    $this->dir = $dir;
  }

  /**
   * @return string
   */
  public function getFileName()
  {
    return $this->fileName;
  }

  /**
   * @param string $fileName
   */
  public function setFileName($fileName)
  {
    $this->fileName = $fileName;
  }

  public function deploy()
  {
    switch ($this->_config->type) {
      case 'ftp':
      case 'ftps':
        $this->deployFtp();
        break;
      case 'sftp':
      case 'ssh':
        $this->deploySsh();
        break;
    }
  }

  public function deploySsh()
  {
    $conn = ssh2_connect($this->_config->host, $this->_config->port);
    ssh2_auth_password($conn, $this->_config->username, $this->_config->password);
    ssh2_scp_send($conn, $this->dir . $this->fileName, $this->_config->remote_dir . '/' . $this->fileName, 0644);
    ssh2_disconnect($conn);
  }

  public function deployFtp()
  {
    $ftp   = $this->_config->type == 'ftps' ?
      ftp_ssl_connect($this->_config->host, $this->_config->port, "30") :
      ftp_connect($this->_config->host, $this->_config->port, "30");
    $login = ftp_login($ftp, $this->_config->username, $this->_config->password);
    ftp_chdir($ftp, $this->_config->remote_dir);
    ftp_pasv($ftp, true);
    if(!ftp_put($ftp, $this->fileName, $this->dir . $this->fileName, FTP_BINARY)) {
      LogHelper::mailSend('developer@forumedia.com', '', 'Не получилось загрузить клиентов с арены ' . HOST_NAME);
    }
    ftp_close($ftp);
  }
}