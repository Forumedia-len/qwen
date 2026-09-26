<?php
/**
 * @var array<string, \AC\core\modules\news\entities\dto\NewsDto> $langsData        Данные для всех языков (ключ - код языка, значение - NewsDto)
 * @var string                                                    $action           URL действия формы
 * @var string                                                    $default_language Язык по умолчанию
 * @var int|null                                                  $main_news_id     ID основной новости
 */

use AC\core\modules\news\entities\dto\NewsDto;

$isNewElement = !$langsData[$default_language]->getId();
?>

<form action="<?= site_url(Service::structure()->getPageHrefByKey('news')) ?>" method="post" id="newsForm">
  <input type='hidden' name='action' value="<?= ($isNewElement ? 'create' : 'update') ?>">
  <input type='hidden' name='main_news_id' value="<?= $main_news_id ?? '' ?>">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <caption>
      <?php
      // Используем переиспользуемый компонент табов языков в caption
      $this->addPathToView(paths()->getTplDir('views', 'admin'));
      echo $this->render('base/_language_tabs', [
        'languages'        => array_keys($langsData),
        'active_language'  => $default_language,
        'content_selector' => '.lang-field',
        'container_class'  => '',
        'container_id'     => 'news-language-tabs'
      ]);
      ?>
    </caption>

    <?php
    foreach ($langsData as $langCode => $langData):
      $isActive = $langCode === $default_language; ?>
      <!-- Заголовок -->
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <th colspan="2">
          <input type='hidden' name="news[<?= $langCode ?>][news_id]" value="<?= $langData->getId() ?>">
          <?= ($isNewElement ? lang('title_create') : lang('title_update')) ?> - <?= strtoupper($langCode) ?>
        </th>
      </tr>

      <!-- Дата -->
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <td class='light'><?= lang('Date') ?>:</td>
        <td class='light <?= (!$isActive ? 'not-use-for-lang-data' : '') ?>'>
          <?php if ($isActive): ?>
            <input type="text"
                   name="news[<?= $langCode ?>][date]"
                   class="input wide datepicker"
                   data-lang-field="date"
                   data-lang="<?= $langCode ?>"
                   value="<?= $langData->getDate('d.m.Y') ?>"/>
          <?php else: ?>
            <input type='hidden'
                   name="news[<?= $langCode ?>][date]"
                   value="<?= $langData->getDate('d.m.Y') ?>"
                   class="lang-field lang-<?= $langCode ?>"
                   data-lang-field="date"
                   data-lang="<?= $langCode ?>">
            <span class="lang-shared-display"
                  data-lang-display="date"
                  data-lang="<?= $langCode ?>">
              <?= $langData->getDate('d.m.Y') ?>
            </span>
            <small>(<?= lang('from main news', 'news') ?>)</small>
          <?php endif; ?>
        </td>
      </tr>

      <!-- Заголовок -->
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <td class="dark"><?= lang('Title') ?>:</td>
        <td class="dark">
          <input type="text" name="news[<?= $langCode ?>][title]" class="input wide"
                 value="<?= htmlspecialchars($langData->getTitle()) ?>"/>
        </td>
      </tr>

      <!-- Содержание -->
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <td class="light"><?= lang('Content') ?>:</td>
        <td class="light">
          <textarea name="news[<?= $langCode ?>][content]"
                    class="ckeditor input wide"><?= htmlspecialchars($langData->getContent() ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </td>
      </tr>

      <!-- Публикация -->
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <td class='light'><?= lang('Publish') ?>:</td>
        <td class='light <?= (!$isActive ? 'not-use-for-lang-data' : '') ?>'>
          <?php if ($isActive): ?>
            <input type='checkbox'
                   name="news[<?= $langCode ?>][publish]"
                   data-lang-field="publish"
                   data-lang="<?= $langCode ?>"
                   value='1'<?= $langData->isPublished() ? ' checked' : '' ?>>
          <?php else: ?>
            <input type='hidden'
                   name="news[<?= $langCode ?>][publish]"
                   value="<?= $langData->getPublished() ?>"
                   class="lang-field lang-<?= $langCode ?>"
                   data-lang-field="publish"
                   data-lang="<?= $langCode ?>">
            <span class="lang-shared-display"
                  data-lang-display="publish"
                  data-lang="<?= $langCode ?>">
              <?= $langData->isPublished() ? lang('Yes') : lang('Not') ?>
            </span>
            <small>(<?= lang('from main news', 'news') ?>)</small>
          <?php endif; ?>
        </td>
      </tr>
      <!-- Информация о создании перевода -->
      <?php if (!$isActive && !$langData->getId()): ?>
      <tr class="lang-field lang-info-row lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <td colspan="2" class="light not-use-for-lang-data" style="text-align: center; ">
          <?= lang('Translation will be created when you save the form', 'news') ?>
        </td>
      </tr>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- Кнопки управления формой -->
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= ($isNewElement ? lang('button_create') : lang('button_update')) ?>" class="button">
        &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
  </table>
</form>
<script>
  $(document).ready(function () {
    const triggerNativeEvent = (element, eventName) => {
      if (!element) {
        return;
      }

      if (typeof Event === 'function') {
        element.dispatchEvent(new Event(eventName, {bubbles: true}));
        return;
      }

      const fallbackEvent = document.createEvent('Event');
      fallbackEvent.initEvent(eventName, true, true);
      element.dispatchEvent(fallbackEvent);
    };

    if ($.fn.datepicker) {
      const datepickerOptions = {
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd.mm.yy',
        yearRange: '-10:+1',
        onSelect: function () {
          triggerNativeEvent(this, 'input');
          triggerNativeEvent(this, 'change');
        }
      };

      $('.datepicker').each(function () {
        $(this).datepicker(datepickerOptions);
      });
    }

    (function () {
      const mainLang = <?= json_encode($default_language) ?>;
      const langYesLabel = <?= json_encode(lang('Yes')) ?>;
      const langNotLabel = <?= json_encode(lang('Not')) ?>;

      const sharedFieldsConfig = {
        date: {
          events: ['change', 'keyup', 'input'],
          getValue(element) {
            return element.value || '';
          },
          formatDisplay(value) {
            return value;
          }
        },
        publish: {
          events: ['change'],
          getValue(element) {
            return element.checked ? '1' : '0';
          },
          formatDisplay(value) {
            return value === '1' ? langYesLabel : langNotLabel;
          }
        }
      };

      Object.keys(sharedFieldsConfig).forEach((fieldName) => {
        const mainElement = document.querySelector('[data-lang-field="' + fieldName + '"][data-lang="' + mainLang + '"]');

        if (!mainElement) {
          return;
        }

        const {events, getValue, formatDisplay} = sharedFieldsConfig[fieldName];

        const syncSharedField = () => {
          const value = getValue(mainElement);

          document.querySelectorAll('[data-lang-field="' + fieldName + '"]').forEach((element) => {
            if (element === mainElement) {
              return;
            }

            if (element.type === 'checkbox') {
              element.checked = value === '1';
            } else {
              element.value = value;
            }
          });

          document.querySelectorAll('[data-lang-display="' + fieldName + '"]').forEach((element) => {
            if (element.dataset.lang === mainLang) {
              return;
            }

            element.textContent = formatDisplay(value);
          });
        };

        events.forEach((eventName) => {
          mainElement.addEventListener(eventName, syncSharedField);
        });

        syncSharedField();
      });
    })();
  });
</script>
