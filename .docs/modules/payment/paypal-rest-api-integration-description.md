# PayPal REST API: описание реализации и ожидаемого поведения

Статус: запланировано.

Дата: 2026-08-24.

Связанный технический план: [paypal-rest-api-integration-plan.md](paypal-rest-api-integration-plan.md).

## 1. Назначение документа

Документ объясняет, какие изменения требуются для добавления PayPal REST API v2, как будет выглядеть новый
платёжный процесс для пользователя и администратора и почему реализация включает не только REST-клиент, но также
постоянное состояние платежа, webhook, восстановление после сбоев и защиту от повторных бизнес-операций.

Технический план остаётся основным источником требований. Это описание раскрывает его в форме сценариев и
компонентов реализации.

## 2. Что должно измениться

В Active Court необходимо добавить второй способ взаимодействия с PayPal:

- `legacy_nvp` - существующий Express Checkout через NVP API;
- `rest_v2` - OAuth 2.0, Orders API v2 и Payments API v2.

PayPal остаётся одним платёжным провайдером. Новый режим не должен регистрироваться как отдельный
`OnlineGateway`: для отчётов, счетов и существующей бизнес-логики это по-прежнему PayPal.

Режим выбирается отдельно для каждого контекстного профиля PayPal. Существующие профили без поля `api_mode`
автоматически продолжают использовать `legacy_nvp`.

Основная задача реализации - обеспечить следующие гарантии:

- двойное нажатие кнопки оплаты не создаёт два PayPal Order;
- один PayPal Capture не создаёт два набора бронирований;
- один PayPal Capture не пополняет личный счёт дважды;
- закрытие браузера не приводит к потере подтверждённой оплаты;
- повторные и пришедшие не по порядку webhook не портят состояние;
- timeout после финансового POST не приводит к слепому повторному списанию;
- ошибка письма, счёта или iCal не вызывает возврат денег за уже созданную услугу;
- переключение профиля обратно на NVP не мешает завершению ранее созданных REST-платежей.

## 3. Как будет выглядеть процесс для пользователя

Пользовательский интерфейс в первом REST-релизе принципиально не меняется. JavaScript SDK и Smart Buttons не
требуются. Сохраняется server-side redirect checkout.

Обычный успешный сценарий:

```text
Пользователь выбирает бронирование
-> нажимает кнопку оплаты
-> Active Court создаёт локальную платёжную попытку
-> Active Court создаёт Order в PayPal
-> пользователь перенаправляется на страницу PayPal
-> пользователь подтверждает платёж
-> PayPal возвращает пользователя в Active Court
-> Active Court выполняет Capture
-> Active Court создаёт бронирования или пополняет баланс
-> пользователь получает локализованный результат
```

До обращения к PayPal система сохраняет постоянную запись платёжной попытки. Поэтому продолжение операции не
зависит от PHP-сессии, текущего браузера или сохранённых в форме hidden-полей.

Если пользователь подтвердил оплату и закрыл браузер до возврата на сайт, процесс продолжается асинхронно:

```text
PayPal отправляет webhook
-> webhook проверяется и сохраняется во входящей очереди
-> worker находит платёжную попытку
-> общий orchestrator проверяет или выполняет Capture
-> идемпотентный финализатор создаёт бизнес-результат
```

Повторное открытие return URL должно показывать сохранённый результат и не выполнять повторный Capture или
повторное создание бронирований.

## 4. Как будет выглядеть настройка в админке

В контекстном профиле PayPal появляется выбор режима:

```text
API mode:
- Legacy NVP
- REST API v2
```

Для `legacy_nvp` используются существующие поля:

- API Username;
- API Password;
- API Signature.

Для `rest_v2` администратор выбирает заранее настроенную PayPal REST App и видит:

- окружение `sandbox` или `live`;
- Client ID;
- состояние Client Secret без отображения самого секрета;
- Webhook ID;
- публичный Webhook URL;
- текущую ревизию credentials;
- состояние готовности подключения.

Административный интерфейс должен поддерживать следующие действия:

- проверка OAuth-подключения;
- проверка регистрации webhook и обязательных событий;
- создание новой ревизии credentials;
- явная замена или очистка Client Secret;
- вывод старой ревизии из эксплуатации после завершения связанных платежей.

Client Secret после сохранения не возвращается в HTML. Пустое поле при редактировании означает «оставить
существующее значение». Для очистки требуется отдельное явное действие.

Выбор `rest_v2` остаётся недоступным, пока runtime readiness не подтвердит наличие схемы БД, REST-клиента,
webhook, reconciliation и безопасного хранения Client Secret.

## 5. Версионирование PayPal REST App

REST credentials нельзя хранить как один изменяемый набор значений в контекстном профиле. Между созданием Order
и завершением Capture, Refund или reconciliation администратор может заменить Client ID, Client Secret или
Webhook ID.

Каждая платёжная попытка должна ссылаться на неизменяемую ревизию PayPal REST App:

| Поле | Назначение |
| --- | --- |
| `rest_app_key` | Стабильный внутренний идентификатор REST App |
| `credential_version` | Неизменяемая версия credentials |
| `environment` | `sandbox` или `live` |
| `client_id` | OAuth Client ID |
| `client_secret_encrypted` | Зашифрованный Client Secret |
| `webhook_id` | PayPal Webhook ID этой ревизии |
| `webhook_route_key` | Стабильный ключ публичного webhook-маршрута |
| `active_from`, `retired_at` | Период использования ревизии |

Изменение credentials создаёт новую ревизию. Старую ревизию нельзя удалить, пока существуют связанные с ней
незавершённые попытки, операции или ожидаемые webhook.

Client Secret хранится зашифрованным ключом, находящимся вне БД, либо во внешнем secrets manager. Plaintext в
таблице `config` не является допустимым штатным вариантом для production.

## 6. Постоянная модель платежа

### 6.1. Платёжная попытка

`online_payment_attempts` является центральной записью процесса. Она хранит:

- назначение платежа: бронирование или пополнение;
- выбранный сервером профиль;
- клиента или гостевой бизнес-снимок;
- сумму и валюту;
- PayPal Order ID и Capture ID;
- ревизию PayPal REST App;
- срок жизни return token, attempt и hold;
- состояния Order, Capture и бизнес-операции;
- данные retry и reconciliation;
- безопасную диагностику без secrets и PII.

Состояние PayPal и состояние Active Court разделяются. Например:

```text
Order: COMPLETED
Capture: COMPLETED
Business: fulfillment_pending
```

Такое состояние означает, что деньги уже списаны, но локальное создание бронирований или пополнение ещё не
завершено. Оно должно восстанавливаться reconciliation, а не считаться полностью успешным или неуспешным.

### 6.2. Финансовые операции

`online_payment_operations` содержит отдельные строки для:

- `create_order`;
- `capture`;
- каждого `refund`.

Каждая операция получает постоянный `PayPal-Request-Id`, состояние, provider resource ID, retry metadata и срок
окна идемпотентности. При timeout система продолжает или сверяет ту же операцию, а не создаёт новую.

### 6.3. Элементы попытки

`online_payment_attempt_items` связывает попытку оплаты бронирований с локальными объектами:

```text
attempt_id
temporary_reservation_id
permanent_reservation_id
step_status
```

Связь позволяет восстановить процесс после остановки между отдельными шагами и исключает использование одного
hold двумя платёжными попытками.

### 6.4. Business ledger

Отдельный ledger должен иметь DB-ограничение, гарантирующее уникальность бизнес-результата по Capture ID:

```text
UNIQUE(gateway, provider_capture_id, purpose)
```

Проверки статуса в PHP недостаточно: return, webhook и reconciliation могут одновременно увидеть старое
состояние. Уникальный индекс обеспечивает гарантию на уровне БД.

### 6.5. Webhook inbox

`online_payment_webhook_events` хранит проверенные входящие события:

- уникальный PayPal `event_id`;
- тип события;
- App и credential revision;
- resource IDs и hash payload;
- состояние проверки и обработки;
- retry count и время следующей попытки;
- ограниченно хранимый raw body, если он нужен для replay.

Raw payload не записывается в обычный журнал. Срок хранения, шифрование и правила удаления PII утверждаются до
production.

## 7. Основные программные компоненты

### 7.1. `PaypalRestClient`

Клиент инкапсулирует обращения к PayPal:

```php
createOrder();
getOrder();
captureOrder();
getCapture();
refundCapture();
getRefund();
verifyWebhookSignature();
```

Он также отвечает за OAuth token cache, timeout, TLS, `PayPal-Request-Id`, допустимые retry, нормализацию ошибок
и безопасное логирование `debug_id`.

Рекомендуемый вариант - внутренний интерфейс и реализация поверх `Service::curl()`. Использование официального
SDK возможно только после определения Composer/deployment-пути и проверки покрытия необходимых операций.

### 7.2. `PaypalPaymentRestController`

Новый контроллер обслуживает REST route-сегмент `rest`. Существующий `PaypalPaymentOldController` продолжает
обслуживать `legacy_nvp`.

Сервер выбирает route по сохранённому профилю и mapping:

```text
legacy_nvp -> old
rest_v2 -> rest
```

Значения `api_mode`, purpose, суммы, валюты и профиля из браузера не считаются доверенными.

### 7.3. `PaypalRestPaymentOrchestrator`

Return, webhook worker и reconciliation используют одну реализацию:

```text
загрузить attempt и credential revision
-> получить блокировку attempt
-> проверить состояние, hold, сумму и валюту
-> выполнить или сверить Capture с сохранённым request ID
-> проверить вложенный Capture
-> вызвать идемпотентный бизнес-финализатор
-> сохранить терминальный результат
```

Обработчики return и webhook не должны иметь собственные варианты capture-логики.

### 7.4. Бизнес-финализаторы и outbox

Завершение платежа разделяется на независимые части:

1. Core fulfillment: создание бронирований или пополнение баланса.
2. Счёт и связанные документы.
3. Письма, iCal, door codes и уведомления.

Core fulfillment защищается ledger и DB-идемпотентностью. Вторичные эффекты повторяются отдельно,
предпочтительно через outbox. Их ошибка не отменяет уже корректно созданную услугу.

## 8. Создание PayPal Order

При нажатии кнопки оплаты сервер:

1. Проверяет CSRF, rate limit и право пользователя на checkout.
2. Получает purpose, профиль, клиента, hold, сумму и валюту из серверных данных.
3. Повторно использует активную attempt при двойном submit.
4. Создаёт attempt, items и операцию `create_order` до HTTP-запроса.
5. Назначает постоянный `PayPal-Request-Id`.
6. Вызывает Orders API с `intent=CAPTURE`.
7. Передаёт локальный attempt ID в `purchase_units[0].custom_id`.
8. Сохраняет Order ID до redirect.
9. Разрешает redirect только на allowlisted PayPal host.

Сумма форматируется сервером с учётом точности валюты. Произвольный API URL из конфигурации не используется:
base URL вычисляется только по `sandbox` или `live`.

## 9. Return и Capture

`return_url` содержит криптографически случайный локальный token. В БД хранится только его hash и срок действия.
PayPal query-параметр `token` сам по себе не является достаточным доказательством связи с attempt.

После return orchestrator:

1. Находит attempt и сохранённую credential revision.
2. Проверяет локальный token, владельца, state и hold.
3. При необходимости получает Order через GET.
4. Выполняет Capture с сохранённым request ID.
5. Находит ожидаемый объект в `purchase_units[].payments.captures[]`.
6. Проверяет Capture ID, статус, сумму и валюту.
7. Запускает fulfillment только при Capture `COMPLETED`.

Top-level `Order.status=COMPLETED` не считается достаточным подтверждением оплаты: вложенный Capture может иметь
статус `DECLINED` или `PENDING`.

При Capture `PENDING` бизнес-финализация не выполняется. Состояние проверяется через webhook и reconciliation.

## 10. Оплата бронирований

До Capture временные бронирования являются hold. Непосредственно перед финансовым запросом проверяются:

- принадлежность hold текущей попытке;
- серверная сумма и валюта;
- срок действия hold;
- отсутствие уже созданных постоянных бронирований;
- отсутствие другого Capture или finalizer для attempt.

На время Capture hold получает lease, чтобы не истечь во время сетевого запроса.

После подтверждённого Capture `COMPLETED`:

```text
зарезервировать Capture ID в business ledger
-> создать постоянные бронирования
-> записать созданные ID в attempt items
-> зафиксировать core fulfillment
-> отдельно создать счёт
-> отдельно отправить email, iCal и door codes
```

Поскольку часть исторических таблиц использует MyISAM, нельзя полагаться на общий транзакционный rollback.
Каждый важный шаг фиксируется в durable ledger до внешних side effects.

Для Capture `PENDING` до production требуется определить максимальный срок удержания корта. Если hold нельзя
сохранить, а Capture позднее стал `COMPLETED`, система должна исключить конфликтующую бронь и выполнить только
доказанно безопасную компенсацию либо направить операцию в `manual_review`.

## 11. Пополнение личного счёта

Attempt хранит выбранный сервером пакет пополнения, `local_client_id` и неизменяемый бизнес-снимок. Асинхронная
обработка не использует `current_client_data` из сессии.

После Capture `COMPLETED`:

```text
ledger резервирует Capture ID
-> создаётся или активируется приходный счёт
-> PrivateAccount/deposit выполняется с business idempotency key
-> счёт закрывается после подтверждённого deposit
-> attempt получает business_completed
```

Совместимый формат `accounts.price_info` сохраняется:

```text
paypal|{order_id}|{capture_id}|tk:{rawurlencode(profile_type_key)}
```

Сегмент `rest_v2` в это поле не добавляется: существующий parser ошибочно классифицирует дополнительный сегмент
как Payone. Версия интеграции хранится в attempt и related data.

## 12. Webhook

Webhook endpoint является публичным HTTPS endpoint на порту 443 и не требует пользовательской сессии. Для
каждой REST App используется стабильный случайный `webhook_route_key`, однозначно определяющий App, credential
revision и Webhook ID.

Порядок обработки:

```text
получить raw body до JSON middleware
-> проверить обязательные transmission headers
-> выбрать App и revision по route key
-> вызвать verify-webhook-signature
-> сохранить проверенное событие в durable inbox
-> быстро вернуть PayPal 2xx
-> обработать событие асинхронным worker
```

Webhook не выполняет отдельную capture-логику. `CHECKOUT.ORDER.APPROVED` ставит в очередь общий orchestration
flow, а Capture `COMPLETED` ставит в очередь общий финализатор.

События могут приходить повторно и не по порядку. Worker выполняет только допустимые монотонные переходы. При
противоречии фактическое состояние запрашивается у PayPal через GET.

## 13. Reconciliation

Reconciliation является обязательной production-задачей восстановления. Она периодически ищет:

- Order `APPROVED` без завершённого return;
- Capture в состоянии `PENDING` или с неизвестным результатом;
- Capture `COMPLETED` без завершённого core fulfillment;
- Refund `PENDING`;
- истёкшие approval и hold;
- старые незавершённые попытки;
- исчерпанные или противоречивые операции.

После исчерпания безопасных retry попытка переводится в `manual_review` и создаётся alert. Reconciliation
продолжает обрабатывать старые REST-попытки даже после переключения профиля обратно на `legacy_nvp`.

До production необходимо определить scheduler, защиту от параллельного запуска, batch size, backoff, SLA,
владельца очереди `manual_review` и канал алертов.

## 14. Поведение при сбоях

### Двойной submit

Конкурентные запросы используют одну активную attempt либо блокируются UNIQUE/CAS. Второй PayPal Order не
создаётся.

### Timeout во время Create или Capture

Неизвестный результат сохраняется. В допустимом окне выполняется retry с тем же `PayPal-Request-Id` либо GET
Order/Capture. Новый финансовый POST с новым ID вслепую не выполняется.

### Повторный webhook

UNIQUE по `event_id` предотвращает повторную обработку одного события.

### Capture `PENDING`

Бронирования и пополнение не создаются. Попытка ожидает webhook или проверки через GET.

### Capture завершён, PHP-процесс остановился

Reconciliation обнаруживает Capture `COMPLETED` и незавершённый business status, после чего повторяет
идемпотентный финализатор.

### Ошибка счёта или уведомления

Повторяется только соответствующий side effect. Платёж и созданная услуга не отменяются.

### Неопределённый частичный бизнес-результат

Автоматический refund не выполняется. Попытка направляется в `manual_review`, потому что возврат после уже
созданной услуги может привести к финансовой потере.

## 15. Изменения в существующих компонентах

- `core/modules/payment/paypal/models/PaypalProfileContext.php`: добавить `api_mode`, `rest_app_key` и условные правила полей.
- `core/modules/payment/config/PaypalConfig.php`: mapping `legacy_nvp -> old`, `rest_v2 -> rest`, readiness и выбор credential revision.
- `core/modules/config/controllers/admin/online_payment/PaymentProfileSection.php`: объединять отсутствующие скрытые поля с предыдущими значениями до валидации.
- `core/modules/config/controllers/admin/online_payment/PaypalSection.php`: добавить PayPal-specific validation.
- `core/modules/config/views/admin/online_payment/paypal/_form.php`: добавить API mode, App, environment, write-only secret, webhook URL и readiness.
- `core/modules/config/views/admin/online_payment/paypal/_list.php`: показывать mode, environment, App и revision без secret.
- `core/modules/reservations/views/common/pp_form.php`: выбирать route по серверному mapping профиля.
- `core/modules/payment/structure/structure.site.php`: исправить ошибочный маршрут `payment_paypal` на PayPal route.
- `app/lang/admin/de/de.ini`, `app/lang/admin/en/en.ini`, `app/lang/admin/ru/ru.ini`: добавить локализованные подписи и сообщения.
- Добавить REST-клиент, DTO, exceptions, таблицы, модели, orchestrator, webhook worker, reconciliation и outbox.

Legacy-компоненты и NVP-реквизиты не удаляются.

## 16. Рекомендуемый порядок реализации

### Этап 0. Зафиксировать решения

До изменения кода определить:

- входит ли в MVP только бронирование или также пополнение;
- политику обычного и технического refund;
- модель REST App и ротации credentials;
- способ хранения ключа шифрования Client Secret;
- public webhook URL и route mapping;
- разрешён ли Capture по `CHECKOUT.ORDER.APPROVED` без return;
- SLA hold для Capture `PENDING`;
- scheduler, worker, manual-review owner и alert channel;
- retention raw webhook и PII;
- REST-клиент или SDK и deployment-путь.

Рекомендуемый первый MVP:

- сначала оплата бронирований;
- server-side redirect checkout;
- прямой клиент через `Service::curl()`;
- отдельная versioned-модель REST App;
- Capture из return, webhook и reconciliation только через общий orchestrator;
- технический refund только при доказанном отсутствии или успешной компенсации core fulfillment;
- сохранение текущих бизнес-правил обычной отмены;
- сохранение legacy NVP.

### Этап 1. Schema, credentials и выключенная конфигурация

Создать таблицы, versioned credentials, шифрование, административный UI и connection checks. REST пока нельзя
активировать для профиля.

### Этап 2. REST-клиент и state machine

Реализовать OAuth, Orders, Capture, Refund, signature verification, request IDs, lock/CAS, допустимые переходы,
retry и error mapping.

### Этап 3. Webhook и восстановление

Реализовать route mapping, signature verification, durable inbox, worker, общий orchestrator, reconciliation,
алерты и `manual_review`.

### Этап 4. Оплата бронирований

Подключить attempt и hold, Create/redirect/return/cancel, Capture verification, ledger, идемпотентный finalizer и
отдельные retries вторичных эффектов.

### Этап 5. Пополнение баланса

Добавить независимый от сессии flow, UNIQUE по Capture, идемпотентный deposit и совместимый `price_info`.

### Этап 6. Тестирование

Проверить REST contract, конкуренцию, webhook, recovery, pending, crash points, compensation, security,
совместимость legacy и реальные PayPal sandbox-сценарии.

### Этап 7. Поэтапное включение

Сначала развернуть код при `legacy_nvp`, затем включить Sandbox для одного профиля, после этого один
низкорисковый production-профиль и только затем расширять использование.

## 17. Rollback

Rollback не должен удалять таблицы или отключать REST routes. Он состоит из двух частей:

1. Kill switch запрещает создание новых REST Order.
2. Профиль переключается на `legacy_nvp` для новых оплат.

Старые credential revisions, webhook, worker и reconciliation продолжают работать до завершения всех
non-terminal REST attempts и окончания принятого окна webhook/reconciliation.

## 18. Признаки готовой реализации

Реализацию можно считать готовой, если выполняются следующие условия:

- существующие профили продолжают работать через NVP без миграции;
- REST нельзя включить при неполной runtime readiness;
- два submit создают одну активную attempt и один PayPal Order;
- return, webhook и reconciliation используют один сериализованный flow;
- потерянный return восстанавливается в согласованный SLA;
- fulfillment разрешён только по Capture `COMPLETED` с совпадающими ID, суммой и валютой;
- Capture `PENDING` не создаёт бизнес-результат;
- один Capture создаёт один набор броней или одно пополнение;
- повторные и out-of-order события не создают дубли и не откатывают состояние;
- ошибки вторичных эффектов повторяются отдельно;
- Client Secret не присутствует в HTML, logs, audit payload и exceptions;
- `accounts.price_info`, отчёты и PDF продолжают определять платёж как PayPal;
- legacy NVP и Payone не получают поведенческих регрессий;
- старые REST-попытки завершаются после rollback;
- старые non-terminal операции создают alert и попадают под операционный контроль.
