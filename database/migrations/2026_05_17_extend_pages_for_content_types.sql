ALTER TABLE pages
    ADD COLUMN page_type VARCHAR(30) NOT NULL DEFAULT 'page' AFTER title,
    ADD COLUMN excerpt TEXT NULL AFTER meta_description,
    ADD COLUMN featured_image VARCHAR(255) NULL AFTER excerpt,
    ADD COLUMN published_at DATETIME NULL AFTER is_active,
    ADD INDEX idx_pages_type_active_published (page_type, is_active, published_at),
    ADD INDEX idx_pages_slug (slug);
