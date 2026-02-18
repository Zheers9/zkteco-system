@extends('layouts.master')

@section('title', 'Transfer Data Between Devices')

@section('content')
    <div class="card">
        <div style="margin-bottom: 2rem;">
            <h2 style="font-size:1.5rem; font-weight:600; margin-bottom:1rem;">Transfer Users & Fingerprints</h2>
            <div style="color:var(--text-muted);">
                Select a source device to load users from, then select expected target devices to copy users to.
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 2fr; gap:2rem;">
            <!-- Left Column: Source & Targets -->
            <div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">1. Source Device</label>
                    <select id="source_device" class="form-control" onchange="loadUsers()">
                        <option value="">-- Select Source Device --</option>
                        @foreach($devices as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->ip }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">3. Target Device(s)</label>
                    <div style="border:1px solid #ddd; padding:1rem; border-radius:4px; max-height:300px; overflow-y:auto;">
                        @foreach($devices as $d)
                            <div class="form-check" style="margin-bottom:0.5rem;">
                                <input class="form-check-input target-device-checkbox" type="checkbox" value="{{ $d->id }}"
                                    id="target_{{ $d->id }}">
                                <label class="form-check-label" for="target_{{ $d->id }}">
                                    {{ $d->name }} <span
                                        style="font-size:0.85rem; color:var(--text-muted);">({{ $d->ip }})</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted);">
                        Select one or more devices to copy users to.
                    </div>
                </div>

                <button id="btn-transfer" class="btn btn-primary" style="width:100%;" onclick="startTransfer()" disabled>
                    <i class="ri-upload-cloud-line"></i> Start Transfer
                </button>
            </div>

            <!-- Right Column: Users List -->
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <label style="font-weight:600;">2. Select Users to Transfer</label>
                    <div style="font-size:0.9rem;">
                        <span id="user-count">0</span> users found
                    </div>
                </div>

                <div style="border:1px solid #ddd; border-radius:4px; overflow:hidden;">
                    <div style="background:#f8f9fa; padding:0.75rem; border-bottom:1px solid #ddd; display:flex; gap:1rem;">
                        <input type="checkbox" id="select-all-users" onchange="toggleAllUsers(this)" disabled>
                        <span style="font-weight:600;">Select All</span>
                    </div>
                    <div id="users-list-container" style="height:400px; overflow-y:auto; padding:0;">
                        <div style="padding:2rem; text-align:center; color:var(--text-muted);">
                            Please select a source device first.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Progress Modal/Overlay -->
    <div id="transfer-overlay"
        style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding:2rem; border-radius:8px; width:400px; max-width:90%;">
            <h3 style="margin-top:0;">Transferring Data...</h3>
            <div style="margin-bottom:1rem;" id="transfer-status">Initializing...</div>
            <div style="background:#e9ecef; border-radius:4px; overflow:hidden; height:10px; margin-bottom:1rem;">
                <div id="transfer-progress-bar"
                    style="width:0%; height:100%; background:var(--primary); transition:width 0.3s;"></div>
            </div>
            <button id="btn-close-overlay" class="btn btn-secondary" style="width:100%; display:none;"
                onclick="closeOverlay()">Close</button>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        let currentUsers = [];

        function loadUsers() {
            const deviceId = document.getElementById('source_device').value;
            const container = document.getElementById('users-list-container');
            const selectAll = document.getElementById('select-all-users');
            const btnTransfer = document.getElementById('btn-transfer');
            const countSpan = document.getElementById('user-count');

            // Reset
            container.innerHTML = '<div style="padding:2rem; text-align:center; color:var(--text-muted);"><span class="loading-spinner"></span> Loading users from device...</div>';
            selectAll.disabled = true;
            selectAll.checked = false;
            btnTransfer.disabled = true;
            countSpan.innerText = '0';
            currentUsers = [];

            // Disable target checkboxes that are same as source
            document.querySelectorAll('.target-device-checkbox').forEach(cb => {
                cb.disabled = (cb.value == deviceId);
                if (cb.value == deviceId) cb.checked = false;
            });

            if (!deviceId) {
                container.innerHTML = '<div style="padding:2rem; text-align:center; color:var(--text-muted);">Please select a source device first.</div>';
                return;
            }

            fetch(`/devices/${deviceId}/fetch-users`)
                .then(r => r.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON:', text);
                        throw new Error('Server returned invalid JSON response');
                    }
                }))
                .then(data => {
                    if (data.success) {
                        currentUsers = data.users;
                        countSpan.innerText = currentUsers.length;
                        renderUsers(currentUsers);
                        selectAll.disabled = false;
                        btnTransfer.disabled = false;
                    } else {
                        container.innerHTML = `<div style="padding:2rem; text-align:center; color:var(--danger);">Error: ${data.message}</div>`;
                    }
                })
                .catch(e => {
                    console.error(e);
                    container.innerHTML = `<div style="padding:2rem; text-align:center; color:var(--danger);">Error loading users. Check console.</div>`;
                });
        }

        function renderUsers(users) {
            const container = document.getElementById('users-list-container');

            if (!Array.isArray(users)) {
                console.error('Expected array of users, got:', users);
                container.innerHTML = '<div style="padding:2rem; text-align:center; color:var(--danger);">Invalid data format received.</div>';
                return;
            }

            if (users.length === 0) {
                container.innerHTML = '<div style="padding:2rem; text-align:center; color:var(--text-muted);">No users found on this device.</div>';
                return;
            }

            let html = '<table style="width:100%;">';
            users.forEach(u => {
                html += `
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:0.75rem; width:40px; text-align:center;">
                                    <input type="checkbox" class="user-checkbox" value="${u.userid}">
                                </td>
                                <td style="padding:0.75rem;">
                                    <div style="font-weight:500;">${u.name || 'Unknown'}</div>
                                    <div style="font-size:0.85rem; color:var(--text-muted);">ID: ${u.userid} | Role: ${u.role}</div>
                                </td>
                            </tr>
                        `;
            });
            html += '</table>';
            container.innerHTML = html;
        }

        function toggleAllUsers(checkbox) {
            document.querySelectorAll('.user-checkbox').forEach(cb => {
                cb.checked = checkbox.checked;
            });
        }

        function startTransfer() {
            const sourceId = document.getElementById('source_device').value;
            const targetIds = Array.from(document.querySelectorAll('.target-device-checkbox:checked')).map(cb => cb.value);
            const userIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);

            if (!sourceId) return alert('Select source device.');
            if (targetIds.length === 0) return alert('Select at least one target device.');
            if (userIds.length === 0) return alert('Select at least one user.');

            if (!confirm(`Transfer ${userIds.length} users to ${targetIds.length} devices?`)) return;

            // Show Overlay
            const overlay = document.getElementById('transfer-overlay');
            const status = document.getElementById('transfer-status');
            const progressBar = document.getElementById('transfer-progress-bar');
            const btnClose = document.getElementById('btn-close-overlay');

            overlay.style.display = 'flex';
            status.innerText = 'Initializing transfer...';
            progressBar.style.width = '0%';
            btnClose.style.display = 'none';

            // Send Request
            fetch('/devices/transfer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    source_device_id: sourceId,
                    target_device_ids: targetIds,
                    user_ids: userIds
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        progressBar.style.width = '100%';
                        progressBar.style.background = 'var(--success)';
                        status.innerText = `Success! Transferred ${data.transferred_count} users.`;
                    } else {
                        progressBar.style.background = 'var(--danger)';
                        status.innerText = 'Error: ' + data.message;
                    }
                })
                .catch(e => {
                    progressBar.style.background = 'var(--danger)';
                    status.innerText = 'Network Error';
                    console.error(e);
                })
                .finally(() => {
                    btnClose.style.display = 'block';
                });
        }

        function closeOverlay() {
            document.getElementById('transfer-overlay').style.display = 'none';
        }
    </script>
@endpush