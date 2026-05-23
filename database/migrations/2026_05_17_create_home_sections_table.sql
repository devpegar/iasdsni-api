CREATE TABLE home_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(80) NOT NULL,
    title VARCHAR(180) NULL,
    subtitle VARCHAR(255) NULL,
    config_json LONGTEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_home_sections_active_order (is_active, sort_order),
    INDEX idx_home_sections_key (section_key)
);

INSERT INTO home_sections (section_key, title, sort_order, is_active) VALUES
('hero_carousel', 'Portada', 1, 1),
('verse_daily', 'Versículo diario', 2, 1),
('mission_vision_service', 'Misión, visión y servicio', 3, 1),
('adventists_world', 'Adventistas en el mundo', 4, 1),
('gallery', 'Galería', 5, 1),
('contact_map', 'Ubicación y horarios', 6, 1);
