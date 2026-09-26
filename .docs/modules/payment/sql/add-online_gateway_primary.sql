-- Основной шлюз онлайн-оплаты: отдельный тип конфига `payment` (не paypal — это не учётные данные PayPal).
-- alias: online_gateway_primary, value: paypal | payone
--
-- Подставьте имя таблицы (часто с префиксом проекта). Повторный запуск безопасен.

INSERT INTO `config` (`type`, `alias`, `value`)
SELECT 'payment', 'online_gateway_primary', 'paypal'
FROM (SELECT 1 AS _) AS dummy
WHERE NOT EXISTS (
  SELECT 1
  FROM `config` AS c
  WHERE c.`type` = 'payment'
    AND c.`alias` = 'online_gateway_primary'
);
