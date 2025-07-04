-- Create pendaftaran_histories table for tracking status changes
-- Execute this SQL directly in your MySQL database

USE volunteer_management;

-- Create pendaftaran histories table
CREATE TABLE pendaftaran_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftaran_id BIGINT UNSIGNED NOT NULL,
    status_from VARCHAR(50) NULL,
    status_to VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pendaftaran_id) REFERENCES pendaftarans(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_pendaftaran_id (pendaftaran_id),
    INDEX idx_created_at (created_at)
);

-- Add initial history records for existing pendaftarans
INSERT INTO pendaftaran_histories (pendaftaran_id, status_from, status_to, notes, created_by, created_at)
SELECT 
    id, 
    NULL, 
    'Pending', 
    'Pengajuan bantuan sosial dibuat',
    user_id,
    created_at
FROM pendaftarans
WHERE id NOT IN (SELECT DISTINCT pendaftaran_id FROM pendaftaran_histories WHERE status_to = 'Pending' AND status_from IS NULL);

-- Add current status history for non-pending applications
INSERT INTO pendaftaran_histories (pendaftaran_id, status_from, status_to, notes, created_by, created_at)
SELECT 
    id,
    'Pending',
    status,
    CONCAT('Status diubah menjadi ', status),
    user_id,
    updated_at
FROM pendaftarans 
WHERE status != 'Pending' 
  AND id NOT IN (SELECT DISTINCT pendaftaran_id FROM pendaftaran_histories WHERE status_to != 'Pending');

-- Verify the table was created
SHOW TABLES LIKE '%history%';
DESCRIBE pendaftaran_histories;