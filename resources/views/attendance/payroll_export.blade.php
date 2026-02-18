<table>
    <thead>
        <tr>
            <th colspan="{{ count($weeks) * $requiredDays + 5 }}">
                Attendance Payroll Report ({{ $startDate }} to {{ $endDate }})
            </th>
        </tr>
        <tr>
            <th style="font-weight:bold; border:1px solid #000;">Employee</th>
            @foreach($weeks as $week)
                <th colspan="{{ $requiredDays }}" style="text-align:center; font-weight:bold; border:1px solid #000;">
                    {{ $week['label'] }}
                </th>
            @endforeach
            <th colspan="4" style="text-align:center; font-weight:bold; border:1px solid #000;">Summary</th>
        </tr>
        <tr>
            <th style="border:1px solid #000;"></th>
            @foreach($weeks as $week)
                @for($i = 1; $i <= $requiredDays; $i++)
                    <th style="text-align:center; border:1px solid #000; font-size:10px;">Day {{ $i }}</th>
                @endfor
            @endforeach
            <th style="border:1px solid #000; font-weight:bold; color:#10b981;">Present</th>
            <th style="border:1px solid #000; font-weight:bold; color:#3b82f6;">Perm.</th>
            <th style="border:1px solid #000; font-weight:bold; color:#f59e0b;">Late</th>
            <th style="border:1px solid #000; font-weight:bold; color:#ef4444;">Absent</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payrollData as $row)
            <tr>
                <td style="font-weight:bold; border:1px solid #000;">{{ $row['name'] }}</td>
                @foreach($row['weeks'] as $slots)
                    @foreach($slots as $slot)
                        <td style="text-align:center; border:1px solid #000; background-color: {{ 
                                                        $slot['status'] == 'Absent' ? '#fdcbcb' :
                            ($slot['status'] == 'Late' ? '#ffeeba' :
                                ($slot['status'] == 'Permission' ? '#dbeafe' : '#c3e6cb')) 
                                                    }}">
                            {{ $slot['details'] }}
                        </td>
                    @endforeach
                @endforeach
                <td style="text-align:center; border:1px solid #000; font-weight:bold; color:#10b981;">
                    {{ $row['summary']['present'] }}
                </td>
                <td style="text-align:center; border:1px solid #000; font-weight:bold; color:#3b82f6;">
                    {{ $row['summary']['permission'] }}
                </td>
                <td style="text-align:center; border:1px solid #000; font-weight:bold; color:#f59e0b;">
                    {{ $row['summary']['late'] }}
                </td>
                <td style="text-align:center; border:1px solid #000; font-weight:bold; color:#ef4444;">
                    {{ $row['summary']['absent'] }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>