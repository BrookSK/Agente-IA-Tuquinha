-- Adiciona campos de cores de texto ao branding de parceiros
ALTER TABLE course_partner_branding
ADD COLUMN text_color VARCHAR(20) DEFAULT NULL AFTER secondary_color,
ADD COLUMN button_text_color VARCHAR(20) DEFAULT NULL AFTER text_color;
