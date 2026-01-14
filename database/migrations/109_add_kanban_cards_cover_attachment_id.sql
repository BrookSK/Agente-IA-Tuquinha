ALTER TABLE kanban_cards
  ADD COLUMN cover_attachment_id INT UNSIGNED NULL,
  ADD INDEX idx_kanban_cards_cover_attachment_id (cover_attachment_id);
