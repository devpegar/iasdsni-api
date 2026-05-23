CREATE TABLE media_folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_media_folders_active_order (is_active, sort_order)
);

ALTER TABLE media_files
    ADD COLUMN folder_id INT NULL AFTER id,
    ADD INDEX idx_media_files_folder (folder_id);

INSERT IGNORE INTO media_folders (name, slug, sort_order, is_active)
VALUES
    ('General', 'general', 10, 1),
    ('Hero', 'hero', 20, 1),
    ('Noticias', 'noticias', 30, 1),
    ('Galería', 'galeria', 40, 1),
    ('Logos', 'logos', 50, 1),
    ('SEO', 'seo', 60, 1);
