UPDATE kanban_card_attachments a
INNER JOIN kanban_cards c ON c.cover_attachment_id = a.id
SET a.is_cover = 1
WHERE c.cover_attachment_id IS NOT NULL;
