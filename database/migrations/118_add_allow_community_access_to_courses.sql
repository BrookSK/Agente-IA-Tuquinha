ALTER TABLE courses 
ADD COLUMN allow_community_access TINYINT(1) NOT NULL DEFAULT 0 AFTER is_external;
