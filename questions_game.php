<?php
// Start session
session_start();

// Include the configuration file
require_once 'config.php';

// Initialize session variables if not set
if (!isset($_SESSION['question_count'])) {
    $_SESSION['question_count'] = 1; // Starting with the first question
    $_SESSION['score'] = 0;          // Initial score
}

if (!isset($_SESSION['recent_questions'])) {
    $_SESSION['recent_questions'] = []; // Initialize recent questions array
}
$recent_questions_limit = 50; // Limit of recent questions to track

// Handle the welcome modal
if (!isset($_SESSION['welcome_modal_shown'])) {
    $_SESSION['welcome_modal_shown'] = false;
}

// Initialize the history session variable if not set
if (!isset($_SESSION['questions_history'])) {
    $_SESSION['questions_history'] = [];
}

// Get the study_id from session
$study_id = isset($_SESSION['study_id']) ? $_SESSION['study_id'] : 1;

// Query to get the study name
$sql_study_name = "
    SELECT s.title 
    FROM tbl_studies s
    WHERE s.study_id = :study_id
";

try {
    // Prepare and execute the query
    $stmt_study_name = $pdo->prepare($sql_study_name);
    $stmt_study_name->bindParam(':study_id', $study_id, PDO::PARAM_INT);
    $stmt_study_name->execute();
    $study = $stmt_study_name->fetch(PDO::FETCH_ASSOC);
    $study_name = $study['title'] ?? 'Unknown Study'; // Default if not found
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

// Query to get the category, question, choices, and answer
$sql = "
    SELECT q.question_id, q.category, q.question, q.hint, c.choice_id, c.choice, a.answer 
    FROM tbl_questions q
    LEFT JOIN tbl_choices c ON q.question_id = c.question_id
    LEFT JOIN tbl_answers a ON q.question_id = a.question_id
    WHERE q.study_id = :study_id
    ORDER BY q.question_id, c.choice_id
";

try {
    // Prepare and execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':study_id', $study_id, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch all results
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize an array to hold the questions data
    $questions_data = [];

    foreach ($questions as $row) {
        $question_id = $row['question_id'];

        if (!isset($questions_data[$question_id])) {
            $questions_data[$question_id] = [
                'question_id' => $question_id,
                'category' => $row['category'],
                'question' => $row['question'],
                'hint' => $row['hint'],
                'choices' => [],
                'answer' => $row['answer'],
            ];
        }

        if (!empty($row['choice'])) {
            $questions_data[$question_id]['choices'][] = [
                'choice_id' => $row['choice_id'],
                'choice' => $row['choice'],
            ];
        }
    }

    // Shuffle the questions
    $questions_data = array_values($questions_data);
    shuffle($questions_data);

    // Shuffle choices for each question
    foreach ($questions_data as &$question) {
        shuffle($question['choices']);
    }

    // Store shuffled questions in session
    $_SESSION['questions_data'] = $questions_data;

    // Initialize the current question index
    if (!isset($_SESSION['current_question_index'])) {
        $_SESSION['current_question_index'] = 0;
    }

} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

// Fetch the profile picture of the logged-in user
$sql_profile = "SELECT profilePic FROM tbl_creator WHERE study_id = :study_id";
try {
    $stmt_profile = $pdo->prepare($sql_profile);
    $stmt_profile->bindParam(':study_id', $study_id, PDO::PARAM_INT); // Correct parameter
    $stmt_profile->execute();
    $user = $stmt_profile->fetch(PDO::FETCH_ASSOC);
    $profilePic = $user['profilePic'] ?? 'default_profile_pic.png'; // Default image if none is found
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

// Get the current question
$current_question_index = $_SESSION['current_question_index'] ?? 0;
$current_question_data = $_SESSION['questions_data'][$current_question_index] ?? null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve submitted data
    $selected_answer = $_POST['selected_answer'] ?? '';
    $correct_answer = $_POST['answer'] ?? '';
    $question_id = $_POST['question_id'] ?? '';

    // Check if the selected answer matches the correct answer
    if (strtolower($selected_answer) === strtolower($correct_answer)) {
        $_SESSION['score']++; // Increment score if the answer is correct
        $answer_status = 'correct';
    } else {
        $answer_status = 'wrong';
    }

    // Update question count and current question index only if form is submitted
    $_SESSION['question_count']++;
    $_SESSION['current_question_index']++;
    if ($_SESSION['current_question_index'] >= count($_SESSION['questions_data'])) {
        // End of questions, reset or handle accordingly
        $_SESSION['current_question_index'] = 0;
    }

    // Store the answer status in session
    $_SESSION['answer_status'] = $answer_status;

    // Redirect or display result
    header("Location: ./questions_game.php"); // Redirect to the same page or a results page
    exit;
}

// Determine if there's a previous answer status
$answer_status = $_SESSION['answer_status'] ?? '';
unset($_SESSION['answer_status']); // Clear the session variable after use

// Handle showing the welcome modal
if ($_SESSION['welcome_modal_shown'] === false && ($_SESSION['question_count'] === 1 && $_SESSION['score'] === 0)) {
    $_SESSION['welcome_modal_shown'] = true;
    $show_welcome_modal = true;
} else {
    $show_welcome_modal = false;
}

// Calculate the percentage of correct answers
$total_questions = $_SESSION['question_count'] - 1; // Total number of questions answered
$correct_answers = $_SESSION['score']; // Number of correct answers
$percentage = ($total_questions > 0) ? ($correct_answers / $total_questions) * 100 : 0;

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Questions Game</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            /* background-color: #00002f; */
            /* background-color: #121429; */
            background-color: #031525;
        }

        /* Modal */
        #welcomeModal .modal-content {
            background-color: transparent; /* Make the modal content background transparent */
            border: none; /* Remove border */
            box-shadow: none; /* Remove shadow */
        }

        #welcomeModal .modal-backdrop.show {
            opacity: 0.6; /* Semi-transparent background for the entire screen */
            background-color: rgba(0, 0, 0, 0.6); /* Darker backdrop color */
        }

        #welcomeModal .modal-body {
            padding: 10px; /* Remove default padding */
            position: relative; /* Allow positioning of the overlay */
            background-color: transparent; /* Make sure the main background is transparent */
        }

        #welcomeModal .modal-body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 35px;
            background-color: #132a45;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); /* Slight shadow for depth */
            z-index: 1; /* Place it behind the image */
        }
        
        #welcomeModal .close {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 3;
            border: none; /* Remove any default border */
            border-radius: 50%; /* Optional: rounded corners */
            background-color: transparent; /* Ensure the background is transparent */
            outline: none; /* Remove the default outline */
            transition: color 0.3s, border 0.3s; /* Smooth transitions */
        }

        #welcomeModal .close span {
            font-size: 2rem;
            color: white;
        }
        .Welcome-To {
            max-width: 40%;
            margin: 0px;
            height: auto;
            position: relative;
            z-index: 2;
        }

        .kikit-face {
            margin-bottom: -13px;
            max-width: 70%; /* Limit image size */
            position: relative;
            z-index: 2; /* Place the image above the background overlay */
            animation: zoomAndWave 2s ease-in-out infinite; /* Infinite animation */
        }

        .Go-Study-ArcDown {
            max-width: 50%; /* Limit image size */
            height: auto;
            position: relative;
            z-index: 2; /* Place the image above the background overlay */
            /* animation: zoomAndWave 1.5s ease-in-out infinite; */
        }
        
        #welcomeModal .modal-body p {
            position: relative;
            color: #b5e3ff;
            font-size: 22px;
            font-family: sans-serif;
            line-height: 1.2em;
            z-index: 2; /* Place the image above the background overlay */
        }

        /* Animation */
        @keyframes zoomAndWave {
            0% {
                transform: rotate(0deg) scale(1);
            }
            25% {
                transform: rotate(-5deg) scale(1.05);
            }
            50% {
                transform: rotate(10deg) scale(1.05);
            }
            75% {
                transform: rotate(-5deg) scale(1.05);
            }
            100% {
                transform: rotate(0deg) scale(1);
            }
        }

        /* Navbar */
        
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
        .Go-Kikit-Face {
            position: absolute;
            height: 60px;
            margin-top: 10px;
            margin-left: -10px;
            z-index: 2;
            animation: zoomAndWave 1.2s ease-in-out;
            animation-play-state: paused; /* Start with the animation paused */
        }
        #navbar {
            min-height: 60px;
            display: flex;
            align-items: center;
            /* background-color: #0d0e1f; */
            background: linear-gradient(to bottom, #0d0e1f 5%, rgba(13, 14, 31, 0) 100%);
            /*padding: 0 20px;  Optional: Adjust padding if needed */
        }

        .footer {
            min-height: 70px !important;
            display: flex;
            align-items: center;
            background: linear-gradient(to top, #0d0e1f 10%, rgba(13, 14, 31, 0) 100%);
            padding: 0 20px;
        }

        /* .navbar-icons1 {
            display: flex;
            font-size: 20px;
            gap: 5px; /* Space between icons *
            color: #9aa9c0;
        } */
        
        /* .navbar-icons2 {
            display: flex;
            font-size: 20px;
            gap: 10px; /* Space between icons *
            color: #9aa9c0;
        } */

        /* Animation and gold color styles for the plus icon when no questions are present */
        @keyframes zoomInOut {
            0% {
            transform: scale(1);
            }
            50% {
            transform: scale(1.2);
            }
            100% {
            transform: scale(1);
            }
        }

        .zoom-in-out {
            animation: zoomInOut 1.5s infinite;
        }

        /* #iconGoKikit {
            animation: zoomAndWave 2s ease-in-out infinite;
            z-index: 1;
        } */

        .gold-icon #plus-icon {
            color: gold; /* Set the icon color to gold */
        }
        
        #profile-icon {
            height: 28px;
            margin-top: 2px;
            margin-left: 10px;
            border: 2px solid #b5e3ff;
            border-radius: 50%;
            cursor: pointer;
        }

        #profile-icon:hover {
            border: 2px solid #b5e3ff;
        }

        .dropdown-menu {
            margin-top: -13px;
            margin-right: 3px;
            background-color: #183a57;
            /* background-color: #132a45; WELCOME MODAL COLOR*/
            border: 2px solid #1e2a44;
            border-radius: 5px; /* Rounded corners */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Light shadow */
        }

        #profile:hover, #settings:hover, #done_studying:hover {
            background-color: #142d47;
        }


        #studyOptions {
            width: 100%;
            height: 30px; /* Set the height of the select box */
            color: #92a2b9;
            line-height: 30px; /* Vertically center the text */
            padding: 0; /* Remove default padding */
            font-size: 14px; /* Optional: Adjust font size for better alignment */
            background-color: #0d2136;
            border: 2px solid #1e2a44;
            margin: auto;
        }

        .plus-icon, .history-icon, .notes-icon, .keywords-icon, .list-icon, .settings-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;   
            border: none;
            background: none;
            padding: 0;
            margin: 0;
            cursor: pointer;
        }

        #plus-icon, #history-icon, #notes-icon, #keywords-icon, #list-icon, #settings-icon {
            color: #92a2b9;
            font-size: 20px;
            line-height: 1;
        }

        #plus-icon:hover, #history-icon:hover, #notes-icon:hover, #keywords-icon:hover, #list-icon:hover, #settings-icon:hover {
            color: #b5e3ff;
        }


        .active-icon {
            text-align: center;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            color: #b5e3ff;
            background-color: rgba(146, 162, 185, 0.1);
            border-radius: 10%;
        }
        
        .logout-icon {
            /* color: #92a2b9; */
            color: #e74c3c;
        }

        .logout-icon:hover {
            color: #b5e3ff;
        }

        .centered-message {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 60vh; /* Full viewport height */
            text-align: center;
            background-color: transparent; /* Optional: background color for the whole screen */
        }

        .centered-message .alert {
            margin: 0; /* Remove default margins for better centering */
        }

        #label-no-questions-yet {
            color: #7cacf8;
        }

        #label-click-plus-icon {
            color: #92a2b9;
            font-size: 14px;
            margin: auto;
        }

        #study-name {
            text-align: center;
            color: gold;
            text-decoration: underline;
            text-decoration-color: gold;
        }

        #head-question {
            color: #7cacf8;
            margin-top: -5px;
        }

        .score-display {
            text-align: center;
            margin-top: 0px;
            margin-bottom: 0px;
            font-size: 18px;
            font-weight: bold;
            color: #b5e3ff;
        }

        /* Progress Bar */
        .progress-container {
            display: flex;
            align-items: center;
            margin-top: 2px;
            margin-bottom: 20px;
            padding-left: 77px;
            padding-right: 77px;
        }

        .progress-bar {
            width: 100%;
            height: 15px;
            position: relative;
            background-color: #f1f1f1; /* Background color for the unused portion */
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-correct, .progress-bar-incorrect {
            height: 100%;
            transition: width 1.5s ease-in-out; /* Smooth transition for width changes */
            position: absolute;
            top: 0;
        }

        .progress-bar-correct {
            background-color: #007bff;
            left: 0;
        }

        .progress-bar-incorrect {
            background-color: #dc3545; /* Soft Red */
            right: 0;
        }

        .percentage-label {
            position: absolute;
            width: 100%;
            text-align: center;
            line-height: 15px; /* Match the height of the progress bar */
            font-size: 0.7em;
            font-weight: bold;
            color: white; /* Text color */
            z-index: 1; /* Ensure it's on top */
        }

        /* Question Container */
        .question-container {
            margin-top: 20px;
            max-width: 600px;
            min-height: 546px;
            position: relative;
            margin-bottom: 0px;
        }

        .question-box {
            position: relative;
            padding: 20px;
            border: none;
            /* border: 1px solid #1e2a44; */
            border-radius: 8px;
            background-color: #0d2136;
            /* box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5); */
        }

        .question-id {
            color: #81accf;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .category {
            color: #81accf;
            font-size: 16px;
            margin-bottom: 15px;
        }

        #question {
            color: #b5e3ff;
        }

        #hr {
            border: none; /* Remove default border */
            /* border-top: 1px solid darkslateblue; */
            border-top: 1px solid #173151;
            margin-top: 15px; /* Adjust margin as needed */
            margin-bottom: 20px; /* Adjust margin as needed */
        }

        /* .show-choices {
            color: #81accf;
        } */

        #iconHint {
            position: absolute;
            margin-top: -10px;
            right: 0;
            margin-right: 12px;
            cursor: pointer;
        }

        .answer-and-send {
            text-align: center;
            margin-top: 10px;
            width: 100%;
        }

        .answer-btn {
            color: gold; /* Gold text */
            margin: 5px 0;
        }

        .answer-btn:hover {
            /* background-color: #007bff;  Bootstrap primary color */
            background-color: #182d63;
            color: gold; /* Gold text */
        }

        /* Answer Button Focus */
        .answer-btn:focus {
            /* background-color: #007bff;  Bootstrap primary color */
            /*background-color: #27417c; light*/
            background-color: #3451a0; /*lighter*/
            color: gold; /* Gold text */
        }

        /* Answer Input Field */
        .answer {
            margin-top: 15px;
            text-align: center;
        }

        .answer input {
            font-size: 24px;
            padding: 10px;
            width: 100%; /* Make the input field take the full width of its container */
            max-width: 600px; /* Increase the max-width value */
            color: #b5e3ff;
            border: 2px solid #1e2a44;
            border-radius: 5px; /* Rounded corners */
            background-color: #031525;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Light shadow */
            transition: border-color 0.3s, box-shadow 0.3s; /* Smooth transitions */
        }

        .answer input:focus {
            border-color: #7cacf8;
            box-shadow: 0 2px 4px rgba(181, 227, 255, 0.3); /* Shadow with #b5e3ff tint */
            outline: none; /* Remove default outline */
            color: #b5e3ff;
        }

        .answer input::placeholder {
            color: #81accf;
        }

        /* #send-button {
            /* background-color: #FF007F; 
            color: white;
        }

        #send-button:hover {
            background-color: #c8e5ec;  Darker Info on hover
        } */

        /* Modal Content */
        .modal-content {
            text-align: center;
        }

        .modal-gif {
            width: 100px;
            height: 100px;
        }

        .hidden-choices {
            display: none;
        }

        #answerModal .modal-dialog {
            padding: 25px;
        }

        #answerModal .modal-content {
            border-radius: 25px;
        }

        /* Correct Answer Label */
        .correct-answer-label {
            color: #17a2b8; /* Info color */
            font-weight: bold;
        }

        /* Correct Answer Text */
        .correct-answer-text {
            color: #000; /* Black color */
            font-size: 18px;;
            font-weight: bold;
        }

        #answerGif {
            height: 120px;
            position: relative;
            z-index: 2;
            animation: zoomAndWave 2s ease-in-out infinite;
        }

        #proceed-button {
            background-color: #FF007F;
            color: white;
            width: auto;
        }

        #proceed-button:hover {
            background-color: #E6007E; /* Darker Rose Pink on hover */
        }

        /* Footer Pink */
        .footer {
            min-height: 30px;
            display: flex;
            justify-content: center; /* Centers content horizontally */
            align-items: center;    /* Centers content vertically */
            /* background-color: pink; */
            padding: 0 20px; /* Optional: Adjust padding if needed */
        }

        /* Responsive Design */
        @media (min-width: 768px) {
            .question-container {
                margin-top: 50px;
                max-width: 600px;
                min-height: 565px;
                position: relative;
            }
        }

        /* Hide audio element */
        audio {
            display: none;
        }

        .hidden-choices {
            display: none;
        }
    </style>
</head>
<body>
    <!-- <iframe src="./audio_player.php" style="display:none;" aria-hidden="true"></iframe> -->
    <nav class="navbar navbar-expand-lg" id="navbar">
        <img src="../go-kikit/icons/LOGO-GO-KIKIT-V2.png" alt="Go-Kikit-Face" class="Go-Kikit-Face" id="Go-Kikit-Face">
        <div class="form-group" style="position: absolute; margin-left: 60px; margin-top: 15px;">
            <select id="studyOptions" class="form-control" onchange="window.location.href=this.value;">
                <option value="questions_game.php">Game</option>
                <option value="questions_form.php">Form</option>
            </select>
        </div>
        <div class="ml-auto navbar-icons">
            <button type="button" class="btn plus-icon ml-2 <?php echo !isset($current_question_data) ? 'zoom-in-out gold-icon' : ''; ?>" onclick="window.location.href='crud/question_create.php'" title="Add New Question">
                <i class="fas fa-plus" id="plus-icon"></i>
            </button>
            <button type="button" class="btn history-icon ml-2" onclick="window.location.href='questions_history.php'" title="Questions History">
                <i class="fas fa-history" id="history-icon"></i>
            </button>
            <button type="button" class="btn notes-icon ml-2 text-dark" onclick="window.location.href='questions_notes.php'" title="View Notes">
                <i class="fas fa-sticky-note" id="notes-icon"></i>
            </button>
            <button type="button" class="btn keywords-icon ml-2 text-dark" onclick="window.location.href='questions_keywords.php'" title="View Keywords">
                <i class="fas fa-key" id="keywords-icon"></i>
            </button>
            <button type="button" class="btn list-icon ml-2" onclick="window.location.href='questions_list.php'" title="Questions List">
                <i class="fas fa-list" id="list-icon"></i>
            </button>
            <img src="images/<?php echo htmlspecialchars($profilePic); ?>" class="img-fluid zoomable-image rounded-square dropdown" id="profile-icon" title="Your Profile" alt="Profile Picture" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profile-icon">
                <a class="dropdown-item text-light" id="profile" href="./profile.php"><i class="fas fa-user mr-2"></i>Profile</a>
                <a class="dropdown-item text-light" id="settings" href="./settings.php"><i class="fas fa-cog mr-2"></i>Settings</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" id="done_studying" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Done</a>
            </div>
        </div>
    </nav>


    <!-- <nav class="navbar navbar-expand-lg" id="navbar2">
    </nav> -->

    
    <!-- <hr style="margin-top: 7px; border-top: 1px solid #1a2339;"></hr> -->



<!-- Welcome Modal -->
<?php if ($show_welcome_modal): ?>
    <div class="modal fade p-4" id="welcomeModal" tabindex="-1" role="dialog" aria-labelledby="welcomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 2rem; color: white;">&times;</span>
                </button>
                <div class="modal-body text-center">
                    <img src="../go-kikit/icons/ICON_WELCOME_TO.png" alt="Welcome To" class="Welcome-To mt-5 mb-0">
                    <img src="../go-kikit/icons/ICON_KIKIT.png" alt="Kikit-Face" class="kikit-face">
                    <img src="../go-kikit/icons/ICON-GO-KIKIT-GOLD-ARCDOWN.png" alt="Go-Study-ArcDown" class="Go-Study-ArcDown">
                    <p class="mt-5 ml-3 mr-3 mb-4">Good luck and have fun studying!</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<script>
    $(document).ready(function() {
        // Show the modal
        $('#welcomeModal').modal('show');

        // Hide the modal after 2.5 seconds (2500 milliseconds)
        setTimeout(function() {
            $('#welcomeModal').modal('hide');
        }, 2500);
    });
</script>


<div class="container question-container">
    <h5 class="mb-3"  id="study-name"><?php echo htmlspecialchars($study_name); ?></h5>
    <?php if (isset($current_question_data)): ?>
        <h2 class="text-center" id="head-question">
            <?php echo isset($current_question_data) ? 'Question ' . $_SESSION['question_count'] : 'No questions yet.'; ?>
        </h2>
        <div class="score-display">
            Score: <?php echo $_SESSION['score']; ?> / <?php echo $_SESSION['question_count'] - 1; ?>
        </div>
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-bar-correct" id="progress-bar-correct"></div>
                <div class="progress-bar-incorrect" id="progress-bar-incorrect"></div>
                <span class="percentage-label" id="percentage-label">0%</span>
            </div>
        </div>
        <div class="questions-container mb-5">
            <div class="question-box">
                <!-- <p class="question-id">Q-ID: <?php echo htmlspecialchars($current_question_data['question_id']); ?></p> -->
                <i><p class="category"><?php echo htmlspecialchars($current_question_data['category']); ?></p></i>
                <h4 id="question"><?php echo htmlspecialchars($current_question_data['question']); ?></h4>
                <hr id="hr"></hr>
                <img src="../go-kikit/icons/GIF_HINT.gif" height="35" class="d-inline-block align-top" id="iconHint" title="Show Hint" alt="Hint Icon">
                <div class="row text-center align-items-center justify-content-center" id="hint-section" style="display: none;">
                    <?php if (!empty($current_question_data['hint'])): ?>
                        <div class="alert alert-info mt-4 mb-3" id="hint-text"><em><?php echo htmlspecialchars($current_question_data['hint']); ?></em></div>
                    <?php else: ?>
                        <div class="alert alert-danger mt-4 mb-3" id="hint-text"><em>No hint for this question.</em></div>
                    <?php endif; ?>
                </div>
                <a href="#" class="show-choices" data-question-id="<?php echo htmlspecialchars($current_question_index); ?>">Show Choices</a>
                <div id="choices-<?php echo htmlspecialchars($current_question_index); ?>" class="hidden-choices">
                    <form id="question-form-<?php echo htmlspecialchars($current_question_index); ?>" method="post" action="">
                        <div class="answer-buttons mt-3">
                            <?php 
                            $letters = range('A', 'Z'); 
                            $index = 0;
                            if (!empty($current_question_data['choices'])):
                                foreach ($current_question_data['choices'] as $choice): ?>
                                    <button type="button" class="btn btn-block btn-transparent answer-btn" 
                                        data-answer="<?php echo htmlspecialchars($choice['choice_id']); ?>" 
                                        data-text="<?php echo htmlspecialchars($choice['choice']); ?>">
                                        <h4><?php echo $letters[$index]; ?>. <?php echo htmlspecialchars($choice['choice']); ?></h4>
                                    </button>
                                    <?php $index++; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">No choices for this question.</div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="selected_answer" id="selected_answer_<?php echo htmlspecialchars($current_question_index); ?>" value="">
                        <input type="hidden" name="answer" value="<?php echo htmlspecialchars($current_question_data['answer']); ?>">
                        <input type="hidden" name="question_id" value="<?php echo htmlspecialchars($current_question_index); ?>">
                    </form>
                </div>
                <div class="answer-and-send">
                    <div class="answer mb-3">
                        <input type="text" id="answer_<?php echo htmlspecialchars($current_question_index); ?>" name="answer_display" placeholder="Your answer here...">
                    </div>
                    <button type="button" class="btn btn-success" id="send-button">
                        <i class="fas fa-paper-plane"></i> Send Answer
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="centered-message">
            <div class="alert alert-transparent text-center">
                <img src="../go-kikit/icons/GIF_NOQUESTIONSYET.gif" height="100" class="d-inline-block align-top" id="iconNoQuestionsYet">
                <h3 id="label-no-questions-yet">No questions yet.</h3>
                <label id="label-click-plus-icon"><i>Please click the "plus" icon above to start!</i></label>
            </div>
        </div>
    <?php endif; ?>
</div>


    <!-- Modal for Correct/Wrong Alert -->
    <div class="modal fade" id="answerModal" tabindex="-1" role="dialog" aria-labelledby="answerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button> -->
                    <p id="answerMessage"></p>
                    <img id="answerGif" src="" alt="Answer Feedback" class="img-fluid mb-3" />
                    <!-- Paragraph to display the correct answer when the answer is wrong -->
                    <p id="correctAnswerMessage" class="text-info font-weight-bold"></p>
                    <div>
                        <button type="button" class="btn" id="proceed-button" data-dismiss="modal">Proceed</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    


    <script>
    
    document.addEventListener("DOMContentLoaded", function() {
            var icon = document.getElementById("Go-Kikit-Face");

            function triggerAnimation() {
                icon.style.animation = "none"; // Reset the animation
                icon.offsetHeight; // Trigger a reflow to restart the animation
                icon.style.animation = "zoomAndWave 1.2s ease-in-out"; // Apply the animation

                setTimeout(function() {
                    icon.style.animationPlayState = "paused"; // Pause the animation after it completes
                }, 1200);
            }

            // Trigger the animation every 5 seconds
            setInterval(triggerAnimation, 5000);
            
            // Initial trigger
            triggerAnimation();
        });



        
$(document).ready(function() {
    // Show the welcome modal if applicable
    <?php if ($show_welcome_modal): ?>
        $('#welcomeModal').modal('show');
    <?php endif; ?>


    // Show hint when the hint icon is clicked
    $(".d-inline-block.align-top").on('click', function() {
        $(this).siblings('.row').toggle();
    });

    // Show or hide choices when clicking the "Show Choices" link
    document.querySelectorAll('.show-choices').forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            var questionId = this.getAttribute('data-question-id');
            var choicesDiv = document.getElementById('choices-' + questionId);
            if (choicesDiv.style.display === 'none' || choicesDiv.style.display === '') {
                choicesDiv.style.display = 'block';
                this.textContent = 'Hide Choices';
            } else {
                choicesDiv.style.display = 'none';
                this.textContent = 'Show Choices';
            }
        });
    });

    // Display the selected choice in the answer textfield
    document.querySelectorAll('.answer-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var questionId = this.closest('.question-box').querySelector('form').id.split('-')[2];
            var selectedAnswer = this.getAttribute('data-answer');
            var selectedText = this.getAttribute('data-text');
            document.getElementById('selected_answer_' + questionId).value = selectedAnswer;
            document.getElementById('answer_' + questionId).value = selectedText;
        });
    });

    // Handle form submission when the send button is clicked
$("#send-button").on('click', function(e) {
    e.preventDefault(); // Prevent the form from submitting immediately

    var questionId = $(this).closest('.question-box').find('form').attr('id').split('-')[2];
    var selectedAnswer = $("#selected_answer_" + questionId).val().trim().toLowerCase();
    var typedAnswer = $("#answer_" + questionId).val().trim().toLowerCase();
    var correctAnswerText = $("input[name='answer']").val().trim(); // Keep the original casing

    // Determine the final answer
    var finalAnswer = typedAnswer || selectedAnswer;

    // Check if an answer is selected or typed
    if (!finalAnswer) {
        alert("Please select or type an answer before submitting.");
    } else {
        // Determine if the answer is correct
        if (finalAnswer === correctAnswerText.toLowerCase()) { // Compare case-insensitively
            showAnswerModal("Your answer is correct!", "../go-kikit/icons/GIF_CORRECT.gif", "");
        } else {
            showAnswerModal("Your answer is wrong!", "../go-kikit/icons/GIF_WRONG.gif", 
                '<span class="correct-answer-label">Correct answer: <br></span> <span class="correct-answer-text">' + correctAnswerText + '</span>'
            );
        }

        // Update hidden input for form submission
        $("#selected_answer_" + questionId).val(finalAnswer);
    }
});

    // Function to show the modal with the corresponding GIF, message, and correct answer if wrong
    function showAnswerModal(message, gifSrc, correctAnswerMessage) {
        $('#answerMessage').text(message);
        $('#answerGif').attr('src', gifSrc);
        $('#correctAnswerMessage').html(correctAnswerMessage); // Use .html() to render HTML content
        $('#answerModal').modal('show');
        
        var correctAudio = document.getElementById('correctAnswerAudio');
        var wrongAudio = document.getElementById('wrongAnswerAudio');

        // Play the correct answer audio if the answer is correct
        if (message.includes("correct")) {
            $('#proceed-button').hide();
            correctAudio.play();

            // Automatically close the modal after 3 seconds (3000 ms)
            setTimeout(function() {
                $('#answerModal').modal('hide');
            }, 3000);
        } else {
            // Play the wrong answer audio if the answer is wrong
            wrongAudio.play();
        }
        
    }

    // Listen for when the modal is hidden to submit the form
    $('#answerModal').on('hidden.bs.modal', function () {
        $("form").submit(); // Submit the form after the modal closes
    });

    // Function to update the progress bar and percentage label
    function updateProgressBar(correctAnswers, totalQuestions) {
        var correctPercentage = totalQuestions > 0 ? (correctAnswers / totalQuestions) * 100 : 0;
        var incorrectPercentage = 100 - correctPercentage;

        correctPercentage = correctPercentage.toFixed(2); // Limit to two decimal places

        var correctBar = document.getElementById('progress-bar-correct');
        var incorrectBar = document.getElementById('progress-bar-incorrect');
        var percentageLabel = document.getElementById('percentage-label');

        // Update the widths of the bars and percentage label
        correctBar.style.width = correctPercentage + '%';
        incorrectBar.style.width = incorrectPercentage + '%';
        percentageLabel.textContent = correctPercentage + '%';
    }

    // Initialize progress bar
    var totalQuestions = <?php echo $_SESSION['question_count'] - 1; ?>;
    var correctAnswers = <?php echo $_SESSION['score']; ?>;

    if (totalQuestions === 0 && correctAnswers === 0) {
        // Start with 50% blue and 50% red
        document.getElementById('progress-bar-correct').style.width = '50%';
        document.getElementById('progress-bar-incorrect').style.width = '50%';
        document.getElementById('percentage-label').textContent = '0%';
    } else {
        // Update the progress bar based on actual data
        updateProgressBar(correctAnswers, totalQuestions);
    }
});
</script>


    <audio id="correctAnswerAudio" src="../go-kikit/audio/AUDIO_CORRECT_SHORTEST.mp3"></audio>
    <audio id="wrongAnswerAudio" src="../go-kikit/audio/AUDIO_WRONG_SHORTEN.mp3"></audio>

</body>

<footer class="footer text-light">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-copyright">
                &copy; 2024 Go-Kikit!. All Rights Reserved.
            </div>
        </div>
    </div>
</footer>

</html>
