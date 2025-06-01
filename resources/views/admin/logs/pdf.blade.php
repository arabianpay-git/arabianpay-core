<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Activity logs</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Arial, sans-serif; font-size:11px; color:#2d3436; background:#fff; line-height:1.4;">

    <!-- Header -->
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:15px;">
        <tr>
            <td align="center" style="padding-bottom:10px;">
                <img src="{{ public_path('assets/media/images/logo.png') }}" alt="Company Logo"
                    style="height:50px; max-width:200px; object-fit:contain;" />
            </td>
        </tr>
    </table>

    <h1 style="font-weight:700; font-size:16px; color:#34495e;">Activity logs</h1>

    <!-- Risk Scores Table -->
    <table width="100%" cellspacing="0" cellpadding="4"
        style="border-collapse:collapse; font-size:9px; page-break-inside:auto;">
        <thead>
            <tr style="background-color:#34495e; color:#fff; text-align:left;">
                <th>#</th>
                <th style="width: 20%;">Event</th>
                <th style="width: 20%;">User</th>
                <th style="width: 20%;">Description</th>
                <th style="width: 20%;">Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr style="{{ $loop->even ? 'background-color:#f9f9f9;' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td style="color:{{ $log->event == 'create' ? '#2ecc71' : ($log->event == 'update' ? '#f39c12' : ($log->event == 'delete' ? '#e74c3c' : '#3498db')) }};">
                        {{ ucfirst($log->event) }}
                    </td>
                    <td>{{ $log->causer->first_name ?? 'System' }}</td>
                    <td>{{ $log->description }}
                        <br>
                        @if($log->properties)
                            @foreach($log->properties->toArray() as $key => $value)
                            <li>
                                <strong>{{ $key }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}
                            </li>
                            @endforeach
                        @endif
                    </td>
                    <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="position: fixed; bottom: 10mm; width: 100%; text-align: center; font-size: 10px; color: #aaa;">
        Generated on: {{ now()->format('d M Y H:i') }}
    </div>

</body>

</html>
