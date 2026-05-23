CREATE TABLE media_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes INT NOT NULL DEFAULT 0,
    path VARCHAR(255) NOT NULL,
    public_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_media_created_at (created_at),
    INDEX idx_media_mime_type (mime_type),
    INDEX idx_media_active_created (is_active, created_at)
);
