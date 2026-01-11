# Database Cleanup Tool - User Guide

## Overview
The TSN Database Cleanup Tool helps you remove all test data from your local development environment, preparing it for fresh production deployment.

## ⚠️ Important Safety Features

### 1. Production Protection
- **Automatic Detection**: The tool detects if it's running on production (not localhost)
- **Extra Confirmation**: Requires `?allow_production=1` URL parameter on production
- **Admin-Only Access**: Only WordPress administrators can run this tool

### 2. Confirmation Requirements
- Must check confirmation checkbox
- JavaScript disables submit button until checked
- Additional browser confirmation dialog
- Shows exact record count before deletion

## 📍 How to Access

### Local Development
```
http://localhost/tsn/wp-content/plugins/tsn-modules/tools/cleanup-database.php
```

### If Localhost Doesn't Work
Navigate directly in your browser to:
```
C:\xampp\htdocs\tsn\wp-content\plugins\tsn-modules\tools\cleanup-database.php
```
(Right-click file → Open in Browser)

## 🔧 What It Does

### Data Removed
The tool will **TRUNCATE** (completely empty) these tables:
- ✅ `tsn_members` → All member records
- ✅ `tsn_member_transactions` → All membership payments  
- ✅ `tsn_events` → All events
- ✅ `tsn_event_ticket_types` → All ticket configurations
- ✅ `tsn_orders` → All ticket/donation orders
- ✅ `tsn_order_items` → All order line items
- ✅ `tsn_tickets` → All issued tickets
- ✅ `tsn_scans_audit` → All scan logs
- ✅ `tsn_donations` → All donations
- ✅ `tsn_donation_causes` → All fundraising causes

### Additional Cleanup
- ✅ Clears all TSN-related transients
- ✅ Clears TSN-related cache data

### What It Preserves
- ✅ Table structure (columns, indexes, etc.)
- ✅ WordPress core data (posts, pages, users, settings)
- ✅ Theme files and uploads
- ✅ Plugin code and configuration
- ✅ Other WordPress tables

## 📋 Step-by-Step Usage

### Step 1: Access the Tool
1. Open browser
2. Navigate to the cleanup tool URL
3. You'll see current database status

### Step 2: Review Current Data
- Table showing all TSN tables
- Record count for each table
- Total records to be deleted

### Step 3: Confirm Deletion
1. Read the warning carefully
2. Check the confirmation checkbox
3. Click "Delete All Data" button
4. Confirm in browser dialog

### Step 4: Review Results
- See status for each table
- Verify all show "✓ Cleaned"
- Read next steps for production deployment

## 🚀 Production Deployment Guide

After cleaning your local database, follow these steps:

### 1. Export Clean Database
```bash
# Using command line (from xampp/mysql/bin/)
mysqldump -u root -p tsn_database > tsn_clean.sql

# Or use phpMyAdmin
# Select database → Export → Go
```

### 2. Package Files
```bash
# Zip entire WordPress installation
# Exclude: wp-content/uploads/* (upload separately if needed)
```

### 3. Prepare wp-config.php
Update for production:
```php
// Database credentials
define('DB_NAME', 'production_db_name');
define('DB_USER', 'production_db_user');
define('DB_PASSWORD', 'production_password');
define('DB_HOST', 'localhost');

// Security keys - GENERATE NEW ONES!
// Get from: https://api.wordpress.org/secret-key/1.1/salt/

// Disable debug mode
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
```

### 4. Upload to Server
- Transfer files via FTP/SFTP
- Import clean database
- Update wp-config.php with production credentials

### 5. Update URLs
Use plugin like "Better Search Replace" or WP-CLI:
```bash
wp search-replace 'http://localhost/tsn' 'https://yourdomain.com' --all-tables
```

### 6. Final Checks
- [ ] Test member registration
- [ ] Test OTP email sending (will work automatically!)
- [ ] Test event creation
- [ ] Test donation form
- [ ] Verify all pages load correctly

## 🛡️ Safety Tips

### Before Running Cleanup
1. **Backup First**: Export your database before cleanup (just in case)
2. **Test Thoroughly**: Make sure everything works in local before deploying
3. **Document Workflows**: Note any manual setup needed

### For Production Deployment
1. **Never run cleanup tool on production** (it's protected, but be careful)
2. **Use proper deployment workflow** (staging → production)
3. **Test in staging first** if possible
4. **Keep backups** of production database

## 🔄 When to Use This Tool

### Good Use Cases
✅ **Before initial production deployment** → Clean test data  
✅ **After major testing phase** → Start fresh  
✅ **Before demos** → Remove messy test data  
✅ **Development reset** → Start new testing cycle

### Bad Use Cases
❌ **On production server** → Risk of data loss  
❌ **Without backups** → Can't undo  
❌ **Just to test** → Use copy of database instead  
❌ **With real member data** → You'll lose real information

## 📊 Example Output

### Before Cleanup
```
Members:                    156 records
Member Transactions:         42 records
Events:                      12 records
Event Ticket Types:          36 records
Orders:                      89 records
Order Items:                203 records
Tickets:                    245 records
Donations:                   34 records
Donation Causes:              5 records
────────────────────────────────────────
TOTAL:                      822 records
```

### After Cleanup
```
All TSN data has been successfully removed!

✓ Members - Cleaned
✓ Member Transactions - Cleaned
✓ Events - Cleaned
✓ Event Ticket Types - Cleaned
✓ Orders - Cleaned
✓ Order Items - Cleaned
✓ Tickets - Cleaned
✓ Donations - Cleaned
✓ Donation Causes - Cleaned
✓ Transients - Cleared
```

## ⚡ Quick Reference

| Action | URL |
|--------|-----|
| **Run Cleanup** | `http://localhost/tsn/wp-content/plugins/tsn-modules/tools/cleanup-database.php` |
| **Back to Admin** | After cleanup, click "Back to Dashboard" |
| **Re-run** | Click "Run Again" to see current status |

## 🆘 Troubleshooting

### "Access Denied"
**Problem**: Not logged in as admin  
**Solution**: Log in to WordPress admin first, then access tool

### "Production Environment Detected"
**Problem**: Tool thinks you're on production  
**Solution**: Good! It's protecting you. Only bypass if absolutely necessary with `?allow_production=1`

### Table Not Found Error
**Problem**: TSN tables don't exist yet  
**Solution**: Activate TSN Modules plugin to create tables first

### Can't Access Tool
**Problem**: 404 error  
**Solution**: Check file path, ensure `tools` folder exists in plugin

## 📝 Notes

- **Recovery**: If you accidentally delete data, restore from backup immediately
- **Automation**: This tool can be run multiple times safely
- **Development Cycle**: Run this between major development phases
- **Documentation**: Keep notes on any custom data needed for setup

---

## Summary

The Database Cleanup Tool is your friend for:
1. Removing test data before production
2. Keeping development environment clean  
3. Preparing for fresh deployments

**Remember**: Always backup before cleanup, and never run on production unless you absolutely know what you're doing!
