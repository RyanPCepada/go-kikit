<!-- <?php
session_start();

// Include config file
require_once "config.php";

// Initialize the history session variable if not set
if (!isset($_SESSION['questions_history'])) {
    $_SESSION['questions_history'] = [];
}

// Fetch the history from session
$questions_history = $_SESSION['questions_history'];

// Reverse the order to display latest first
$questions_history = array_reverse($questions_history);

// Initialize counters
$correct_count = 0;
$wrong_count = 0;
$total_count = count($questions_history);

// Count correct and wrong entries
foreach ($questions_history as $entry) {
    if ($entry['status'] === 'correct') {
        $correct_count++;
    } elseif ($entry['status'] === 'wrong') {
        $wrong_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Questions History</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <style>
        .fixed-back-button {
            position: fixed;
            top: 10px;
            z-index: 1000; /* Ensure it is above other content */
            background-color: white;
            border-radius: 50%;
            transition: box-shadow 0.3s; /* Smooth transition for box-shadow */
        }
        .scrolled {
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); /* Drop shadow */
        }
        .container-fluid {
            margin-top: 10px;
            max-width: 600px;
            min-height: 500px;
            position: relative;
        }
        .summary {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .summary .status-icon-correct {
            color: #007bff;
            font-size: 20px;
        }
        .summary .status-icon-wrong {
            color: #dc3545; /* Soft Red */
            font-size: 20px;
        }
        .summary p {
            margin: 0;
            margin-left: 5px;
            font-size: 20px;
        }
        .history-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .history-item h4 {
            color: #17a2b8;
            margin-top: 0;
            margin-bottom: 0px;
            font-size: 16px;
            font-weight: bold;
        }
        .history-item p {
            font-weight: bold;
        }
        .history-item hr {
            margin-top: 0;
            margin-bottom: 5px;
        }
        .history-item p {
            margin-top: 0;
            margin-bottom: 5px !important;
            font-size: 16px;
        }
        .history-item p {
            margin: 0;
        }
        .status-gif {
            margin-top: -10px;
            margin-right: -10px;
            width: 20px;
            height: auto;
            float: inline-end;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="mt-1 mb-3 clearfix">
                    <button class="fixed-back-button btn btn-transparent" onclick="window.location.href='./questions_game.php';">
                        <i class="fas fa-arrow-left" style="font-size: 14px;"></i>
                    </button>
                    <h2 class="text-center mb-0 mt-5">Previous Questions</h2>
                </div>
                <div class="summary">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check status-icon-correct mt-1" alt="Correct"></i>
                        <p><?php echo $correct_count; ?></p>
                    </div>
                    <div class="d-flex align-items-center ml-4 mr-4">
                        <i class="fas fa-times status-icon-wrong mt-1" alt="Wrong"></i>
                        <p><?php echo $wrong_count; ?></p>
                    </div>
                    <div class="d-flex align-items-center">
                        <strong><p>= <?php echo $total_count; ?></p></strong>
                    </div>
                </div>
                <?php if (!empty($questions_history)): ?>
                    <?php $question_number = 1; // Initialize question number ?>
                    <?php foreach ($questions_history as $entry): ?>
                        <div class="history-item">
                            <?php if ($entry['status'] === 'correct'): ?>
                                <img src="../go-kikit/icons/GIF_CORRECT.gif" alt="Correct" class="status-gif">
                            <?php else: ?>
                                <img src="../go-kikit/icons/GIF_WRONG.gif" alt="Wrong" class="status-gif">
                            <?php endif; ?>
                            <h4>Question <?php echo $total_count - $question_number + 1; ?>:</h4> <!-- Show question number in reverse order -->
                            <p><?php echo htmlspecialchars($entry['question']); ?></p>
                            <hr>
                            <h4>Your Answer:</h4>
                            <p><?php echo htmlspecialchars($entry['your_answer']); ?></p>
                            <hr>
                            <h4>Correct Answer:</h4>
                            <p><?php echo htmlspecialchars($entry['correct_answer']); ?></p>
                        </div>
                        <?php $question_number++; // Increment question number ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">No answered questions yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            $(window).on('scroll', function() {
                if ($(this).scrollTop() > 0) {
                    $('.fixed-back-button').addClass('scrolled');
                } else {
                    $('.fixed-back-button').removeClass('scrolled');
                }
            });
        });
    </script>
</body>
</html> -->



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
        <h1><i class="fas fa-tools"></i> Previous<br>Questions</h1>
        <p>This page is under development.</p>
    </div>
    <div class="go-back-container">
        <button class="go-back-button" onclick="history.back()">Go Back</button>
    </div>
</body>
</html>
