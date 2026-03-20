ALTER TABLE course_partner_branding
ADD COLUMN subdomain VARCHAR(80) DEFAULT NULL AFTER user_id,
ADD COLUMN subdomain_status VARCHAR(20) NOT NULL DEFAULT 'none' AFTER subdomain,
ADD COLUMN subdomain_requested_at DATETIME NULL AFTER subdomain_status,
ADD COLUMN subdomain_approved_at DATETIME NULL AFTER subdomain_requested_at,
ADD COLUMN favicon_url VARCHAR(500) DEFAULT NULL AFTER logo_url,
ADD UNIQUE KEY uq_course_partner_branding_subdomain (subdomain);
