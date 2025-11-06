{{-- resources/views/emails/simple-message.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $subjectLine }}</title>
</head>

<body style="font-family: Arial, sans-serif; line-height:1.6; color:#111;">
    <h2 style="margin-bottom:12px;">{{ $subjectLine }}</h2>
    <div>
        {!! nl2br(e($bodyMessage)) !!}
    </div>
</body>

</html>
