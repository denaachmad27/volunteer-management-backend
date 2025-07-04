-- Fix ENUM status column to include "Perlu Dilengkapi"
-- Execute this SQL directly in your MySQL database

USE volunteer_management;

-- Add "Perlu Dilengkapi" to the status ENUM
ALTER TABLE pendaftarans 
MODIFY COLUMN status ENUM('Pending', 'Diproses', 'Disetujui', 'Ditolak', 'Selesai', 'Perlu Dilengkapi') 
DEFAULT 'Pending';

-- Verify the change
SHOW COLUMNS FROM pendaftarans WHERE Field = 'status';