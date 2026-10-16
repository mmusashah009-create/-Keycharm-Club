USE noir_form;

-- Run once before adding new keychains; retain product and order history.
UPDATE products
SET is_active = 0;
