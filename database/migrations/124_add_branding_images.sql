-- Adiciona campos de imagens configuráveis ao branding de parceiros
ALTER TABLE course_partner_branding
ADD COLUMN header_image_url VARCHAR(500) DEFAULT NULL AFTER button_text_color,
ADD COLUMN footer_image_url VARCHAR(500) DEFAULT NULL AFTER header_image_url,
ADD COLUMN hero_image_url VARCHAR(500) DEFAULT NULL AFTER footer_image_url,
ADD COLUMN background_image_url VARCHAR(500) DEFAULT NULL AFTER hero_image_url;
