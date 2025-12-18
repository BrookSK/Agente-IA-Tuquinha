ALTER TABLE user_friends
    ADD COLUMN is_favorite_user1 TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN is_favorite_user2 TINYINT(1) NOT NULL DEFAULT 0;
