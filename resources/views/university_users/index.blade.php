@extends('layouts.master')

@section('title', 'University Users')

@section('content')
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header" style="display:block;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <h2 class="card-title" style="margin:0;">University Users (Staff)</h2>
                    <div style="display:flex; gap:0.5rem;">
                        <button class="btn btn-success" onclick="openImportModal()">
                            <i class="ri-file-excel-line"></i> Import Excel
                        </button>
                        <button class="btn btn-primary" onclick="openAddUserModal()">
                            <i class="ri-user-add-line"></i> Add User
                        </button>
                    </div>
                </div>
                <p class="card-subtitle" style="margin:0;">Manage university staff and assign them to device users</p>
            </div>

            <!-- Search Bar -->
            <div
                style="padding: 1.5rem; margin-bottom: 0; background: var(--bg-main); border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <form action="{{ route('university-users.index') }}" method="GET"
                    style="display:flex; align-items:stretch; gap:0.75rem; max-width:900px;">
                    <div style="flex:1; position:relative;">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search by name or staff ID..." class="form-control"
                            style="padding:0.625rem 1rem; width:100%; height:100%; font-size:0.95rem; border-radius:10px; background:var(--bg-card); border:1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease;">
                    </div>
                    <div style="position:relative;">
                        <select name="status" class="form-control"
                            style="padding:0.625rem 1rem; width:auto; min-width:160px; height:100%; font-size:0.95rem; border-radius:10px; background:var(--bg-card); border:1px solid rgba(255, 255, 255, 0.1); cursor:pointer;">
                            <option value="">All Status</option>
                            <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="unassigned" {{ request('status') == 'unassigned' ? 'selected' : '' }}>Unassigned
                            </option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="padding:0.625rem 1.5rem; border-radius:10px; font-weight:500; display:flex; align-items:center; gap:0.5rem; white-space:nowrap; height:auto; font-size:0.95rem;">
                        <i class="ri-search-line" style="font-size:1rem;"></i> Search
                    </button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('university-users.index') }}" class="btn btn-secondary"
                            style="padding:0.625rem 1rem; border-radius:10px; display:flex; align-items:center; justify-content:center; height:auto;"
                            title="Clear filters">
                            <i class="ri-close-line" style="font-size:1.1rem;"></i>
                        </a>
                    @endif
                </form>
            </div>

            <div class="table-container" style="margin-top: 1.5rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Staff ID</th>
                            <th>Status</th>
                            <th>Assigned Device User</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td><span class="badge badge-primary">{{ $user->id }}</span></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <i class="ri-user-line" style="color:var(--primary)"></i>
                                        <strong>{{ $user->name }}</strong>
                                    </div>
                                </td>
                                <td><code>{{ $user->user_sid }}</code></td>
                                <td>
                                    @if($user->isAssigned())
                                        <span class="badge badge-success">
                                            <i class="ri-checkbox-circle-line"></i> Assigned
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            <i class="ri-error-warning-line"></i> Unassigned
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->deviceUser)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <i class="ri-fingerprint-line" style="color:var(--success)"></i>
                                            <span>{{ $user->deviceUser->name }} (ID: {{ $user->deviceUser->id }})</span>
                                        </div>
                                    @else
                                        <span style="color:var(--text-muted)">Not assigned</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.5rem;">
                                        @if(!$user->isAssigned())
                                            <button class="btn btn-sm btn-success"
                                                onclick="openAssignModal({{ $user->id }}, '{{ $user->name }}')">
                                                <i class="ri-link"></i> Assign
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-secondary"
                                                onclick="openAssignModal({{ $user->id }}, '{{ $user->name }}')">
                                                <i class="ri-refresh-line"></i> Reassign
                                            </button>
                                        @endif

                                        <form action="{{ route('university-users.destroy', $user) }}" method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm('Are you sure you want to delete this university user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; padding:2rem;">
                                    <i class="ri-user-unfollow-line"
                                        style="font-size:3rem; color:var(--text-muted); opacity:0.5;"></i>
                                    <p style="color:var(--text-muted); margin-top:1rem;">No university users found. Add one to
                                        get started.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


            @if($users->hasPages())
                <div style="margin-top:2rem;">
                    {{ $users->links('pagination.custom') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="ri-user-add-line"></i> Add University User</h3>
                <button class="modal-close" onclick="closeAddUserModal()">&times;</button>
            </div>
            <form action="{{ route('university-users.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="user_sid">Staff ID</label>
                        <input type="text" id="user_sid" name="user_sid" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Device User Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="ri-link"></i> Assign Device User</h3>
                <button class="modal-close" onclick="closeAssignModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Assigning device user to: <strong id="assignUserName"></strong></p>
                <div class="form-group">
                    <label for="device_user_id">Device User ID</label>
                    <input type="number" id="device_user_id" class="form-control" placeholder="Enter device user ID"
                        required>
                    <small style="color:var(--text-muted); margin-top:0.5rem; display:block;">
                        <i class="ri-information-line"></i> You can find device user IDs in the "Device Users" page
                    </small>
                </div>
                <div id="assignError" style="display:none; color:var(--danger); margin-top:1rem;">
                    <i class="ri-error-warning-line"></i> <span id="assignErrorText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitAssignment()">
                    <i class="ri-check-line"></i> Assign
                </button>
            </div>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-main);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: var(--text-muted);
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-main);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-main);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg-main);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-control:hover {
            border-color: rgba(255, 255, 255, 0.2);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }
    </style>

    <script>
        let currentUserId = null;

        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('active');
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.remove('active');
        }

        function openAssignModal(userId, userName) {
            currentUserId = userId;
            document.getElementById('assignUserName').textContent = userName;
            document.getElementById('device_user_id').value = '';
            document.getElementById('assignError').style.display = 'none';
            document.getElementById('assignModal').classList.add('active');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('active');
            currentUserId = null;
        }

        async function submitAssignment() {
            const deviceUserId = document.getElementById('device_user_id').value;
            const errorDiv = document.getElementById('assignError');
            const errorText = document.getElementById('assignErrorText');

            if (!deviceUserId) {
                errorText.textContent = 'Please enter a device user ID';
                errorDiv.style.display = 'block';
                return;
            }

            try {
                const response = await fetch(`/university-users/${currentUserId}/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        device_user_id: deviceUserId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    closeAssignModal();
                    location.reload();
                } else {
                    errorText.textContent = data.message || 'Assignment failed';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorText.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        }

        // Close modals when clicking outside
        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        });

        // Import Excel Functions
        function openImportModal() {
            document.getElementById('importModal').classList.add('active');
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.remove('active');
            document.getElementById('excelFile').value = '';
            document.getElementById('importPreview').style.display = 'none';
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

                // Skip first row (header) and preview first 5 rows
                const preview = jsonData.slice(1, 6);
                displayPreview(preview);
            };
            reader.readAsArrayBuffer(file);
        }

        function displayPreview(data) {
            const previewDiv = document.getElementById('importPreview');
            const previewTable = document.getElementById('previewTable');

            let html = '<tr><th>Name</th><th>Staff ID</th><th>Device User ID</th></tr>';
            data.forEach(row => {
                if (row.length >= 2) {
                    const deviceUserId = row[2] ? (isNaN(row[2]) ? 'Not Assigned' : row[2]) : 'Not Assigned';
                    html += `<tr>
                                                <td>${row[0] || 'N/A'}</td>
                                                <td>${row[1] || 'N/A'}</td>
                                                <td>${deviceUserId}</td>
                                            </tr>`;
                }
            });

            previewTable.innerHTML = html;
            previewDiv.style.display = 'block';
        }

        async function submitImport() {
            const fileInput = document.getElementById('excelFile');
            const file = fileInput.files[0];

            if (!file) {
                showToast('Please select an Excel file', 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = async function (e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

                // Skip first row (header)
                const users = jsonData.slice(1).filter(row => row.length >= 2).map(row => {
                    let deviceUserId = null;

                    // Check if third column exists and is a number
                    if (row[2] && !isNaN(row[2]) && row[2].toString().trim() !== '') {
                        deviceUserId = parseInt(row[2]);
                    }

                    return {
                        name: row[0],
                        user_sid: row[1].toString(),
                        device_user_id: deviceUserId
                    };
                });

                try {
                    const response = await fetch('/university-users/import', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ users })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast(`Successfully imported ${result.imported} users. ${result.skipped} skipped (duplicates).`, 'success');
                        closeImportModal();
                        location.reload();
                    } else {
                        showToast(result.message || 'Import failed', 'error');
                    }
                } catch (error) {
                    showToast('An error occurred during import', 'error');
                }
            };
            reader.readAsArrayBuffer(file);
        }
    </script>

    <!-- Import Excel Modal -->
    <div id="importModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3><i class="ri-file-excel-line"></i> Import Staff from Excel</h3>
                <button class="modal-close" onclick="closeImportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="excelFile">Select Excel File</label>
                    <input type="file" id="excelFile" accept=".xlsx,.xls" class="form-control"
                        onchange="handleFileSelect(event)">
                    <small style="color:var(--text-muted); margin-top:0.5rem; display:block;">
                        <i class="ri-information-line"></i> Excel format: Column 1 = Name, Column 2 = Staff ID, Column 3 =
                        Device User ID (number or "Not Assigned")
                    </small>
                </div>

                <div id="importPreview" style="display:none; margin-top:1rem;">
                    <h4 style="margin-bottom:0.5rem;">Preview (first 5 rows)</h4>
                    <div style="overflow-x:auto;">
                        <table id="previewTable" class="data-table" style="width:100%; font-size:0.9rem;">
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitImport()">
                    <i class="ri-upload-line"></i> Import
                </button>
            </div>
        </div>
    </div>

    <!-- SheetJS Library for Excel parsing -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
@endsection