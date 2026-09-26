<?php

namespace AC\core\system\view;

use Service;

/** Иерархия интерфейсов и поиск файлов шаблонов с сохранением приоритета namespace. */
class TemplateResolver
{
  /**
   * Возвращает устройства от наиболее специализированного к родительскому.
   * @return string[]
   */
  public function getLookupOrder(?string $device = null): array
  {
    $device ??= Service::url()->getTemplatePath();
    return $device === 'widget' ? ['widget', 'site'] : [$device];
  }

  /**
   * Возвращает слои для объединения: значения дочернего интерфейса применяются последними.
   * @return string[]
   */
  public function getMergeOrder(?string $device = null): array
  {
    return array_reverse($this->getLookupOrder($device));
  }

  /**
   * Возвращает только родительские интерфейсы в порядке поиска.
   * @return string[]
   */
  public function getParentTemplates(?string $device = null): array
  {
    return array_slice($this->getLookupOrder($device), 1);
  }

  /**
   * Собирает пути, сохраняя порядок кандидатов внутри каждого устройства.
   * @param callable(string): array $pathsForDevice
   * @return string[]
   */
  public function buildPaths(callable $pathsForDevice, ?string $device = null): array
  {
    $paths = [];
    foreach ($this->getLookupOrder($device) as $template) {
      array_push($paths, ...$pathsForDevice($template));
    }
    return $paths;
  }

  /**
   * Находит первый файл среди кандидатов устройства и его родителей.
   * @param callable(string): array $pathsForDevice
   */
  public function findFile(callable $pathsForDevice, ?string $device = null, string $extension = 'php'): string|false
  {
    $paths = $this->buildPaths($pathsForDevice, $device);
    return $extension === 'php'
      ? Service::locator()->findPriorityPathToFile($paths)
      : Service::locator()->findPriorityPathToFileWithExtension($paths, $extension);
  }

  /** Находит именованный шаблон в app/tpl с учётом родительского интерфейса. */
  public function resolveTemplate(string $name, ?string $device = null): string|false
  {
    return $this->findFile(static fn(string $template): array => [paths()->getTplDir($name, $template)], $device);
  }

  /**
   * Находит явно заданный путь; пути внутри текущего шаблона наследуются от родительского интерфейса.
   * Общие и произвольные пути сохраняют обычные правила FileLocator.
   */
  public function resolvePath(string $file, ?string $device = null): string|false
  {
    $normalizedFile = str_replace('\\', '/', $file);
    $templatePrefix = str_replace('\\', '/', paths()->getTplDir('', $device));
    $candidates = [$file];
    if (str_starts_with($normalizedFile, $templatePrefix)) {
      foreach ($this->getParentTemplates($device) as $parent) {
        $candidates[] = paths()->getTplDir(substr($normalizedFile, strlen($templatePrefix)), $parent);
      }
    }
    return Service::locator()->findPriorityPathToFile($candidates);
  }
}
