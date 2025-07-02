# Changelog

## [Unreleased] - 2025-07-02

### Fixed
- **Database Configuration**: Fixed database connection issues by updating configuration to use proper MySQL user and credentials
- **CASCADE DELETE Implementation**: Implemented proper CASCADE DELETE for foreign key relationships
- **Query Fixes**: Fixed `transaction_date` column references to use `created_at` throughout the application
- **Foreign Key Constraints**: Removed manual foreign key validation in PHP and let database handle CASCADE operations
- **Admin Dashboard**: Fixed dashboard data retrieval issues for transactions and reports

### Added
- **Missing Database Tables**: Added `user_activity_logs` table for tracking user activities
- **Proper Foreign Key Constraints**: Updated all foreign key constraints with appropriate CASCADE and SET NULL rules
- **Database Schema Improvements**: Enhanced database structure with proper relationships

### Changed
- **Delete Operations**: Products, categories, and users can now be deleted with automatic cleanup of related records
- **Error Messages**: Updated success/error messages to reflect CASCADE delete behavior
- **Database Connection**: Changed from root user to dedicated `bagas_user` for better security

### Database Schema Changes
- `transaction_items.product_id` → `products.id` (CASCADE DELETE)
- `transaction_items.transaction_id` → `transactions.id` (CASCADE DELETE)  
- `transactions.user_id` → `users.id` (CASCADE DELETE)
- `cart.user_id` → `users.id` (CASCADE DELETE)
- `cart.product_id` → `products.id` (CASCADE DELETE)
- `products.category_id` → `categories.id` (SET NULL)
- `user_activity_logs.user_id` → `users.id` (CASCADE DELETE)

### Files Modified
- `config/database.php` - Updated database credentials and connection handling
- `admin/products.php` - Removed manual foreign key validation, enabled CASCADE DELETE
- `admin/categories.php` - Removed manual foreign key validation, enabled CASCADE DELETE  
- `index.php` - Fixed dashboard queries to use correct column names
- Multiple files - Replaced `transaction_date` references with `created_at`

### Testing
- ✅ Verified CASCADE DELETE works for products with transaction items
- ✅ Verified CASCADE DELETE works for users with transactions, cart, and activity logs
- ✅ Verified SET NULL works for categories with products
- ✅ Confirmed no more "cannot delete due to foreign key constraint" errors
