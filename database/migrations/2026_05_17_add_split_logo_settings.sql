INSERT IGNORE INTO site_settings
    (setting_key, setting_value, setting_type, group_name, label, sort_order, is_public)
VALUES
    ('logo_header_url', '', 'url', 'identidad', 'Logo header', 31, 1),
    ('logo_footer_url', '', 'url', 'identidad', 'Logo footer / columna sábado', 32, 1);
