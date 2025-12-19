ALTER TABLE social_portfolio_collaborators
    ADD COLUMN portfolio_item_id INT NULL AFTER collaborator_user_id,
    DROP INDEX uniq_spc_pair,
    ADD UNIQUE KEY uniq_spc_item_pair (owner_user_id, collaborator_user_id, portfolio_item_id),
    ADD INDEX idx_spc_item (portfolio_item_id);

ALTER TABLE social_portfolio_invitations
    ADD COLUMN portfolio_item_id INT NULL AFTER inviter_user_id,
    ADD INDEX idx_spi_item (portfolio_item_id);
