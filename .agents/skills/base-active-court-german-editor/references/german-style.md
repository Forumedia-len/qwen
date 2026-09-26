# Немецкие формулировки base-active-court

Основание: 18 файлов `app/lang/**/de/*.ini`, просмотренных 22 сентября 2026 года.
Это словарь предпочтений по существующим текстам, а не утверждение, что весь корпус вычитан носителем языка.
Для правки используй актуальные значения и контекст вывода. Путь + секция + ключ надёжнее номера строки.
Если до первой секции нет заголовка, проектный парсер относит строку к `[common]`.

## Карта источников

Все пути в таблице начинаются с `app/lang/`.

| Источник | Для чего использовать |
|---|---|
| `common/de/de.ini` | Базовые кнопки, бронирование, платежи, игроки, ошибки и уведомления. |
| `common/de/mess.de.ini` | Старые подписи и составные фразы; сверять с новым контекстом, не брать весь текст как норму. |
| `common/de/dictionary.de.ini` | Дни недели и месяцы: `Montag`, `Januar`, `März` и т. д. |
| `common/de/reservations.de.ini` | Конкретные ошибки выбора даты, времени, места и способа оплаты. |
| `common/de/exception.de.ini` | Пользовательские HTTP-сообщения и отдельные технические диагностические строки. |
| `common/de/cookie.de.ini` | Названия cookie-категорий и действий; длинные тексты не считать нормативным образцом. |
| `common/de/message.de.ini`, `site/de/message.de.ini` | Тексты до/после регистрации и бронирования, варианты `messages_*`. |
| `site/de/de.ini`, `site/de/structure.de.ini` | Сайт, обычная и пошаговая регистрация, личные данные, Guthaben, навигация. |
| `touch/de/{de,message.de,structure.de}.ini` | Терминал, подтверждение часа, карта, оплата на месте. |
| `admin/de/{de,message.de,structure.de}.ini` | Администрирование, счета, Abos, клиенты и настройки. |
| `widget/de/de.ini` | Собственные подписи виджета; остальное наследуется из common и site. |
| `display/de/de.ini` | Короткие подписи информационного дисплея. |

## Обращение зависит от сценария

|---|---|---|
- **Обычное бронирование** — `common/de/de.ini`, `[show_order]`, `Select a payment method`: `Wählen Sie eine Zahlungsart aus`
  Источник / вывод: `Sie` — исходное обращение общего интерфейса.

- **Пошаговая регистрация** — `site/de/de.ini`, `[registration_fields]`, `parameter_registration_field_encash_step_reg`: `Wie möchtest Du bezahlen?`
  Источник / вывод: Сохранять неформальное обращение в этом сценарии.

- **Первый шаг регистрации** — `common/de/de.ini`, `[common]`, `reg_step1`: `1. Dein Account`
  Источник / вывод: Дополнительное подтверждение `du` именно для пошаговой регистрации.

- **Некоторые письма/сообщения активации** — `site/de/message.de.ini`, `[messages_default]`, `info_text_after_registration_invoice_aut_active`:
  `Vielen Dank für deine Anmeldung an unserem Buchungssystem.`
  Источник / вывод: Не превращать последовательно неформальный текст в формальный без причины.

- **Вариант mc_arena** — `common/de/reservations.de.ini`, `[message_error_mc_arena]`: `du/deine`; `site/de/message.de.ini`, `[messages_mc_arena]`:
  `Sie/Ihre`
  Источник / вывод: Название варианта не позволяет выбрать обращение для всех его сообщений сразу.


В `info_text_after_registration_invoice_man_active` встречаются `deine` и `Sie` в одном сообщении.
В `site/de/de.ini`, `[show_order]` рядом с `Sie` встречаются `wähle` и `Klicke`.
Это несогласованность исходников, а не правило смешивать формы. При правке конкретного сообщения выбери
одно обращение по сценарию; не переноси случайный `du` на весь экран.

## Словарь и границы синонимов

|---|---|---|
- **Бронировать** — `Buchen`, короткое действие `Jetzt buchen`, объект `Buchung`
  Источник / вывод: `common/de/de.ini`: `[common] Book`, `[show_order] Book now`

- **Резервирование** — `Reservierung` сохранять в подтверждениях и админке, где это уже название объекта
  Источник / вывод: `common/de/de.ini`: `[order] Your reservation`; `admin/de/de.ini`: `[reservations] New reservation`

- **Отмена редактирования** — `Abbrechen`; существующий `Cancel = Abbruch` не заменять глобально
  Источник / вывод: `common/de/de.ini`: `[common] cancel`, `Cancel`

- **Сторнирование** — `Stornieren`, `Reservierung stornieren`, `Stornierung`
  Источник / вывод: `admin/de/de.ini`: `[accounts] Cancel`, `[reservations] Cancel reservation`

- **Удаление** — `Löschen` — когда действие действительно удаляет запись
  Источник / вывод: `common/de/de.ini`: `[common] button_remove`

- **Изменение** — `Bearbeiten` для редактирования; `Verändern` сохранять в существующих кнопках и разделах
  Источник / вывод: `common/de/de.ini`: `[common] Edit`, `button_update`

- **Корт** — `Platz/Plätze`, при уточнении `Tennisplatz`, `Freiplatz`, `Halle` по контексту
  Источник / вывод: `common/de/de.ini`: `[common] Place`, `Places`; `admin/de/de.ini`: `[config] ..._open`, `..._close`

- **Абонемент** — `Abo/Abos` в кратком интерфейсе, `Abonnement` допустимо в развёрнутом сообщении
  Источник / вывод: `common/de/de.ini`: `[common] Abo`, `Abos`; `common/de/reservations.de.ini`: `[message_error] reservation_invalid_ticket`

- **Денежный остаток** — `Guthaben`, `Guthabenstand`; не переводить как кредит/заём
  Источник / вывод: `common/de/de.ini`: `[common] Credit balance`; `site/de/de.ini`: `[prepayment] your_credit`

- **Пополнение** — `Guthaben aufladen`; текущий `Guthaben-Konto auffüllen` допустим в старом пояснении
  Источник / вывод: `site/de/de.ini`: `[show_order] click_here_to_top_up_your_credit`; `[prepayment] you_can_use_this_to_top_up_your_credit_account`

- **Зачисление / кредит-нота** — `Gutschrift`, отдельно от остатка `Guthaben`
  Источник / вывод: `admin/de/de.ini`: `[accounts] To the credit`, `[accounts_view] REGISTER`

- **Ваучер** — `Gutschein/Gutscheine`, действие `Gutschein einlösen`
  Источник / вывод: `site/de/de.ini`: `[prepayment] redeem_voucher`, `my_vouchers`

- **Внесено / доступно к использованию** — Различать `Zahlbetrag` и принятый проектом `Abspielbetrag`; не объединять их
  Источник / вывод: `admin/de/de.ini`: `[config_online_payment] label_price_real`, `label_price_account`

- **Способ оплаты** — `Zahlungsart/Zahlungsarten`
  Источник / вывод: `common/de/de.ini`: `[common] Payment method`, `Payment methods`

- **Наличные** — `Barzahlung`; плательщик `Barzahler`
  Источник / вывод: `common/de/de.ini`: `[common] Cash payment`; `site/de/de.ini`: `[show_order] cash_payers`

- **Счёт и списание** — `Rechnung`, `Auf Rechnung`; в выборе регистрации `Rechnung/Lastschriftverfahren`, в step_reg `Lastschrift`
  Источник / вывод: `site/de/de.ini`: `[show_order] invoice`, `on_bill`; `[registration_fields] ...encash_invoice`, `...encash_invoice_step_reg`

- **Итоговые суммы** — `Gesamtpreis` в бронировании, `Rechnungsbetrag` в счёте, `Gesamtbetrag` для суммы
  Источник / вывод: `site/de/de.ini`: `[show_order] show_order_block_prices_sum_price`; `admin/de/de.ini`: `[accounts_view] Invoice amount`,
  `[accounts] Total amount`

- **Скидки и дополнения** — `Rabatt`, `Zuschlag`, `Sonderpreis`, `Buchungs-Optionen` как существующее название
  Источник / вывод: `site/de/de.ini`: `[show_order] show_order_block_prices_*`; `common/de/de.ini`: `[common] Booking options`

- **Игроки и клиенты** — `Spieler`, `Mitspieler`, `Hauptspieler`, `Gastspieler`; `Kunde` для клиентской записи
  Источник / вывод: `common/de/de.ini`: `[common] Client`, `Guest_player`, `[show_order] main_player`; `site/de/de.ini`: `[show_order]
  show_order_after_comment_text`

- **Членство** — `Mitglied`, `Nichtmitglied`, `Gast` — разные категории
  Источник / вывод: `common/de/de.ini`: `[common] Member`, `Non-member`, `Guest`

- **Расписание и список броней** — `Stundenplan` в админке; `Terminplan`/`Belegungsplan` в пояснениях; `Buchungsübersicht` — список броней
  Источник / вывод: `admin/de/structure.de.ini`: `[structure] schedule`; `common/de/reservations.de.ini`: `[message_error] reservation_invalid_time`;
  `site/de/structure.de.ini`: `[structure] clients_reservations_title`

- **Пароль и фамилия** — Старые формы: `Kennwort`, `Familienname`; step_reg: `Passwort`, `Nachname`
  Источник / вывод: `site/de/de.ini`: `[registration_fields] ...password`, `...surname`, `...password_step_reg`, `...surname_step_reg`

- **Вход / регистрация** — `Anmelden` и `Anmeldung` встречаются в обоих контекстах; смысл определять по экрану
  Источник / вывод: `admin/de/de.ini`: `[auth] logIn`; `site/de/structure.de.ini`: `[structure] registration_title`

- **Личный кабинет** — `Mein Konto` для widget
  Источник / вывод: `widget/de/de.ini`: `[widget] Personal account`

- **Услуги** — `Licht`, `Heizung`, `Netz`
  Источник / вывод: `common/de/de.ini`: `[common] Light`, `Heating`, `Net`


`Buchung` и `Reservierung` в этом корпусе перекрываются по смыслу. Не объявляй `Reservierung` неоплаченной,
а `Buchung` оплаченной только по слову. Сначала проверь модель и действие.
То же относится к `Account`: в исходных ключах это может быть счёт, в пользовательском тексте — учётная запись.

## Банк фраз

Формулировки ниже приведены дословно. Подставляй их только при совпадении смысла и параметров.
Исключения, лимиты и правила оплаты из других строк не переносить в новый сценарий.

| Фраза | Источник в `app/lang/` |
|---|---|
| `Jetzt für {{sum_price}} {{currency}} buchen` | `site/de/de.ini`, `[show_order]`, `show_order_button_submit` |
| `Wählen Sie eine Zahlungsart aus` | `common/de/de.ini`, `[show_order]`, `Select a payment method` |
| `Bitte wählen Sie zuerst eine Uhrzeit aus` | `common/de/de.ini`, `[message_error]`, `Please select a time first` |
| `Vielen Dank für Ihre Buchung.` | `common/de/de.ini`, `[order]`, `Thank you for your booking.` |
| `Buchung abgeschlossen` | `common/de/de.ini`, `[message_success]`, `Booking completed` |
| `Ihre Buchung wurde storniert` | `common/de/de.ini`, `[payment_pp]`, `Your_booking_has_been_cancelled` |
| `Ungültiges Datum. Wählen Sie ein Datum im Buchungskalender.` | `common/de/reservations.de.ini`, `[message_error]`, `reservation_invalid_date` |
| `Ungültige Buchungsdaten. Prüfen Sie die Formularfelder und versuchen Sie es erneut.` | `common/de/reservations.de.ini`, `[message_error]`, `reservation_invalid_input` |
| `Bitte versuchen Sie es später erneut.` | `common/de/exception.de.ini`, `[http_error]`, `try_again_later` |
| `Ein Bestätigungscode wurde an Ihre E-Mail-Adresse {{email}} gesendet.` | `admin/de/de.ini`, `[users]`, `A confirmation code has been sent to your e-mail address` |
| `Bitte halten Sie die Karte ans Lesegerät!` | `touch/de/de.ini`, `[message_error]`, `please_hold_the_card_up_to_the_reader` |
| `Gutschein einlösen` | `site/de/de.ini`, `[prepayment]`, `redeem_voucher` |
| `Kalender schließen` | `widget/de/de.ini`, `[widget]`, `Close calendar` |

## Что не превращать в предпочтение

Это примеры локальной редакторской нормализации, а не поручение исправить все исходные файлы.

| В корпусе | При редактировании соответствующего текста |
|---|---|
| `not = "nien"` в `common/de/de.ini` | `nein`, если нужен ответ «нет». |
| `Bitte wählen sie ggf. ein Zahlungsart aus` в `site/de/de.ini` | `Bitte wählen Sie ggf. eine Zahlungsart aus.` Сохранить условность `ggf.`, если она нужна по сценарию. |
| `Sind Sie, sicher, dass Sie Ihre Reservierung stornieren möchten?` в `common/de/de.ini` | `Sind Sie sicher, dass Sie Ihre Reservierung stornieren möchten?` |
| `Geben Sie Ihr Benutzername und Kennwort ein!` в `common/de/de.ini` | `Bitte geben Sie Ihren Benutzernamen und Ihr Kennwort ein.` |
| `Viel Spaß beim spielen!` в `site/de/de.ini` | `Viel Spaß beim Spielen!` |
| `ABO's`, `Abo's`, ``Abo`s`` в админке | Для новых обычных форм `Abos`, для родительного падежа `des Abos`; регистр UI учитывать отдельно. |
| `Spielplatz` в старых административных подписях | Для нового текста о спортивном корте обычно `Platz`; старую навигацию не переименовывать попутно. |
| `Color`, `Close`, `order not found` внутри немецких файлов | Проверить назначение строки; это не предпочтение писать пользовательский интерфейс на английском. |

В корпусе встречаются и `Straße`, и `Strasse`, а также `schließt` и `schliessen`.
По этим отдельным написаниям нельзя объявлять весь проект швейцарской локалью.
Для новых общих строк выбрана норма немецкого Германии; при явном требовании площадки следовать её варианту.
