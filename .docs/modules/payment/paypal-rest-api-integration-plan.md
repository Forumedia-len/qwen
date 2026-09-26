# Внедрение PayPal REST API с сохранением legacy-интеграции

> **Статус:** запланировано  
> **Область:** модуль `payment`  
> **Последняя проверка:** 2026-08-24  
> **Связанный код:** `core/modules/payment/`

## 1. Цель и обязательные свойства решения

Добавить для PayPal второй, выбираемый вариант подключения:

- `legacy_nvp` - существующий Express Checkout через NVP API;
- `rest_v2` - OAuth 2.0, Orders API v2 и Payments API v2.

Выбор выполняется отдельно для каждого контекстного профиля PayPal (`paypal`, `paypal|...`).
Переключатель основного онлайн-провайдера `paypal|payone` сохраняет текущее назначение: он выбирает провайдера,
а не версию PayPal API.

Первый REST-релиз сохраняет server-side redirect checkout: сервер создаёт Order, получает ссылку подтверждения и
перенаправляет пользователя на PayPal. JavaScript SDK и Smart Buttons не являются условием перехода на REST API.

До включения `rest_v2` в production обязательны:

- постоянная платёжная попытка, не зависящая от пользовательской сессии;
- версионированная привязка попытки к PayPal REST App и credentials;
- безопасное хранение Client Secret;
- один идемпотентный orchestration flow для return, webhook и reconciliation;
- webhook inbox, worker/reconciliation, алерты и очередь `manual_review`;
- DB-гарантия, что один Capture ID создаёт не более одной бизнес-операции;
- сохранение legacy NVP и совместимости существующих отчётов и счетов.

## 2. Что реализовано сейчас

### 2.1. Конфигурация

- Основной онлайн-провайдер выбирается через `payment.online_gateway_primary` в `OnlineGatewayService`.
- PayPal поддерживает глобальный и контекстные профили в таблице `config`: `paypal`, `paypal|{context}`.
- Профиль содержит `api_username`, `api_password`, `api_signature`, `paypal_use`.
- `PaypalConfig::$useVersion` жёстко установлен в `old`.
- `PaypalConfig::checkData()` проверяет только legacy-реквизиты.
- `PaymentProfileSection` использует статическую обязательность полей и превращает отсутствующие POST-поля
  в пустые значения. Простого скрытия неактивной группы полей в форме недостаточно.
- Административная форма PayPal рассчитана только на NVP-реквизиты.

### 2.2. Текущий платёжный поток

Форма `core/modules/reservations/views/common/pp_form.php` отправляется на:

```text
payment/paypal/{useVersion}/checkDetails
```

При `useVersion = old` используется `PaypalPaymentOldController`:

1. `SetExpressCheckout` создаёт Express Checkout token.
2. Пользователь перенаправляется на PayPal.
3. После возврата вызывается `GetExpressCheckoutDetails`.
4. Локально создаются бронирования или выполняется пополнение баланса.
5. `DoExpressCheckoutPayment` завершает платёж.
6. В локальные данные записываются PayPal token и `TRANSACTIONID`.

Поддерживаются два назначения:

- `payPerGame` - оплата бронирований;
- `replenishmentBalance` - пополнение личного счёта.

### 2.3. Ограничения и подтверждённые риски

- Состояние операции в значительной степени хранится в сессии и недоступно асинхронному обработчику.
- `payment_data`, `typeDetails`, `configTypeKey` и ID временных броней приходят из браузера.
  Для REST они являются только недоверенными подсказками, а не источником суммы, назначения или профиля.
- Локальная бизнес-операция выполняется до `DoExpressCheckoutPayment`. Компенсационный откат не атомарен.
- Постоянного idempotency key и общей сериализации повторных callback нет.
- `reservations_tmp_paypal` использует MyISAM. Общая транзакция конвертации временных броней невозможна.
- `reservations_tmp_paypal.reference` и `accounts.price_info` не заменяют модель платёжной попытки.
- Текущие методы финализации используют сессию и `current_client_data`. Их нельзя вызывать из webhook без
  сохранённого бизнес-снимка.
- Ошибка создания онлайн-счёта сейчас считается вторичной и не отменяет уже созданные брони и оплату. Поэтому
  автоматический refund нельзя запускать на любую ошибку общего финализатора.
- `OnlineGatewayService::parseOnlinePaymentInvoicePriceInfo()` считает любой дополнительный сегмент перед
  `|tk:...` способом Payone. Запись `rest_v2` четвёртым сегментом ошибочно классифицирует PayPal как Payone.
- В application worktree не обнаружены `composer.json` и `composer.lock`. Подключение SDK требует
  отдельного решения по зависимостям и deployment.
- `core/modules/payment/structure/structure.site.php` содержит подтверждённую ошибку:
  `payment_paypal` указывает на `payment/payone`.
- Пользователю может показываться внутреннее транспортное сообщение. REST-поток должен возвращать только
  локализованную безопасную ошибку и локальный номер операции.

## 3. Рекомендуемая архитектура

### 3.1. Разделение провайдера и протокола

В контекстный профиль PayPal добавить:

```text
api_mode = legacy_nvp | rest_v2
```

`paypal_rest` не добавляется как новый `OnlineGateway`. Для остальной системы это PayPal с прежними
кодами, правилами показа и отчётами. Меняется только адаптер связи с провайдером.

Значение по умолчанию для существующих профилей и профилей без нового поля:

```text
legacy_nvp
```

Переключение профиля влияет только на создание новых попыток. Уже созданные REST-попытки всегда завершаются
через REST по сохранённой ревизии приложения, даже после rollback профиля на NVP.

### 3.2. Профиль и версия PayPal REST App

Поля контекстного профиля:

- `api_mode`;
- `paypal_use`;
- NVP-поля `api_username`, `api_password`, `api_signature`;
- для `rest_v2` - ссылка `rest_app_key` на настроенную PayPal REST App.

Рекомендуется не дублировать REST credentials во всех контекстных профилях. Нужна отдельная versioned-модель
PayPal REST App:

| Поле | Назначение |
| --- | --- |
| `rest_app_key` | Стабильный внутренний идентификатор App |
| `credential_version` | Неизменяемая ревизия настроек |
| `environment` | `sandbox` или `live` |
| `client_id` | OAuth Client ID |
| `client_secret_encrypted` | Client Secret, зашифрованный ключом вне БД |
| `webhook_id` | PayPal Webhook ID, не является секретом |
| `webhook_route_key` | Стабильный случайный ключ маршрута до App/revision |
| `active_from` / `retired_at` | Управление ротацией и drain |

Если credentials всё же остаются внутри `config`, профиль обязан поддерживать те же неизменяемые ревизии.
Простого `profile_type_key + environment` недостаточно: администратор может заменить Client ID, secret или
Webhook ID между Create, Capture, Refund и reconciliation.

Правила административной формы:

- обязательность полей зависит от `api_mode`;
- отсутствующие поля скрытой/неактивной группы объединяются с предыдущими значениями до валидации;
- очистка credential выполняется только отдельным явным действием;
- Client Secret после сохранения не возвращается в HTML даже частично; пустое поле означает «оставить прежний»;
- Webhook ID и Client ID можно показывать администратору read-only;
- старую ревизию нельзя удалить, пока существуют связанные non-terminal попытки;
- изменение режима и credentials попадает в аудит конфигурации.

API base URL вычисляется только из `environment`:

- sandbox: `https://api-m.sandbox.paypal.com`;
- live: `https://api-m.paypal.com`.

Произвольный API URL запрещён. Это исключает ошибочную конфигурацию и SSRF.

### 3.3. Серверный REST-клиент

Рекомендуется `PaypalRestClient` поверх `Service::curl()` и внутреннего интерфейса. Клиент должен
инкапсулировать:

- `POST /v1/oauth2/token`;
- `POST /v2/checkout/orders`;
- `GET /v2/checkout/orders/{order_id}`;
- `POST /v2/checkout/orders/{order_id}/capture`;
- `GET /v2/payments/captures/{capture_id}`;
- `POST /v2/payments/captures/{capture_id}/refund`;
- `GET /v2/payments/refunds/{refund_id}`;
- `POST /v1/notifications/verify-webhook-signature`;
- при проверке настройки - чтение зарегистрированного webhook и сверку URL/events.

Клиент также отвечает за:

- OAuth token cache по `rest_app_key + credential_version + environment`;
- обновление token с запасом относительно `expires_in`;
- фиксированные connect/read timeouts и ограничения размера ответа;
- TLS 1.2+ и проверку сертификата;
- заголовки `PayPal-Request-Id` и `Prefer`;
- нормализованные ошибки без утечки body пользователю;
- сохранение `debug_id`, HTTP-кода и PayPal error name в безопасном журнале.

Официальный `paypal/paypal-server-sdk` является альтернативой только после определения Composer/deployment
пути и проверки покрытия всех нужных Orders, Payments и webhook operations. Контроллеры в любом случае зависят
от внутреннего интерфейса, а request IDs и состояния хранятся локально.

### 3.4. Постоянная модель данных

Все новые таблицы создаются как InnoDB с принятой `utf8mb4` collation. Денежные значения хранятся как
`DECIMAL`/строки фиксированной точности, не `float`.

#### `online_payment_attempts`

| Группа полей | Минимальные данные |
| --- | --- |
| Идентичность | `id`, `gateway=paypal`, `integration=rest_v2`, `purpose` |
| Контекст | `profile_type_key`, `rest_app_key`, `credential_version`, `environment` |
| Клиент | `local_client_id`, immutable payer/business snapshot с ограниченным PII |
| Сумма | `amount`, `currency`, версия серверного расчёта |
| Return | `return_state_hash`, `state_expires_at`, `attempt_expires_at` |
| PayPal | `provider_order_id` UNIQUE, `provider_capture_id` UNIQUE nullable |
| Статусы | `provider_order_status`, `provider_capture_status`, `business_status` |
| Конкурентность | `lock_version`, `last_transition_source` |
| Reconciliation | `retry_count`, `next_retry_at`, `last_attempt_at`, `last_reconciled_at` |
| Диагностика | `error_code`, `provider_debug_id`, timestamps |

`provider_*_status` и `business_status` являются разными state machine. Старое или пришедшее не по
порядку webhook-событие не может откатить более новое состояние. При конфликте выполняется GET/reconciliation.

Рекомендуемые бизнес-состояния:

```text
created
approval_pending
approved
capture_requested
capture_pending
captured
fulfillment_pending
business_completed
user_cancelled
expired
failed
refund_pending
refunded
manual_review
```

#### `online_payment_operations`

Отдельная строка для `create_order`, `capture` и каждого `refund`:

- постоянный `request_id` с UNIQUE;
- provider resource ID и status;
- amount/currency для refund;
- срок idempotency window;
- HTTP/result metadata;
- retry count, `next_retry_at`, started/completed timestamps.

Это даёт явные `refund_request_id`, Refund ID и refund status, а также позволяет сверить неизвестный результат
после timeout.

#### `online_payment_attempt_items` и fulfillment ledger

Для бронирований нужна нормализованная связь:

- attempt ID;
- ID временной брони;
- ID созданной постоянной брони;
- состояние шага;
- UNIQUE, исключающий использование одного hold двумя попытками.

Для обоих назначений нужна DB-гарантия бизнес-идемпотентности, например ledger с UNIQUE по
`gateway + provider_capture_id + purpose`. Для пополнения эта уникальность должна охватывать фактическую
ledger/deposit операцию, а не только проверку `business_status` до вызова.

#### `online_payment_webhook_events`

Минимальные поля:

- PayPal `event_id` UNIQUE и event type;
- `rest_app_key` и `credential_version` из route mapping;
- resource IDs и `payload_hash`;
- verification/processing status;
- attempt count, `next_retry_at`, `processed_at`;
- raw body byte-for-byte в защищённом хранилище с ограниченным сроком хранения, если он нужен для inbox/replay.

Raw payload не пишется в обычный журнал. Срок хранения, шифрование и удаление PII определяются до production.

DDL включает UNIQUE/indexes для idempotency и индекс reconciliation по status/`next_retry_at`. Миграция
идемпотентна, учитывает префиксы сайтов и разворачивается до кода, который пишет в новые таблицы. Исторический
backfill не требуется. Rollback не удаляет таблицы с незавершёнными операциями.

### 3.5. Общий orchestration и финализация

Return, webhook worker и reconciliation вызывают один `PaypalRestPaymentOrchestrator`:

```text
load attempt and credential revision
-> acquire attempt lock
-> validate state and hold
-> capture/reconcile with saved request ID
-> verify nested Capture
-> invoke idempotent business finalizer
-> persist terminal result
```

Orchestrator и финализатор не читают пользовательскую сессию и mutable global `PaypalConfig`. Все данные
берутся из attempt, его items, immutable business snapshot и сохранённой credential revision.

Бизнес-завершение разделяется на:

- core fulfillment: создание броней либо зачисление баланса;
- счёт и связанные документы;
- вторичные эффекты: письма, iCal, door codes, уведомления.

Core fulfillment имеет durable step ledger и DB-idемпотентность. Счёт и вторичные эффекты повторяются отдельно,
предпочтительно через outbox. Их ошибка не запускает автоматический refund уже корректно оказанной услуги.

Компенсационный refund допустим только если доказано отсутствие core fulfillment либо частичный результат
успешно локально компенсирован. При временной ошибке сначала повторяется идемпотентный финализатор. Неопределённый
частичный результат переводится в `manual_review`, а не автоматически возвращается.

Legacy-контроллер остаётся на прежнем потоке. Перевод NVP на общие сервисы допускается только отдельной задачей
с regression-тестами.

## 4. REST-потоки

### 4.1. Создание Order

1. Create endpoint проверяет CSRF, rate limit и право клиента на checkout.
2. Сервер выводит purpose, профиль, владельца, hold, сумму и валюту из локальных данных.
3. Два конкурентных submit повторно используют активную попытку либо отсеиваются UNIQUE/CAS.
4. Для `legacy_nvp` выполняется существующий маршрут без изменений.
5. Для `rest_v2` до HTTP-вызова создаются attempt, items и операция `create_order` с постоянным request ID.
6. Hold и attempt получают явный срок жизни. Стандартное окно Orders для capture обычно составляет 3 часа;
   расширение требует отдельного согласования с PayPal и не считается доступным по умолчанию.
7. Сервер вызывает `POST /v2/checkout/orders` с `intent=CAPTURE`.
8. Локальный attempt ID передаётся в `purchase_units[0].custom_id`. `invoice_id` используется только
   для реального уникального номера счёта, а не для технической корреляции.
9. В `payment_source.paypal.experience_context` передаются HTTPS `return_url`, `cancel_url`,
   `user_action=PAY_NOW` и `shipping_preference=NO_SHIPPING`.
10. Amount formatter учитывает поддерживаемые PayPal валюты и их точность, включая zero-decimal currencies.
11. Принимаются валидные Order ID и HTTPS link relation `payer-action` или `approve`.
12. Order ID сохраняется до redirect. Redirect допускается только на разрешённый PayPal host.
13. Approval page открывается в обычном браузере, не во встроенном WebView.

### 4.2. Return, capture и потерянный return

`return_url` содержит криптографически случайный локальный token. В БД хранится только hash и expiry.
PayPal query `token` не является достаточным доказательством связи с попыткой.

Общий алгоритм:

1. Найти attempt и сохранённую credential revision.
2. Под блокировкой проверить state, purpose, владельца/гостевой token, hold, сумму и валюту.
3. При необходимости получить Order через GET и убедиться, что это ожидаемый Order.
4. Выполнить capture с сохранённым `PayPal-Request-Id`.
5. При timeout, 5xx или `PREVIOUS_REQUEST_IN_PROGRESS` повторять только в разрешённом idempotency window.
6. При `ORDER_ALREADY_CAPTURED` и других неоднозначных ответах перейти к GET/reconciliation, не создавать Order.
7. Найти ожидаемый объект в `purchase_units[].payments.captures[]` и проверить Capture ID, `status`,
   amount и currency.
8. Top-level `Order.status=COMPLETED` сам по себе не подтверждает оплату: завершённый Order может содержать
   Capture со статусом `DECLINED`.
9. Только Capture `COMPLETED` разрешает core fulfillment.
10. Capture `PENDING` сохраняется с Capture ID, не запускает fulfillment и обрабатывается webhook/reconciliation.
11. После `COMPLETED` вызывается идемпотентный business finalizer.
12. Повторный return возвращает сохранённый результат без повторного списания или бизнес-операции.

`CHECKOUT.ORDER.APPROVED` при потерянном return ставит в очередь тот же orchestration flow. Webhook не
реализует отдельный capture-код.

Если безопасная компенсация необходима, создаётся refund operation с постоянным request ID. `PENDING` refund
остаётся в `refund_pending` и сверяется через webhook/GET. `manual_review` используется при
`FAILED`/`CANCELLED`, исчерпании контролируемых retry или неизвестном результате.

### 4.3. Оплата бронирований

До capture временные бронирования являются hold. Непосредственно перед capture проверяются:

- принадлежность всех hold этой попытке;
- серверная сумма и валюта;
- срок hold;
- отсутствие уже созданных постоянных броней;
- отсутствие другого capture/finalizer под тем же attempt.

Перед сетевым capture hold получает согласованную lease, чтобы не истечь в середине запроса. Для provider
`PENDING` продуктовая политика обязана определить максимальный срок удержания корта. Если hold нельзя
сохранить до терминального Capture и деньги позже списаны, операция не создаёт конфликтующую бронь и уходит
в безопасный refund/manual review.

Из-за MyISAM нельзя полагаться на общий rollback. Каждый durable шаг фиксируется в attempt items/ledger до
внешних side effects. После core commit отдельно создаются счёт, письма, iCal и door code delivery. Ошибка счёта
или уведомления повторяется отдельно и не возвращает оплату за уже созданную бронь.

Перевод критичных таблиц на InnoDB остаётся отдельным улучшением, но durable step ledger и блокировки attempt
обязательны уже в первом REST-релизе.

### 4.4. Пополнение личного счёта

Attempt хранит выбранный сервером пакет пополнения, `local_client_id` и необходимый immutable snapshot.
Webhook/reconciliation не используют `current_client_data` из сессии.

После подтверждённого Capture:

1. DB-constraint/ledger резервирует `provider_capture_id` для одного пополнения.
2. Создаётся или активируется приходный счёт.
3. `PrivateAccount/deposit` выполняется с постоянным business idempotency key.
4. Счёт закрывается только после подтверждённого deposit.
5. Attempt переводится в `business_completed`.

Совместимый формат `accounts.price_info`:

```text
paypal|{order_id}|{capture_id}|tk:{rawurlencode(profile_type_key)}
```

`rest_v2` не добавляется отдельным сегментом: текущий parser сочтёт его Payone payment type. Версия
интеграции хранится в attempt/related data. Новый формат допустим только вместе с версионированием и изменением
всех parser/report/PDF consumers.

### 4.5. Cancel и expiry

Переход на `cancel_url` означает только намерение пользователя отменить checkout, а не состояние PayPal.
Под attempt lock устанавливается `user_cancelled` и запрещается новый capture, если он ещё не начат.

Гонка cancel/capture разрешается тем же CAS/lock. Hold освобождается только когда подтверждено отсутствие capture
и orchestration больше не может его начать. Если Capture уже существует, выполняется обычная финализация либо
безопасная компенсация. Истёкшие Order/attempt очищаются reconciliation job.

### 4.6. Webhook inbox

Webhook endpoint является публичным HTTPS endpoint на порту 443 без сессии, CSRF и пользовательской авторизации.
Для каждой PayPal REST App используется URL со стабильным случайным `webhook_route_key`, который однозначно
определяет allowlisted App, credential revision и Webhook ID. Route key не считается секретом или заменой подписи.

Webhook ID не приходит в body или headers. Выбирать его по непроверенному resource ID нельзя. Альтернатива
route mapping - ограниченная проверка по allowlist исторических Webhook ID с защитой от amplification.

Порядок обработки:

1. Считать raw body до JSON middleware, ограничить размер и сохранить byte-for-byte для verification.
2. Проверить обязательные PayPal transmission headers и корректность JSON-копии.
3. По route mapping выбрать App/revision/Webhook ID.
4. Вызвать `verify-webhook-signature`, передав событие в исходном виде.
5. При `verification_status != SUCCESS` не начинать бизнес-обработку.
6. После успешной проверки атомарно вставить inbox event с UNIQUE по `event_id`.
7. Быстро вернуть 2xx после надёжной фиксации inbox. Worker обрабатывает событие с внутренними retry.
8. Несопоставленное проверенное событие сохраняется для reconciliation и создаёт операционный сигнал.

Минимальный allowlist событий:

- `CHECKOUT.ORDER.APPROVED`;
- `CHECKOUT.ORDER.COMPLETED`;
- `CHECKOUT.PAYMENT-APPROVAL.REVERSED`;
- `PAYMENT.CAPTURE.PENDING`;
- `PAYMENT.CAPTURE.COMPLETED`;
- `PAYMENT.CAPTURE.DECLINED`;
- `PAYMENT.CAPTURE.REFUNDED`;
- `PAYMENT.CAPTURE.REVERSED`;
- `PAYMENT.REFUND.PENDING`;
- `PAYMENT.REFUND.COMPLETED`;
- `PAYMENT.REFUND.FAILED`;
- `PAYMENT.REFUND.CANCELLED`.

События могут приходить повторно и не по порядку. Worker применяет монотонные переходы; конфликт проверяется GET
к PayPal. `CHECKOUT.ORDER.APPROVED` планирует capture orchestration, а Capture `COMPLETED` планирует
finalizer. Return, webhook и reconciliation сериализуются по одному attempt.

### 4.7. Reconciliation

Reconciliation является production-gate, а не желательным улучшением. Он:

- находит approved Order без return и запускает общий capture flow;
- сверяет неизвестный/`PENDING` Capture через GET Order/Capture;
- сверяет `refund_pending` через GET Refund;
- доводит `captured` до идемпотентного fulfillment;
- закрывает истёкшие approval/hold;
- переводит исчерпанные или противоречивые операции в `manual_review`;
- продолжает drain старых REST-попыток после rollback профиля.

До production определяются scheduler/worker, lock, batch size, backoff, dead-letter/manual-review, SLA и владелец
алертов. Если используется `app/cron/Cron.php`, для задачи добавляются защита от параллельного запуска и
ограниченная пакетная обработка.

## 5. Идемпотентность, ошибки и retry

Для Create, Capture и каждого Refund создаётся отдельный `PayPal-Request-Id` до HTTP-вызова. Вызовы одной
попытки сериализуются: одновременные запросы с одинаковым ID не считаются безопасным механизмом блокировки.

Идемпотентность PayPal не бессрочна:

- для Orders request ID по умолчанию хранится 6 часов;
- расширение Orders window до 72 часов требует согласования с PayPal Account Manager;
- для Refund документация указывает окно до 45 дней.

`idempotency_expires_at` фиксируется в operation. После окна нельзя вслепую повторять финансовый POST.
Сначала выполняется GET/reconciliation; если результат нельзя доказать, операция переходит в `manual_review`.

Правила:

- timeout и 5xx - retry с тем же request ID, bounded exponential backoff и jitter;
- `409 RESOURCE_CONFLICT / PREVIOUS_REQUEST_IN_PROGRESS` - задержка и retry той же операции с тем же ID;
- `401` resource endpoint - один refresh cached bearer token и один retry;
- `401 invalid_client` от OAuth endpoint - configuration error без автоматического retry;
- `429` - `Retry-After`, если он есть, иначе bounded backoff;
- прочие 4xx - не повторять без классификации PayPal error name;
- `ORDER_ALREADY_CAPTURED` и неизвестный Capture/Refund result - GET/reconciliation;
- исчерпанный retry budget - `manual_review` и уведомление.

В журнал попадают локальный operation ID, HTTP-код, PayPal error name и `debug_id`. Пользователь получает
локализованное сообщение и локальный номер операции.

## 6. Безопасность

- Client Secret хранится зашифрованным ключом вне БД либо во внешнем secrets manager.
- Plaintext Client Secret в `config` не является допустимым штатным live-решением. Исключение требует
  отдельного формального security acceptance до production.
- Секрет, access token, Authorization header и полный provider payload не выводятся в HTML и журналы.
- REST-запросы выполняются только сервером через TLS 1.2+ с проверкой сертификата.
- Create endpoint защищён CSRF, авторизацией/ownership check и rate limit.
- Сумма, валюта, purpose, профиль и IDs восстанавливаются по локальному attempt/hold.
- Return token криптографически случаен, ограничен по времени и хранится как hash.
- Endpoint и redirect hosts выбираются из allowlist.
- Webhook доверяется только после проверки подписи по route-mapped Webhook ID.
- Непроверенный webhook payload не может выбрать credentials или зарезервировать `event_id` как обработанный.
- PII в attempt/webhook хранится минимально, с утверждёнными retention и deletion rules.
- Ошибки, stack trace, PayPal body и `debug_id` не показываются пользователю.
- Доступ к credentials минимален; административные изменения аудируются.
- PayPal approval page не открывается во встроенном WebView.

## 7. Наблюдаемость и эксплуатация

Структурированные поля:

```text
payment_attempt_id
payment_operation_id
gateway
integration
profile_type_key
rest_app_key
credential_version
purpose
provider_order_id
provider_capture_id
provider_refund_id
provider_order_status
provider_capture_status
provider_refund_status
business_status
transition_source
retry_count
http_status
provider_debug_id
```

Credentials, access token, raw Authorization header и полный payload с PII не журналируются.

Обязательные сигналы:

- Capture `COMPLETED`, но core fulfillment не завершён;
- attempt/capture/refund находится в pending дольше согласованного SLA;
- verified webhook не сопоставлен с attempt;
- webhook signature не прошла проверку;
- систематические 401, 409, 429 или 5xx;
- расхождение Order/Capture/amount/currency;
- истёкшее idempotency window при неизвестном результате;
- non-terminal attempt старше порога;
- необработанная очередь `manual_review`.

Нужен runbook: поиск по локальному номеру, безопасный GET Order/Capture/Refund, повтор finalizer/side effect,
ручная компенсация и завершение drain. Для каждого алерта определяются владелец, SLA и канал уведомления.

## 8. Изменения по компонентам

### Конфигурация и админка

- `core/modules/payment/paypal/models/PaypalProfileContext.php`: `api_mode`, `rest_app_key` и
  условные правила.
- `core/modules/payment/config/PaypalConfig.php`: mapping `legacy_nvp -> old` и `rest_v2 -> rest`,
  runtime readiness и выбор ревизии App.
- `core/modules/config/controllers/admin/online_payment/PaymentProfileSection.php`: merge отсутствующих
  полей с previous, затем provider validation; явная очистка credentials.
- `core/modules/config/controllers/admin/online_payment/PaypalSection.php`: PayPal-specific validation.
- `core/modules/config/views/admin/online_payment/paypal/_form.php`: API mode, App/environment,
  write-only Client Secret, webhook URL и readiness.
- `core/modules/config/views/admin/online_payment/paypal/_list.php`: mode, environment, App и revision без secret.
- `app/lang/admin/de/de.ini`, `app/lang/admin/en/en.ini`, `app/lang/admin/ru/ru.ini`:
  новые подписи и локализованные сообщения.
- Новая versioned-модель/таблицы PayPal REST App и credentials либо эквивалентная immutable storage.

`rest_v2` нельзя сделать выбираемым, пока backend capability не подтверждает schema, client, webhook,
reconciliation и secret storage readiness.

### REST-инфраструктура

- Enum API mode и явный route mapping.
- `PaypalRestClient`, DTO, нормализованные exceptions и fake HTTP contract.
- Table/Model/Engine или принятые проектом сервисы для attempts, operations, items, webhook inbox и ledger.
- `PaypalRestPaymentOrchestrator`, webhook worker и обязательный reconciliation job.
- Outbox/retry для счетов, писем, iCal и уведомлений.
- Идемпотентный DDL/installer в принятом механизме проекта.

### Checkout и бизнес-операции

- `PaypalPaymentRestController` с route-сегментом `rest`.
- `pp_form.php` выбирает route через серверный mapping профиля; POST-значения не считаются доверенными.
- Финализаторы двух назначений получают attempt/snapshot, а не сессию.
- Order/Capture ID связываются с существующими сущностями и attempt.
- `accounts.price_info` сохраняет совместимый `paypal|ORDER|CAPTURE|tk:...` без сегмента `rest_v2`.
- `payment_paypal` в `structure.site.php` исправляется на PayPal route и покрывается regression-тестом.

### Legacy

- `PaypalPaymentOldController` и `PaypalPaymentModel` остаются рабочими.
- NVP-поля и fallback-константы не удаляются.
- `api_mode` по умолчанию всегда `legacy_nvp`.
- REST App/revision, routes, webhook и reconciliation не отключаются, пока есть non-terminal REST attempts.

## 9. План внедрения

### Этап 0. Зафиксировать решения

Обязательные решения:

- MVP: бронирования, пополнение или последовательный запуск;
- redirect checkout без JavaScript SDK;
- обычный provider refund при отмене брони или только техническая компенсация;
- модель REST Apps/credential revisions и процедура ротации;
- route mapping для каждого Webhook ID;
- защита Client Secret;
- capture policy для `CHECKOUT.ORDER.APPROVED` без return;
- SLA hold при Capture `PENDING`;
- scheduler/worker, manual-review owner и alert channel;
- PII/raw webhook retention;
- прямой REST-клиент или SDK с определённым dependency/deployment path.

Рекомендация: прямой клиент поверх `Service::curl()`, versioned REST App, отдельный webhook route key,
capture через общий orchestrator, обязательный technical refund только после доказанной безопасной компенсации.

### Этап 1. Schema, credentials и выключенная конфигурация

Работы:

- создать InnoDB attempts/operations/items/webhook/ledger schema и indexes;
- добавить versioned REST App и encrypted secret storage;
- добавить `api_mode`, merge/validation и административный UI;
- реализовать OAuth connection check и проверку webhook registration;
- оставить `rest_v2` недоступным для активации до полной runtime readiness;
- развернуть всё при `legacy_nvp` для существующих профилей.

Критерий: legacy работает без миграции профилей, secret не выводится/не хранится открыто, schema готова на
поддерживаемых вариантах БД.

### Этап 2. REST-клиент, state machine и idempotency

Работы:

- OAuth cache;
- Create/Get Order, Capture/Get Capture, Refund/Get Refund, Verify Webhook Signature;
- operations с request IDs и idempotency expiry;
- CAS/lock и таблица допустимых provider/business transitions;
- нормализованные errors, retry budget и безопасное логирование.

Критерий: неизвестный результат восстанавливается через GET в пределах правил, а конкурентные вызовы не создают
две provider/business операции.

### Этап 3. Webhook, orchestration и reconciliation

Работы:

- route mapping App/revision/Webhook ID;
- raw-body verification и durable inbox;
- worker с dedupe/out-of-order handling;
- общий orchestration для return/webhook/reconciliation;
- обязательный scheduler, алерты, manual-review queue и runbook.

Критерий: approved Order без return запускает тот же capture flow; pending/unknown операции доводятся до
терминального или manual-review состояния в согласованный SLA.

### Этап 4. Оплата бронирований

Работы:

- серверный пересчёт и reusable attempt при double submit;
- Create/redirect/return/cancel;
- attempt items, hold lease и race protection;
- строгая проверка nested Capture;
- идемпотентный core finalizer и durable step ledger;
- отдельный retry счетов/писем/iCal/door codes;
- безопасная компенсация только после классификации результата.

Критерий: один Capture создаёт один набор броней; crash в любой durable точке восстанавливается без дубля;
ошибка вторичного эффекта не вызывает ошибочный refund.

### Этап 5. Пополнение баланса

Работы:

- REST flow без зависимости от session/current client;
- business ledger/UNIQUE на Capture;
- идемпотентный deposit;
- совместимый `price_info` и related data;
- pending/refund/reconciliation сценарии.

Критерий: один Capture ID может увеличить баланс ровно один раз даже при crash после deposit и до обновления
attempt.

### Этап 6. Тестирование

Перед созданием или запуском тестов следовать `.docs/development/testing.md` и `../tests/README.md`.
Тесты размещаются в соседнем `../tests/`, capability добавляется и включается по правилам проекта.

Минимальная матрица:

- unit: mode/profile mapping, merge скрытых полей, amount formatter, token cache, transitions, error mapping;
- REST contract с fake HTTP: OAuth, Create/Get, Capture/Get, Refund/Get, 401, 409, 422, 429, 5xx, timeout;
- DB concurrency: два submit, два return, return/webhook race, UNIQUE/CAS/ledger;
- capture verification: top-level Order `COMPLETED` с nested Capture `DECLINED`, сумма/валюта/ID mismatch;
- webhook: invalid signature, byte-preserving body, duplicate, out-of-order, unmatched event и route к нескольким Apps;
- recovery: lost return до capture, `APPROVED` webhook, credential rotation, rollback и истечение windows;
- pending: Capture pending -> completed/declined; Refund pending -> completed/failed/cancelled;
- business: одна/несколько броней, занятый/истёкший hold, гость, клиент, пополнение;
- crash points: до/после provider POST, core fulfillment, deposit, attempt update, invoice и outbox;
- compensation: безопасный refund, failed refund, частичный локальный результат и manual review;
- compatibility: legacy NVP, Payone, `price_info` parser, reports и PDF определяют PayPal корректно;
- migration: поддерживаемые MySQL/MariaDB schema variants, prefixes, повторный installer и rollback без DROP;
- security: подмена всех hidden values, CSRF, state expiry, redaction в HTML/log/audit и raw payload retention;
- PayPal sandbox: approve/cancel и реальные webhook redelivery/verification.

Postback `verify-webhook-signature` не поддерживает Webhooks Simulator events. End-to-end postback verification
выполняется на реальных sandbox-транзакциях; simulator используется только в поддерживаемом PayPal режиме.

Критерий: автоматизированные проверки проходят, sandbox-сценарии документируются локальными attempt ID и
provider IDs без PII и secrets.

### Этап 7. Поэтапное включение

1. Развернуть schema, код и UI при `legacy_nvp`; `rest_v2` держать за readiness/capability.
2. Запустить worker/reconciliation, alerts и manual-review runbook до первого REST Order.
3. Создать отдельные Sandbox и Live REST Apps и webhook subscriptions.
4. Проверить Payment Receiving Preferences и поддерживаемую валюту merchant account.
5. Включить Sandbox для одного тестового контекстного профиля и пройти оба назначения.
6. Настроить live credential revision и live Webhook ID без замены sandbox revision.
7. Включить один низкорисковый production-контекст.
8. Наблюдать SLA, pending, webhook delivery, reconciliation и business side effects.
9. Расширять включение по профилям.
10. Удалять NVP только отдельной задачей после drain и подтверждения всех сайтов.

Rollback использует отдельный kill switch создания новых REST Orders и переводит профиль на `legacy_nvp`.
Он не отключает REST routes, старые credential revisions, webhook, worker или reconciliation. Drain завершается
только после отсутствия non-terminal attempts и окончания принятого окна webhook/reconciliation.

## 10. Критерии приёмки

- Администратор выбирает `legacy_nvp` или готовый `rest_v2` по контекстному профилю.
- Существующие профили продолжают работать через NVP без backfill.
- Два конкурентных submit создают ровно одну активную attempt и один PayPal Order.
- Return, webhook и reconciliation вызывают один сериализованный orchestration flow.
- Потерянный return доводится до результата или `manual_review` в установленный SLA.
- Fulfillment выполняется только по nested Capture `COMPLETED` с ожидаемыми ID, суммой и валютой.
- Capture `PENDING` не создаёт бронь и не увеличивает баланс.
- Один Capture ID создаёт ровно один core business effect, гарантированный DB constraint.
- Повторные callback/webhook и out-of-order events не откатывают состояние и не создают дубли.
- Refund `PENDING` сверяется по Refund ID; неизвестный результат не считается успехом или отказом.
- Ошибка invoice/email/iCal/door code повторяется отдельно и не возвращает оплату за оказанную услугу.
- Компенсационный refund запускается только после доказанного отсутствия/компенсации core fulfillment.
- Async flow не требует пользовательской сессии или текущего mutable профиля.
- Attempt ссылается на неизменяемую App/credential revision.
- Client Secret зашифрован, write-only и отсутствует в HTML, logs, audit payload и exceptions.
- `accounts.price_info`, reports и PDF сохраняют классификацию PayPal, а не Payone.
- `payment_paypal` ведёт на PayPal route.
- Legacy NVP и Payone не получают поведенческой регрессии.
- Rollback прекращает новые REST Orders и продолжает drain старых попыток.
- Non-terminal операции старше установленного порога всегда создают alert.

## 11. Что не включать в первый REST-релиз

- Полную замену UI на PayPal JavaScript SDK/Smart Buttons.
- Удаление NVP-кода и legacy-реквизитов.
- Переименование исторических полей `paypal_status` и таблицы `reservations_tmp_paypal`.
- Массовую переработку отчётов сверх сохранения совместимости и связи с attempt.
- Автоматический provider refund при обычной отмене брони без отдельного бизнес-правила.
- Изменение текущего `refund_for_paypal`: он зачисляет сумму на личный счёт и не означает PayPal Refund API.
- Одновременный рефакторинг Payone.
- Миграцию legacy NVP на общий finalizer без отдельного regression scope.

Безопасное хранение Client Secret, webhook inbox, reconciliation и DB-идемпотентность не относятся к
«последующему усилению»: они входят в обязательный production scope.

## 12. Открытые вопросы этапа 0

1. В MVP входят оба назначения или сначала только бронирования?
2. Обычная отмена возвращает деньги в PayPal или сохраняет текущее зачисление на личный счёт?
3. Создаётся отдельная versioned-модель REST Apps или versioned credentials остаются в `config`?
4. Как выполняется ротация Client Secret/App без потери доступа к non-terminal attempts?
5. Какой публичный URL:443 гарантирован на всех installations и какой route key соответствует каждой App?
6. Какой scheduler/worker запускает webhook inbox и reconciliation, кто владеет алертами?
7. Разрешён ли автоматический capture после `CHECKOUT.ORDER.APPROVED` без return?
8. Каков SLA hold для Capture `PENDING` и правило при более позднем `COMPLETED`?
9. Какие ошибки относятся к core fulfillment, а какие к retryable invoice/side effects?
10. Каковы retention и encryption rules для payer snapshot и raw webhook body?
11. Используется прямой REST-клиент или SDK и где управляется Composer dependency?
12. Какой измеримый SLA принят для reconciliation и `manual_review`?

## 13. Официальная документация PayPal

- [Get started with PayPal REST APIs](https://developer.paypal.com/api/rest/)
- [Get an access token](https://developer.paypal.com/reference/get-an-access-token/)
- [Orders API integration guide](https://developer.paypal.com/api/rest/integration/orders-api)
- [Orders API standard use case](https://developer.paypal.com/api/rest/integration/orders-api/api-use-cases/standard/)
- [Create Order](https://developer.paypal.com/sdk/orders/v2/orders-create/)
- [Purchase Unit Request](https://developer.paypal.com/sdk/orders/v2/definitions/purchase_unit_request)
- [API requests and headers](https://developer.paypal.com/api/rest/requests/)
- [Idempotency](https://developer.paypal.com/api/rest/reference/idempotency/)
- [API responses and errors](https://developer.paypal.com/api/rest/responses/)
- [Orders v2 error messages](https://developer.paypal.com/api/orders/v2/error-messages)
- [Capture status](https://developer.paypal.com/api/payments/v2/definitions/capture_status/)
- [Get capture](https://developer.paypal.com/api/payments/v2/captures-get/)
- [Refund a capture](https://developer.paypal.com/api/payments/v2/captures-refund/)
- [Get refund](https://developer.paypal.com/api/payments/v2/refunds-get/)
- [Webhooks overview](https://developer.paypal.com/api/rest/webhooks/)
- [Integrate webhooks](https://developer.paypal.com/api/rest/webhooks/rest/)
- [Webhook event names](https://developer.paypal.com/api/rest/webhooks/event-names/)
- [Verify webhook signature](https://developer.paypal.com/api/webhooks/v1/verify-webhook-signature-post/)
- [Currency codes](https://developer.paypal.com/reference/currency-codes/)
- [Security guidelines](https://developer.paypal.com/security-guidelines)
- [Move an app to production](https://developer.paypal.com/api/rest/production/)
- [PayPal Server SDK for PHP](https://developer.paypal.com/serversdk/php/getting-started/how-to-get-started/)
