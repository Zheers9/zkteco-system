# University Users (Staff) Feature

## Overview
This feature allows you to manage university staff members and link them to device users from ZKTeco fingerprint devices. Staff can be added individually or imported in bulk via Excel.

## Database Structure

### Tables Created
1. **university_users**
   - `id` - Primary key
   - `name` - Staff member's full name
   - `user_sid` - Staff ID (unique)
   - `device_user_id` - Foreign key to device_users.id (nullable)
   - `created_at`, `updated_at`

### Relationships
- `university_users.device_user_id` → `device_users.id` (many-to-one)
- When a device user is deleted, the university user's `device_user_id` is set to NULL

## Features

### 1. Device Users Page (`/device-users`)
- View all users synced from all devices
- Shows: ID, Device name, User ID on device, Name, Role, Card number
- "Sync Users from Devices" button (redirects to devices page)

### 2. University Users Page (`/university-users`)
- **Add Staff Member**: Create new staff members with name and staff ID
- **Import Excel**: Bulk import staff from Excel file
  - Supports .xlsx and .xls formats
  - Skips first row (header)
  - Column 1: Name, Column 2: Staff ID, Column 3: Device User ID (number or "Not Assigned")
  - Preview first 5 rows before import
  - Validates device user IDs exist
  - Skips duplicate staff IDs
  - Shows import summary (imported count, skipped count)
- **Status Column**: Shows "Assigned" (green) or "Unassigned" (yellow) based on device_user_id
- **Assign/Reassign**: Popup modal to enter device user ID
  - Validates that the device user ID exists
  - Shows error if ID not found
  - Displays success message with device user name
- **Delete**: Remove staff members

## How to Use

### Step 1: Sync Device Users
1. Go to "Devices" page
2. Click "Manage" on a device
3. Click "Sync Users" to import users from the physical device
4. Users are saved to `device_users` table

### Step 2: View Device Users
1. Go to "Device Users" in sidebar
2. Find the ID of the device user you want to link
3. Note the ID number (shown in blue badge)

### Step 3: Add Staff Members

#### Option A: Add Individual Staff Member
1. Go to "University Users" in sidebar
2. Click "Add User"
3. Enter name and staff ID
4. Click "Assign" button next to the user
5. Enter the device user ID from Step 2
6. System validates the ID and creates the link

#### Option B: Import from Excel
1. Prepare Excel file with columns: Name, Staff ID, Device User ID
   - See `EXCEL_IMPORT_TEMPLATE.md` for detailed format
2. Go to "University Users" in sidebar
3. Click "Import Excel"
4. Select your Excel file
5. Review the preview (first 5 rows)
6. Click "Import"
7. Review import summary (imported/skipped counts)

## Routes
- `GET /device-users` - List all device users
- `GET /university-users` - List all university users
- `POST /university-users` - Create new university user
- `POST /university-users/import` - Bulk import from Excel
- `POST /university-users/{id}/assign` - Assign device user
- `DELETE /university-users/{id}` - Delete university user

## Files Created/Modified

### Controllers
- `app/Http/Controllers/UniversityUserController.php` (new)
- `app/Http/Controllers/DeviceController.php` (added `allUsers` method)

### Models
- `app/Models/UniversityUser.php` (new)

### Views
- `resources/views/device_users/index.blade.php` (new)
- `resources/views/university_users/index.blade.php` (new)
- `resources/views/layouts/master.blade.php` (updated sidebar)

### Migrations
- `database/migrations/2026_01_19_071742_create_university_users_table.php` (new)

### Routes
- `routes/web.php` (added university users and device users routes)
