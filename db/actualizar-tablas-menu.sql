-- Solo para una base que ya tenía las tablas del menú creadas con la primera versión.
-- Agrega las columnas nuevas sin borrar datos. En una base nueva no hace falta:
-- basta con importar schema.sql.
ALTER TABLE restaurants
    ADD COLUMN IF NOT EXISTS description VARCHAR(300) NULL AFTER name,
    ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS address VARCHAR(200) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS opening_hours VARCHAR(120) NULL AFTER address;

ALTER TABLE menu_items
    ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) NULL AFTER description;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(30) NULL AFTER customer_name,
    ADD COLUMN IF NOT EXISTS order_type VARCHAR(20) NOT NULL DEFAULT 'mesa' AFTER customer_phone,
    MODIFY COLUMN table_label VARCHAR(120) NULL;
