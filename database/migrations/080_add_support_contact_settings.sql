INSERT INTO settings (`key`, `value`) VALUES
('support_whatsapp', '5517988093160'),
('support_email', 'contato@lrvweb.com.br')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
