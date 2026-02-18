<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }

        .status-present {
            color: #10b981;
            font-weight: bold;
        }

        .status-permission {
            color: #3b82f6;
            font-weight: bold;
        }

        .status-late {
            color: #f59e0b;
            font-weight: bold;
        }

        .status-absent {
            color: #ef4444;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div style="text-align: center; margin-bottom: 20px;">
        <h2>Attendance Payroll Report</h2>
        <p>{{ $startDate }} to {{ $endDate }}</p>
    </div>

    @foreach($weeks as $weekIndex => $week)
        <div style="margin-top: 20px; page-break-inside: avoid;">
            <h3 style="background-color: #e5e7eb; padding: 10px; border: 1px solid #000; margin-bottom: 0;">
                Week: {{ $week['label'] }}
            </h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">Date / Details</th>
                        <th style="width: 30%;">Employee</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 35%;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrollData as $user)
                            @php 
                                $slots = $user['weeks'][$weekIndex] ?? [];
                            @endphp
                        @foreach($slots as $slot)
                            <tr>
                                <td>{{ $slot['details'] }}</td>
                                            <td>{{ $user['name'] }}</td>
                                            <td style="background-color: {{ 
                                                $slot['status'] == 'Absent' ? '#fee2e2' :
                            ($slot['status'] == 'Late' ? '#fef3c7' :
                                ($slot['status'] == 'Permission' ? '#dbeafe' : '#d1fae5')) 
                                            }}">
                                                <span class="{{ 
                                                    $slot['status'] == 'Absent' ? 'status-absent' :
                            ($slot['status'] == 'Late' ? 'status-late' :
                                ($slot['status'] == 'Permission' ? 'status-permission' : 'status-present')) 
                                                }}">
                                        {{ $slot['status'] }}
                                    </span>
                                </td>
                                <td>
                                <!-- Use details or custom notes if available, currently mostly in details -->
                                    @if(\Illuminate\Support\Str::contains($slot['details'], '('))
                                        {{ \Illuminate\Support\Str::before(\Illuminate\Support\Str::after($slot['details'], '('), ')') }}
                                    @elseif($slot['status'] == 'Permission')
                                        Permission Granted
                                    @endif
                                    </td>
                                    </tr>
                        @endforeach
                    @endforeach
                </tbody>
        </table>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>

         @endif
    @endforeach
</body>
</html>