ALTER TABLE users
    ADD COLUMN billing_cpf VARCHAR(20) NULL AFTER email,
    ADD COLUMN billing_birthdate DATE NULL AFTER billing_cpf,
    ADD COLUMN billing_phone VARCHAR(30) NULL AFTER billing_birthdate,
    ADD COLUMN billing_postal_code VARCHAR(15) NULL AFTER billing_phone,
    ADD COLUMN billing_address VARCHAR(255) NULL AFTER billing_postal_code,
    ADD COLUMN billing_address_number VARCHAR(50) NULL AFTER billing_address,
    ADD COLUMN billing_complement VARCHAR(255) NULL AFTER billing_address_number,
    ADD COLUMN billing_province VARCHAR(120) NULL AFTER billing_complement,
    ADD COLUMN billing_city VARCHAR(120) NULL AFTER billing_province,
    ADD COLUMN billing_state VARCHAR(10) NULL AFTER billing_city;
