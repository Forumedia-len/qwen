# Модуль config

## Назначение

Модуль предоставляет системные, модульные и контекстные настройки Active Court.

## Текущее состояние

Базовая и контекстная конфигурация используется приложением. Ограничения клиентов остаются отдельным активным направлением работ.

## Основные компоненты

- Код модуля: `core/modules/config/`.
- Конфигурация приложения: `app/config/`.
- Контекстный доступ к настройкам реализован через scoped config API.

## Документы

| Документ | Описание |
|---|---|
| [Scoped config](scoped-config.md) | Контекстный доступ к настройкам |
| [Open type config](open-type-config.md) | Настройки открытых типов |
| [Features](features.md) | Дополнительные возможности и флаги модулей |
| [Client restriction](client-restriction.md) | Модель ограничений клиентов |
| [Client restriction и scoped config](client-restriction-and-scoped-config.md) | Совместное использование подсистем |

## Активные планы

- [Внедрение ограничений клиентов](../../tasks/client-restriction-implementation.md).

## Связанные модули

`clients`, `reservations`, `payment`.
