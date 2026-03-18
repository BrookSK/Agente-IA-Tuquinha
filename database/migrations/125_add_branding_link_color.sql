-- Adiciona campo de cor de links ao branding de parceiros
ALTER TABLE course_partner_branding
ADD COLUMN link_color VARCHAR(20) DEFAULT NULL AFTER button_text_color;
