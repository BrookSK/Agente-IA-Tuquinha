CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    short_description VARCHAR(255) NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NULL,
    is_paid TINYINT(1) NOT NULL DEFAULT 0,
    price_cents INT NULL,
    allow_plan_access_only TINYINT(1) NOT NULL DEFAULT 1,
    allow_public_purchase TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);
