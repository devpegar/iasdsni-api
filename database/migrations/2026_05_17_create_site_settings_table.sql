CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) UNIQUE NOT NULL,
    setting_value LONGTEXT NULL,
    setting_type VARCHAR(30) NOT NULL DEFAULT 'text',
    group_name VARCHAR(80) NOT NULL DEFAULT 'general',
    label VARCHAR(180) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_site_settings_public_group (is_public, group_name, sort_order),
    INDEX idx_site_settings_group_order (group_name, sort_order)
);

INSERT INTO site_settings
    (setting_key, setting_value, setting_type, group_name, label, sort_order, is_public)
VALUES
    ('site_name', 'IASD San Nicolás Centro', 'text', 'identidad', 'Nombre del sitio', 10, 1),
    ('site_subtitle', 'Iglesia Adventista del Séptimo Día', 'text', 'identidad', 'Subtítulo institucional', 20, 1),
    ('logo_url', '', 'url', 'identidad', 'URL del logo', 30, 1),
    ('favicon_url', '', 'url', 'identidad', 'URL del favicon', 40, 1),
    ('facebook_url', 'https://facebook.com/iasdsni', 'url', 'redes', 'Facebook', 10, 1),
    ('instagram_url', 'https://instagram.com/iasdsni', 'url', 'redes', 'Instagram', 20, 1),
    ('youtube_url', '', 'url', 'redes', 'YouTube', 30, 1),
    ('whatsapp_number', '3364683017', 'phone', 'contacto', 'WhatsApp', 10, 1),
    ('contact_email', 'info@iasdsni.com.ar', 'email', 'contacto', 'Email de contacto', 20, 1),
    ('address', 'Rivadavia 161, San Nicolás de los Arroyos', 'text', 'contacto', 'Dirección', 30, 1),
    ('service_hours', 'Sábados: 9:30 a 10:45 - Escuela Sabática; 11:00 a 12:00 - Culto Sabático; 19:00 a 20:00 - Culto Joven', 'longtext', 'contacto', 'Horarios de culto', 40, 1),
    ('google_maps_url', 'https://www.google.com/maps?q=Rivadavia+161+San+Nicolás+de+los+Arroyos&output=embed', 'url', 'contacto', 'URL de Google Maps', 50, 1),
    ('footer_text', 'Una comunidad que anuncia esperanza y se prepara para la segunda venida de Jesús.', 'longtext', 'textos', 'Texto del footer', 10, 1);
