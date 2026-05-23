ALTER TABLE pages
    ADD COLUMN seo_title VARCHAR(255) NULL AFTER title,
    ADD COLUMN og_image VARCHAR(255) NULL AFTER featured_image,
    ADD COLUMN canonical_url VARCHAR(255) NULL AFTER og_image,
    ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0 AFTER canonical_url;
