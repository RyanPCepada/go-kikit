<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Landing Page</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', sans-serif;
            box-sizing: border-box;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #031525; /* Dark navy backdrop color */
            margin: 0;
            padding: 25px;
        }
        .welcome-container {
            width: 100%;
            max-width: 400px;
            padding-bottom: 30px;
            display: block;
            margin: 0 auto;
            background-color: #132a45; /* Slightly lighter navy content background */
            border-radius: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); /* Slight shadow for depth */
            text-align: center;
        }
        .Go-Kikit-ArcUp, .Go-Kikit-ArcDown {
            width: 50%;
            margin: 0px;
        }
        .Welcome-To {
            width: 40%;
            margin: 0px;
        }
        .icon-kikit-face {
            width: 70%;
            margin-bottom: -20px;
            animation: zoomAndWave 1.2s ease-in-out;
            /* animation-play-state: paused;  Start with the animation paused */
        }
        .btn {
            background-color: #1F4A6F;
            border-color: #1A3A5C;
            color: #b5e3ff;
            margin-top: 40px;
        }
        .btn:hover {
            background-color: #2C5B7D;
            border-color: #1A3A5C;
            color: #b5e3ff;
        }
        .btn:focus, .btn:active {
            background-color: #2C5B7D;
            border-color: #7cacf8;
            box-shadow: 0 0 0 0.2rem rgba(124, 172, 248, 0.5); /* Outline on focus */
        }
        
        @keyframes zoomAndWave {
            0% {
                transform: rotate(0deg) scale(1);
            }
            25% {
                transform: rotate(-10deg) scale(1.05);
            }
            50% {
                transform: rotate(10deg) scale(1.05);
            }
            75% {
                transform: rotate(-10deg) scale(1.05);
            }
            100% {
                transform: rotate(0deg) scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-container">
            <img src="icons/ICON_WELCOME_TO.png" alt="Welcome To" class="Welcome-To mt-5 mb-3">
            <!-- <img src="icons/ICON-GO-KIKIT-GOLD-ARCUP.png" alt="Go-Kikit-ArcUp" class="Go-Kikit-ArcUp"> -->
            <img src="icons/ICON_KIKIT.png" alt="Kikit" class="icon-kikit-face">
            <img src="icons/ICON-GO-KIKIT-GOLD-ARCDOWN.png" alt="Go-Study-ArcDown" class="Go-Kikit-ArcDown">
            <br>
            <button onclick="window.location.href='/go-kikit/studies.php'" class="btn">Start!</button>
        </div>
    </div>
</body>
</html>
