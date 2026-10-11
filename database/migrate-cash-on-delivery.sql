USE noir_form;

ALTER TABLE orders
    ADD COLUMN checkout_token CHAR(64) DEFAULT NULL,
    ADD COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'cash_on_delivery',
    ADD UNIQUE KEY uq_orders_checkout_token (checkout_token);

UPDATE orders
SET payment_method = CASE
    WHEN stripe_session_id IS NOT NULL THEN 'legacy_card'
    ELSE 'cash_on_delivery'
END
WHERE payment_method = 'cash_on_delivery';

ALTER TABLE orders
    DROP INDEX uq_orders_stripe_session,
    DROP INDEX uq_orders_stripe_payment_intent,
    DROP COLUMN stripe_session_id,
    DROP COLUMN stripe_payment_intent_id;

DROP TABLE IF EXISTS stripe_webhook_events;
