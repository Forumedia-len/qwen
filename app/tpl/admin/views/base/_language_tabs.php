<?php
/**
 * Переиспользуемый компонент табов для переключения языков
 *
 * @var array  $languages        Список доступных языков (ключ - код языка, значение - LanguageDto или массив)
 * @var string $active_language  Активный язык (если не указан, используется default_language)
 * @var string $content_selector Селектор для контента табов (например, '.news-tab-content', '.product-tab-content')
 * @var string $container_class  Дополнительный CSS класс для контейнера
 * @var string $container_id     ID контейнера (для уникальности, если используется несколько компонентов на странице)
 */
$content_selector = $content_selector ?? '.language-tab-content';
$container_class  = $container_class ?? '';
$container_id     = $container_id ?? 'language-tabs-' . uniqid();
?>
<?php if (count($languages) > 1): ?>
  <style>
    /**
     * Универсальные стили для табов языков
     * Определяются только один раз, даже если компонент используется несколько раз на странице
     */
    <?php if (!isset($GLOBALS['_language_tabs_styles_loaded'])): ?>
    .language-tabs-container {
      display: flex;
      justify-content: flex-start;
      margin-bottom: 0;
    }

    /* Стили для caption с табами */
    caption .language-tabs-container {
      margin-bottom: 0;
      padding: 0;
    }

    /* Универсальный класс для контента табов - можно переопределить через content_selector */
    .language-tab-content {
      display: none;
    }

    .language-tab-content.active {
      display: block;
    }

    /* Стили для элементов формы с привязкой к языкам */
    .lang-field {
      display: none;
    }

    /* Скрытые input всегда скрыты */
    input.lang-field[type='hidden'] {
      display: none !important;
    }

    /* Строки таблицы показываются только когда активны */
    tr.lang-field.active {
      display: table-row;
    }

    tr.lang-field.active td {
      display: table-cell;
    }

    tr.lang-field.lang-info-row {
      display: none;
    }

    tr.lang-field.lang-info-row.active {
      display: table-row;
    }

    .not-use-for-lang-data {
      color: #555;
      font-style: italic;
    }

    <?php $GLOBALS['_language_tabs_styles_loaded'] = true; ?>
    <?php endif; ?>
  </style>

  <div class="language-tabs-container <?= $container_class ?>" id="<?= $container_id ?>"
       data-content-selector="<?= $content_selector ?>">
    <div class="list-type list-type-content">
      <?php foreach ($languages as $langCode):
        $isActive = $langCode === $active_language;
        ?>
        <div class="item-type <?= $isActive ? 'active' : '' ?>">
          <a href="#"
             class="language-tab-link"
             data-lang="<?= $langCode ?>"
             onclick="switchLanguageTab('<?= $langCode ?>', '<?= $container_id ?>'); return false;">
            <?= strtoupper($langCode) ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    /**
     * Универсальная функция для переключения табов языков
     * Определяется только один раз, даже если компонент используется несколько раз на странице
     * @param {string} langCode - Код языка для переключения
     * @param {string} containerId - ID контейнера табов (опционально)
     */
    if (typeof window.switchLanguageTab === 'undefined') {
      window.switchLanguageTab = function (langCode, containerId) {
        // Определяем контейнер табов
        var container = containerId ? document.getElementById(containerId) : document.querySelector('.language-tabs-container');
        if (!container) {
          return;
        }

        // Получаем селектор контента из data-атрибута или используем значение по умолчанию
        var contentSelector = container.getAttribute('data-content-selector') || '.language-tab-content';

        // Скрываем все элементы с классами языков
        document.querySelectorAll(contentSelector).forEach(function (element) {
          element.classList.remove('active');
        });

        // Убираем активный класс у всех item-type в текущем контейнере
        container.querySelectorAll('.item-type').forEach(function (item) {
          item.classList.remove('active');
        });

        // Показываем элементы выбранного языка
        document.querySelectorAll(contentSelector + '.lang-' + langCode).forEach(function (element) {
          element.classList.add('active');
        });

        // Добавляем активный класс к item-type
        var activeLink = container.querySelector('.language-tab-link[data-lang="' + langCode + '"]');
        if (activeLink) {
          activeLink.closest('.item-type').classList.add('active');
        }
      };
    }
  </script>
<?php endif; ?>

