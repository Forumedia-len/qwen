<?php

namespace AC\mapi\act\repositories;

use const AC\mapi\pathRepositories;

class WorkWith
{
  public function getRepository($repositoryName, array $config = []): ?BaseRepository
  {
    if (($fileName = $this->hasRepository($repositoryName)) && require_once($fileName)) {
      if(($className = \Service::locator()->getClassname($fileName)) && class_exists($className, false)) {
        return new $className($config);
      }
    }
    return null;
  }
  
  public function hasRepository($repositoryName, $path = 'workWith'): ?string
  {
    $fileName = pathAs(pathRepositories . $path . '/' . ucfirst($repositoryName) . '.php');
    if (file_exists($fileName)) {
      return realpath($fileName);
    }
    return null;
  }
}