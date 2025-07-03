# Changelog

## [v3.0] - 2025-07-02 - Enhanced Session & Security Update

### Added
- **Enhanced Session Management**: New comprehensive session manager with security improvements
- **Session Security Documentation**: Added detailed SESSION_MANAGEMENT.md guide
- **HTTP-Only Cookies**: Protection against XSS attacks
- **Session Regeneration**: Automatic session ID regeneration on login
- **Favicon Support**: Added favicon.ico for better UI

### Enhanced
- **Database Security**: Updated to use dedicated `bagas_user` instead of root
- **Session Timeout**: Configurable 24-minute session timeout
- **Authentication Flow**: Improved login/logout process with better security
- **Error Handling**: Enhanced error messages and user feedback

### Files Added/Modified
- `includes/session_manager.php` - New comprehensive session management
- `docs/SESSION_MANAGEMENT.md` - Complete session security documentation
- `auth/auth.php` - Enhanced authentication with session security
- `config/database.php` - Updated to use dedicated database user
- Multiple UI files - Improved user experience and security

## [v2.0] - 2025-07-02 - Database CASCADE Implementation

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
- **ERD Diagram**: Added visual database relationship diagram

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
- `admin/reports.php` - Enhanced reporting functionality
- `admin/transactions.php` - Improved transaction management
- `database/setup_database.sql` - Complete database schema with constraints
- `index.php` - Fixed dashboard queries to use correct column names
- Multiple files - Replaced `transaction_date` references with `created_at`

### Testing
- ✅ Verified CASCADE DELETE works for products with transaction items
- ✅ Verified CASCADE DELETE works for users with transactions, cart, and activity logs
- ✅ Verified SET NULL works for categories with products
- ✅ Confirmed no more "cannot delete due to foreign key constraint" errors
- ✅ Session security and timeout functionality validated
- ✅ Enhanced authentication flow tested
