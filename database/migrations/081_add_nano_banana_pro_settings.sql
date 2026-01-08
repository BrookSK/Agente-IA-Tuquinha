INSERT INTO settings (`key`, `value`) VALUES
('nano_banana_pro_api_key', ''),
('nano_banana_pro_endpoint', ''),
('nano_banana_pro_model', 'nano-banana-pro')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
