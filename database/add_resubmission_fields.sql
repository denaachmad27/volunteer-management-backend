-- Add resubmission tracking fields to pendaftarans table
-- Execute this SQL directly in your MySQL database

USE volunteer_management;

-- Add resubmission fields
ALTER TABLE pendaftarans 
ADD COLUMN is_resubmission BOOLEAN DEFAULT FALSE AFTER catatan_admin,
ADD COLUMN resubmitted_at TIMESTAMP NULL AFTER is_resubmission,
ADD COLUMN resubmission_count INT DEFAULT 0 AFTER resubmitted_at;

-- Verify the changes
SHOW COLUMNS FROM pendaftarans;