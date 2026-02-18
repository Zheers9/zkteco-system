# Excel Import Template for University Users (Staff)

## File Format
- **File Type**: Excel (.xlsx or .xls)
- **First Row**: Header row (will be skipped during import)

## Column Structure

| Column | Name | Description | Required | Example |
|--------|------|-------------|----------|---------|
| A (1) | Name | Full name of the staff member | Yes | John Doe |
| B (2) | Staff ID | Unique staff identifier | Yes | ST12345 |
| C (3) | Device User ID | ID from device_users table | No | 42 or "Not Assigned" |

## Column 3 (Device User ID) Rules
- **If numeric value**: The system will assign this device user ID to the staff member
  - Example: `42` will link to device_users.id = 42
  - The system validates that this ID exists in the device_users table
  - If ID doesn't exist, the row will be skipped
  
- **If "Not Assigned" or empty**: The staff member will be created without a device user assignment
  - `device_user_id` will be set to `NULL`
  - You can assign a device user later through the UI

## Sample Excel Content

```
| Name          | Staff ID | Device User ID |
|---------------|----------|----------------|
| John Doe      | ST12345  | 15             |
| Jane Smith    | ST12346  | Not Assigned   |
| Bob Johnson   | ST12347  | 23             |
| Alice Brown   | ST12348  |                |
| Charlie Davis | ST12349  | 8              |
```

## Import Behavior

### Success Cases
- New staff members with unique Staff IDs are imported
- Valid Device User IDs are assigned
- "Not Assigned" or empty values create unassigned staff

### Skipped Cases
- Duplicate Staff IDs (already exists in database)
- Invalid Device User IDs (ID doesn't exist in device_users table)

### Import Result
After import, you'll see a message like:
- "Successfully imported 15 users. 3 skipped (duplicates)."

## How to Get Device User IDs

1. Go to **"Device Users"** page in the sidebar
2. The ID is shown in the blue badge in the first column
3. Note the IDs you want to assign
4. Use these IDs in Column 3 of your Excel file

## Steps to Import

1. Prepare your Excel file following the template above
2. Go to **University Users (Staff)** page
3. Click **"Import Excel"** button
4. Select your Excel file
5. Review the preview (first 5 rows)
6. Click **"Import"** button
7. Wait for confirmation message

## Tips
- Always keep the header row in your Excel file
- Staff IDs must be unique across all imports
- You can import staff without device assignments and assign them later
- The system will tell you which rows were skipped and why
