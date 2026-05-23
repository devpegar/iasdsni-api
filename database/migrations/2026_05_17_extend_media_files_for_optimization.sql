ALTER TABLE media_files
    ADD COLUMN original_path VARCHAR(255) NULL AFTER public_url,
    ADD COLUMN original_url VARCHAR(255) NULL AFTER original_path,
    ADD COLUMN optimized_path VARCHAR(255) NULL AFTER original_url,
    ADD COLUMN optimized_url VARCHAR(255) NULL AFTER optimized_path,
    ADD COLUMN thumbnail_path VARCHAR(255) NULL AFTER optimized_url,
    ADD COLUMN thumbnail_url VARCHAR(255) NULL AFTER thumbnail_path,
    ADD COLUMN width INT NULL AFTER thumbnail_url,
    ADD COLUMN height INT NULL AFTER width,
    ADD COLUMN optimized_width INT NULL AFTER height,
    ADD COLUMN optimized_height INT NULL AFTER optimized_width,
    ADD COLUMN optimization_status VARCHAR(30) NOT NULL DEFAULT 'legacy' AFTER optimized_height;
