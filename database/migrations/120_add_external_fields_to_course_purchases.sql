ALTER TABLE course_purchases 
ADD COLUMN external_token VARCHAR(64) NULL AFTER asaas_payment_id,
ADD COLUMN redirect_after_payment TINYINT(1) NOT NULL DEFAULT 0 AFTER external_token,
ADD INDEX idx_external_token (external_token);
