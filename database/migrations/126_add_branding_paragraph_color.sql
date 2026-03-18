-- Adiciona campo de cor de parágrafos ao branding de parceiros
ALTER TABLE course_partner_branding
ADD COLUMN paragraph_color VARCHAR(20) DEFAULT NULL AFTER link_color;
