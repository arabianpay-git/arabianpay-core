<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background-color:#f4f4f5; font-family: Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5; padding:40px 0;">
        <tr>
            <td align="center">

                {{-- Outer Container --}}
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 0 5px rgba(0,0,0,0.1);">
                    {{-- Header --}}
                    <tr>
                        <td>
                            @include('emails.layouts.header')
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding:40px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td>
                            @include('emails.layouts.footer')
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>

</html>
