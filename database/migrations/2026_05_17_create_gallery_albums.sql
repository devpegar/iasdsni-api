CREATE TABLE gallery_albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(140) UNIQUE NOT NULL,
    description TEXT NULL,
    cover_media_id INT NULL,
    event_date DATE NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gallery_albums_active_order (is_active, sort_order),
    INDEX idx_gallery_albums_slug (slug),
    INDEX idx_gallery_albums_featured (is_featured, is_active)
);

CREATE TABLE gallery_album_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT NOT NULL,
    media_file_id INT NOT NULL,
    caption VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gallery_items_album_order (album_id, is_active, sort_order)
);
