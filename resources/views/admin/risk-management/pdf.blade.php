<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Risk Scores Report</title>
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

    <h1 style="font-weight:700; font-size:16px; color:#34495e;">Risk Scores Report</h1>

    <!-- Risk Scores Table -->
    <table width="100%" cellspacing="0" cellpadding="4"
        style="border-collapse:collapse; font-size:9px; page-break-inside:auto;">
        <thead>
            <tr style="background-color:#34495e; color:#fff; text-align:left;">
                <th style="border:1px solid #ddd; padding:4px;">ID</th>
                <th style="border:1px solid #ddd; padding:4px;">Name</th>
                <th style="border:1px solid #ddd; padding:4px;">Business Name</th>
                <th style="border:1px solid #ddd; padding:4px;">CR Number</th>
                <th style="border:1px solid #ddd; padding:4px;">ID Number</th>
                <th style="border:1px solid #ddd; padding:4px;">CR/ID Match Score</th>
                <th style="border:1px solid #ddd; padding:4px;">ID Expiry Score</th>
                <th style="border:1px solid #ddd; padding:4px;">CR Expiry Score</th>
                <th style="border:1px solid #ddd; padding:4px;">Business Type Score</th>
                <th style="border:1px solid #ddd; padding:4px;">Activity Score</th>
                <th style="border:1px solid #ddd; padding:4px;">CR/ID Total</th>
                <th style="border:1px solid #ddd; padding:4px;">CR/ID Score</th>
                <th style="border:1px solid #ddd; padding:4px;">POS Revenue</th>
                <th style="border:1px solid #ddd; padding:4px;">POS Score</th>
                <th style="border:1px solid #ddd; padding:4px;">Late Payments</th>
                <th style="border:1px solid #ddd; padding:4px;">Repayment Score</th>
                <th style="border:1px solid #ddd; padding:4px;">Industry</th>
                <th style="border:1px solid #ddd; padding:4px;">Industry Score</th>
                <th style="border:1px solid #ddd; padding:4px;">City</th>
                <th style="border:1px solid #ddd; padding:4px;">City Tier Score</th>
                <th style="border:1px solid #ddd; padding:4px;">Economic Activity Score</th>
                <th style="border:1px solid #ddd; padding:4px;">Default Rate Score</th>
                <th style="border:1px solid #ddd; padding:4px;">Location Score</th>
                <th style="border:1px solid #ddd; padding:4px;">Total Score</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($risks as $risk)
                <tr style="{{ $loop->even ? 'background-color:#f9f9f9;' : '' }}">
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->id }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->name }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->business_name }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->cr_number }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->id_number }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->cr_id_match_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->id_expiry_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->cr_expiry_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->business_type_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->activity_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->cr_id_total }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->cr_id_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->pos_revenue }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->pos_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->late_payments }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->repayment_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->industry }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->industry_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->location['city'] ?? '-' }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->location['tier_score'] ?? 0 }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->location['activity_score'] ?? 0 }}</td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->location['default_rate_score'] ?? 0 }}
                    </td>
                    <td style="border:1px solid #ddd; padding:4px;">{{ $risk->location_score }}</td>
                    <td style="border:1px solid #ddd; padding:4px; font-weight:700;">{{ $risk->total_score }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="position: fixed; bottom: 10mm; width: 100%; text-align: center; font-size: 10px; color: #aaa;">
        Generated on: {{ now()->format('d M Y H:i') }}
    </div>

</body>

</html>
