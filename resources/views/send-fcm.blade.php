<!DOCTYPE html>
<html lang="en">

<head>
    <title>Send Notification to Other Device</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            max-width: 500px;
            margin: auto;
            background: #f9f9f9;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        input,
        textarea,
        button {
            font-size: 16px;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            background: #007bff;
            color: white;
            cursor: pointer;
            border: none;
        }

        button:hover {
            background: #0056b3;
        }

        #response {
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>Send Notification to Device</h2>

    <form id="sendNotificationForm">
        <input type="text" id="title" placeholder="Notification Title" required>
        <textarea id="body" placeholder="Notification Body" required></textarea>
        <textarea id="device_token" placeholder="Paste Device Token here" required></textarea>
        <button type="submit">Send Notification</button>
    </form>

    <p id="response"></p>

    <script>
        document.getElementById('sendNotificationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            document.getElementById('response').innerText = 'Sending notification...';

            try {
                const response = await fetch("/send-fcm", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                    },
                    body: JSON.stringify({
                        title: document.getElementById('title').value,
                        body: document.getElementById('body').value,
                        device_token: document.getElementById('device_token').value.trim(),
                    }),
                });

                const result = await response.json();

                if (result.message) {
                    document.getElementById('response').innerText = result.message;
                    document.getElementById('response').style.color = 'green';
                } else if (result.error) {
                    document.getElementById('response').innerText = "Error: " + result.error;
                    document.getElementById('response').style.color = 'red';
                    console.error(result.details);
                }
            } catch (err) {
                console.error('Fetch error:', err);
                document.getElementById('response').innerText = 'Failed to send notification.';
                document.getElementById('response').style.color = 'red';
            }
        });
    </script>
</body>

</html>
