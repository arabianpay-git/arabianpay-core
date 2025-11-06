<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Account Approved</title>
    <style>
        body,
        p,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            color: #444444;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f7fa;
            padding: 40px 0;
            -webkit-text-size-adjust: 100%;
        }

        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .email-header {
            background-color: #004aad;
            padding: 24px 40px;
            text-align: center;
        }

        .email-header img {
            max-height: 60px;
            width: auto;
            display: inline-block;
            filter: brightness(110%);
        }

        .email-body {
            padding: 40px;
            color: #333333;
        }

        .email-body h1 {
            font-weight: 700;
            font-size: 28px;
            color: #004aad;
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }

        .email-body p {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555555;
        }

        .email-body a.button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #004aad;
            color: #ffffff !important;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(0, 74, 173, 0.3);
            transition: background-color 0.3s ease;
        }

        .email-body a.button:hover {
            background-color: #003380;
        }

        .email-footer {
            text-align: center;
            font-size: 13px;
            color: #999999;
            padding: 24px 40px;
            background-color: #f9fafb;
            border-top: 1px solid #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .email-footer a {
            color: #004aad;
            text-decoration: none;
        }

        @media only screen and (max-width: 620px) {
            .email-content {
                width: 90% !important;
                margin: 0 auto !important;
            }

            .email-header,
            .email-body,
            .email-footer {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }

            .email-body h1 {
                font-size: 24px !important;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-content" role="article" aria-roledescription="email" aria-label="Account Approved Email">
            <div class="email-header">
                <img src="{{ asset('assets/media/images/logo.png') }}" alt="ArabianPay Logo" />
            </div>
            <div class="email-body">
                <h1>Hello {{ $name ?? 'Valued Customer' }}, Welcome to ArabianPay!</h1>
                <p>Your account has been <strong>Approved</strong>. You can now access all features and start using our
                    services.</p>
                <p>If you have any questions, feel free to contact our support team.</p>
                <a href="{{ url('/') }}" class="button" target="_blank" rel="noopener">Visit Website</a>
            </div>
            <div class="email-footer">
                &copy; {{ date('Y') }} ArabianPay. All rights reserved.<br />
                King Fahd Rd, Al Olaya, Riyadh 12311<br />
                <a href="mailto:support@arabianpay.com">support@arabianpay.com</a>
            </div>
        </div>
    </div>
</body>

</html>
