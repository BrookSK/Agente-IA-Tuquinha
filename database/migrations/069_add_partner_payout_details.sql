ALTER TABLE course_partners
    ADD COLUMN pix_key VARCHAR(255) NULL,
    ADD COLUMN bank_name VARCHAR(120) NULL,
    ADD COLUMN bank_agency VARCHAR(40) NULL,
    ADD COLUMN bank_account VARCHAR(60) NULL,
    ADD COLUMN bank_account_type VARCHAR(40) NULL,
    ADD COLUMN bank_holder_name VARCHAR(120) NULL,
    ADD COLUMN bank_holder_document VARCHAR(40) NULL;
