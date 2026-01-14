ALTER TABLE kanban_card_attachments
    ADD COLUMN is_cover TINYINT(1) NOT NULL DEFAULT 0;

CREATE INDEX idx_kca_card_cover ON kanban_card_attachments (card_id, is_cover);
