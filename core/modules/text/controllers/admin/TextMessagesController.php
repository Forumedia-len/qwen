<?php

namespace AC\core\modules\text\controllers\admin;

use AC\app\config\LangConfig;
use AC\app\controllers\AdminController;
use AC\core\modules\areas\models\AreasModel;
use AC\core\system\language\IniFiles;
use Service;

/**
 * Административный контроллер для редактирования текста системных сообщений.
 */
class TextMessagesController extends AdminController
{
  protected        $useBaseModel = false;
  protected string $pageKey      = 'config_messages';

  /**
   * @var array<string, array<string, IniFiles>>
   */
  protected array $iniFiles = [];


  protected LangConfig $langConfig;

  protected string $device       = 'site';
  protected string $courtType    = 'common';
  protected array  $types        = [];
  protected array  $messagesTree = [];

  public function __construct($action = false, $runController = true, $segments = [])
  {
    parent::__construct($action, $runController, $segments);
    $this->langConfig = config('lang');
    $this->initIniFiles();
    $this->types = $this->loadTypes();
    $this->initializeFilters();
  }

  public function setViewParams()
  {
    parent::setViewParams();
    $this->view->addJsFile('visualeditor/ckeditor', 'admin', null, 'cdn');
    $this->view->key  = $this->pageKey;
    $this->view->href = $this->buildActionHref();
    $this->view->setContentMenu($this->buildContentMenu());
  }

  public function show()
  {

    return $this->render('messages/list', [
      'groups'           => $this->buildGroupsForView(),
      'localMenu'        => $this->buildLocalMenu(),
      'currentTypeTitle' => $this->getCurrentTypeTitle(),
    ]);
  }

  public function edit()
  {

    $section = $this->sanitizeSection($this->getSegmentByKey('section') ?? Service::request()->_('section'));
    $key     = $this->sanitizeKey($this->getSegmentByKey('key') ?? Service::request()->_('key'));

    if (!$section || !$key) {
      $this->view->addMessage(lang('input data error', 'message_error'), 'error');

      return $this->redirectDefaultAction();
    }

    $messageItem = $this->getMessageDefinition($key);
    if (!$messageItem) {
      $this->view->addMessage(lang('input data error', 'message_error'), 'error');

      return $this->redirectDefaultAction();
    }
    if (($formMessages = Service::request()->_post('messages')) && is_array($formMessages) && !empty($formMessages)) {
      if ($this->saveMessageValues($section, $key, $formMessages)) {
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      } else {
        $this->view->addMessage(lang('input data error', 'message_error'), 'error');
      }
    }

    return $this->render('messages/form', [
      'message'          => $messageItem,
      'values'           => $this->getMessageValues($section, $key),
      'languages'        => $this->langConfig->getActiveLanguages(),
      'defaultLanguage'  => $this->langConfig->getDefault(),
      'paramsMessage'    => $messageItem['variables'] ?? [],
      'formAction'       => site_url($this->buildActionHref('edit', ['section' => $section, 'key' => $key])),
      'backHref'         => site_url($this->buildActionHref()),
      'currentTypeTitle' => $this->getCurrentTypeTitle(),
      'device'           => $this->device,
    ]);
  }

  protected function buildGroupsForView(): array
  {
    $groupsForView = [];

    foreach ($this->getGroupedMessages() as $groupKey => $messages) {
      $rows = [];
      foreach ($messages as $message) {
        if (empty($message['showAccordingToConditions'])) {
          continue;
        }
        $rows[] = [
          'name'     => $message['name'],
          'image'    => $message['image'] ?? null,
          'editHref' => $this->buildActionHref('edit', [
            'section' => $message['section'] . '_' . $this->courtType,
            'key'     => $message['key'],
          ]),
        ];
      }

      if ($rows) {
        $groupsForView[] = [
          'title'    => lang('title_group_' . $groupKey, 'config_messages'),
          'messages' => $rows,
        ];
      }
    }

    return $groupsForView;
  }

  protected function buildContentMenu(): array
  {
    $menu = [
      'site' => [
        'href'   => $this->buildActionHref('show', ['device' => 'site']),
        'title'  => lang('type_device_site', 'config_messages'),
        'active' => $this->device === 'site',
      ],
    ];

    if (defined('TOUCHSCREEN') && TOUCHSCREEN) {
      $menu['touch'] = [
        'href'   => $this->buildActionHref('show', ['device' => 'touch']),
        'title'  => lang('type_device_touch', 'config_messages'),
        'active' => $this->device === 'touch',
      ];
    }

    return $menu;
  }

  protected function buildLocalMenu(): string
  {
    ob_start(); ?>
    <div class="list-type list-type-content">
      <?php foreach ($this->types as $alias => $type) { ?>
        <div class="item-type <?= ($alias === $this->courtType ? 'active' : '') ?>">
          <a href="<?= $this->buildActionHref('show', ['courtType' => $alias]) ?>">
            <?= $type->title ?>
          </a>
        </div>
      <?php } ?>
    </div>
    <?php

    return ob_get_clean();
  }

  protected function getCurrentTypeTitle(): string
  {
    return $this->types[$this->courtType]->title ?? lang('general', 'config_messages');
  }

  protected function initIniFiles(): void
  {
    foreach (['site', 'touch'] as $device) {
      foreach ($this->langConfig->getActiveLanguages() as $language) {
        $path = paths()?->getLangAppDir($language . '/message.' . $language, $device);
        if ($path) {
          $this->iniFiles[$device][$language] = new IniFiles(pathAs($path));
        }
      }
    }
  }

  protected function loadTypes(): array
  {
    return array_merge(
      ['common' => (object)['title' => lang('general', 'config_messages')]],
      AreasModel::selectActiveType()
    );
  }

  protected function initializeFilters(): void
  {
    $this->device    = $this->sanitizeDevice($this->getSegmentByKey('device') ?? Service::request()->_('device', $this->device));
    $this->courtType = $this->sanitizeCourtType($this->getSegmentByKey('courtType') ?? Service::request()->_('courtType', $this->courtType));
  }

  protected function sanitizeDevice(?string $device): string
  {
    $device = strtolower((string)$device);

    if ($device === 'touch' && (!defined('TOUCHSCREEN') || !TOUCHSCREEN)) {
      return 'site';
    }

    return in_array($device, ['site', 'touch'], true) ? $device : 'site';
  }

  protected function sanitizeCourtType(?string $courtType): string
  {
    $courtType = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)$courtType));

    return array_key_exists($courtType, $this->types) ? $courtType : 'common';
  }

  protected function buildActionHref(string $action = 'show', array $params = []): string
  {
    $segments = ['text', 'messages'];
    if ($action !== 'show') {
      $segments[] = $action;
    }

    foreach (array_merge($this->getDefaultParams(), $params) as $key => $value) {
      if ($value === null || $value === '') {
        continue;
      }
      $segments[] = $key;
      $segments[] = $this->sanitizePathValue($value);
    }

    return implode('/', $segments);
  }

  protected function sanitizePathValue(?string $value): string
  {
    return rawurlencode(preg_replace('/[^a-z0-9_\-\.]/i', '', (string)$value));
  }

  protected function getGroupedMessages(): array
  {
    $tree = Service::structure('messages', 'translation')->getTreeByGroup();

    return $tree[$this->device][$this->courtType] ?? [];
  }

  protected function getMessageDefinition(string $key): ?array
  {
    if (!$this->messagesTree) {
      $this->messagesTree = Service::structure('messages')->getTree();
    }

    return $this->messagesTree[$key] ?? null;
  }

  protected function sanitizeSection(?string $section): ?string
  {
    if (!$section) {
      return null;
    }

    $section = preg_replace('/[^a-z0-9_]/i', '', $section);

    return $section ?: null;
  }

  protected function sanitizeKey(?string $key): ?string
  {
    if (!$key) {
      return null;
    }

    $key = preg_replace('/[^a-z0-9_]/i', '', $key);

    return $key ?: null;
  }

  protected function getIniStorage(string $language): ?IniFiles
  {
    if (isset($this->iniFiles[$this->device][$language])) {
      return $this->iniFiles[$this->device][$language];
    }

    return $this->iniFiles['site'][$language] ?? null;
  }

  protected function getMessageValues(string $section, string $key): array
  {
    $values = [];
    foreach ($this->langConfig->getActiveLanguages() as $language) {
      $values[$language] = '';
      if($storage           = $this->getIniStorage($language)) {
        $values[$language] = match (true) {
          $storage->has($section, $key)                    => $storage->read($section, $key, ''),
          preg_match('/^(?<base_section>messages_[^_]+)_(?<type_id>\d+)$/', $section, $matches)
          && $storage->has($matches['base_section'], $key) => $storage->read($matches['base_section'], $key, ''),
          $storage->has('messages_default', $key)          => $storage->read('messages_default', $key, ''),
          default                                          => '',
        };
      }
    }

    return $values;
  }

  protected function saveMessageValues(string $section, string $key, array $formMessages): bool
  {
    $updated = false;
    foreach ($this->langConfig->getActiveLanguages() as $language) {
      if (!isset($formMessages[$language])) {
        continue;
      }
      $storage = $this->getIniStorage($language);
      if (!$storage) {
        continue;
      }
      $content = (string)($formMessages[$language]['content'] ?? '');
      $content = $content === '' ? ' ' : str_replace(["\n", "\r"], '', $content);

      // При сохранении создаем секцию, если её не существует
      $storage->write($section, $key, $content);
      $storage->updateFile();
      $updated = true;
    }

    return $updated;
  }

  protected function getDefaultParams(): array
  {
    return [
      'device'    => $this->device,
      'courtType' => $this->courtType,
    ];
  }
}

