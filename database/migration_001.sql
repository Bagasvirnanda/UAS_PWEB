-- Migration 001: Add supplier_id to products table
USE bagas_db;

-- Check if column exists before adding
SET @sql = 'ALTER TABLE products ADD COLUMN supplier_id INT NULL';
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'bagas_db' 
                   AND TABLE_NAME = 'products' 
                   AND COLUMN_NAME = 'supplier_id');

-- Only add if column doesn't exist
SET @sql = CASE 
    WHEN @col_exists = 0 THEN @sql
    ELSE 'SELECT "Column supplier_id already exists" as message'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = 'bagas_db' 
                  AND TABLE_NAME = 'products' 
                  AND COLUMN_NAME = 'supplier_id'
                  AND REFERENCED_TABLE_NAME = 'suppliers');

SET @sql = CASE 
    WHEN @fk_exists = 0 AND @col_exists = 0 THEN 'ALTER TABLE products ADD FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL'
    ELSE 'SELECT "Foreign key constraint already exists or column missing" as message'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
