ALTER TABLE kanban_cards
  ADD COLUMN is_done TINYINT(1) NOT NULL DEFAULT 0,
  ADD INDEX idx_kanban_cards_is_done (is_done);
