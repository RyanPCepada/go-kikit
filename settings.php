<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #031525;
            color: #b5e3ff;
            display: flex;
            flex-direction: column; /* Ensures vertical alignment */
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .message-container {
            text-align: center;
            background-color: #0d2136;
            border: 2px solid #1e2a44;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        p {
            font-size: 18px;
            margin-bottom: 0;
        }
        .go-back-container {
            margin-top: 20px; /* Space between message container and button */
        }
        .go-back-button {
            display: inline-block;
            padding: 10px 20px;
            color: #b5e3ff;
            background-color: #1e2a44;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        .go-back-button:hover {
            background-color: #7cacf8;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <h1><i class="fas fa-cogs"></i> Settings</h1>
        <p>This page is under development.</p>
    </div>
    <div class="go-back-container">
        <button class="go-back-button" onclick="history.back()">Go Back</button>
    </div>
</body>
</html>
