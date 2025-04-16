<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Player</title>
</head>
<body>
    <audio id="background-music" autoplay loop>
        <source src="../go-kikit/audio/AUDIO_THISISMYNOW.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const audio = document.getElementById('background-music');
            audio.volume = 0.2; // Set volume to 100%

            // Retrieve saved time and play status
            const savedTime = localStorage.getItem('audioCurrentTime');
            const isPlaying = localStorage.getItem('audioIsPlaying');

            if (savedTime) {
                audio.currentTime = parseFloat(savedTime);
            }

            // Unmute the audio after a short delay
            setTimeout(() => {
                audio.muted = false;
            }, -100); // Positive delay to ensure un-muting works

            // Play the audio if it was playing before
            if (isPlaying === 'true') {
                audio.play().catch(error => {
                    console.error('Error playing audio:', error);
                });
            }

            // Update localStorage whenever the audio time updates
            audio.ontimeupdate = () => {
                localStorage.setItem('audioCurrentTime', audio.currentTime);
            };

            // Save play status when the user interacts with the audio player
            audio.onplay = () => {
                localStorage.setItem('audioIsPlaying', 'true');
            };
            audio.onpause = () => {
                localStorage.setItem('audioIsPlaying', 'false');
            };
        });
    </script>
</body>
</html>
