<?php
session_start();

// Include config file
require_once "config.php";

// Initialize variables
// $limit = 50; // Default number of questions
$questions = [];
$show_welcome_modal = false;

// Initialize session variables if not set
if (!isset($_SESSION['questionnaire_count'])) {
    $_SESSION['questionnaire_count'] = 1;
}
if (!isset($_SESSION['attempt_count'])) {
    $_SESSION['attempt_count'] = 1; // Initialize attempt count
}
if (!isset($_SESSION['form_questions_count'])) {
    $_SESSION['form_questions_count'] = 0;
}
if (!isset($_SESSION['form_score'])) {
    $_SESSION['form_score'] = 0;
}
if (!isset($_SESSION['questions'])) {
    $_SESSION['questions'] = [];
}
if (!isset($_SESSION['welcome_modal_shown'])) {
    $_SESSION['welcome_modal_shown'] = false;
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

// Query to get the total number of questions for this study_id
$sql_total_questions = "
    SELECT COUNT(*) AS total 
    FROM tbl_questions 
    WHERE study_id = :study_id
";

try {
    $stmt_total = $pdo->prepare($sql_total_questions);
    $stmt_total->bindParam(':study_id', $study_id, PDO::PARAM_INT);
    $stmt_total->execute();
    $total_question_data = $stmt_total->fetch(PDO::FETCH_ASSOC);
    $total_questions = $total_question_data['total'] ?? 0;
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

$limit = $total_questions;

// Handle the welcome modal logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['num_items'])) {
        $limit = intval($_POST['num_items']);

        // Fetch questions from the database using PDO and randomize the order
        try {
            $stmt = $pdo->prepare("
                SELECT q.question_id, q.question, q.category, q.hint,
                       GROUP_CONCAT(c.choice_id, ':', c.choice ORDER BY c.choice_id ASC SEPARATOR '||') AS choices,
                       a.answer
                FROM tbl_questions q
                LEFT JOIN tbl_choices c ON q.question_id = c.question_id
                LEFT JOIN tbl_answers a ON q.question_id = a.question_id
                WHERE q.study_id = :study_id
                GROUP BY q.question_id
                ORDER BY RAND()
                LIMIT :limit
            ");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':study_id', $study_id, PDO::PARAM_INT);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize questions with choices and answers
            $organized_questions = [];
            foreach ($questions as $row) {
                $question_id = $row['question_id'];
                if (!isset($organized_questions[$question_id])) {
                    $organized_questions[$question_id] = [
                        'question_id' => $question_id,
                        'question' => htmlspecialchars_decode($row['question']),
                        'category' => htmlspecialchars_decode($row['category']),
                        'hint' => htmlspecialchars_decode($row['hint']),
                        'choices' => [],
                        'answer' => htmlspecialchars_decode($row['answer']),
                    ];
                }

                // Split choices string into array
                if ($row['choices']) {
                    $choices = explode('||', $row['choices']);
                    foreach ($choices as $choice) {
                        // list($choice_id, $choice_text) = explode(':', $choice);
                        list($choice_id, $choice_text) = explode(':', $choice, 2); // DISPLAYS 1:30 am INSTEAD OF 1 ONLY
                        $organized_questions[$question_id]['choices'][] = [
                            'choice_id' => $choice_id,
                            'choice' => htmlspecialchars_decode($choice_text),
                        ];
                    }
                }
            }
            
            $_SESSION['questions'] = array_values($organized_questions); // Re-index array
            $_SESSION['form_questions_count'] = count($_SESSION['questions']); // Update the count based on fetched questions
        } catch (PDOException $e) {
            die("Error fetching questions: " . $e->getMessage());
        }
    }

    if (isset($_POST['next_test']) && $_POST['next_test'] === '1') {
        $_SESSION['questionnaire_count']++;
        $_SESSION['attempt_count'] = 1; // Reset attempt count for the new questionnaire

        // Fetch new questions for the new questionnaire_count
        try {
            $stmt = $pdo->prepare("
                SELECT q.question_id, q.question, q.category, q.hint,
                       GROUP_CONCAT(c.choice_id, ':', c.choice ORDER BY c.choice_id ASC SEPARATOR '||') AS choices,
                       a.answer
                FROM tbl_questions q
                LEFT JOIN tbl_choices c ON q.question_id = c.question_id
                LEFT JOIN tbl_answers a ON q.question_id = a.question_id
                WHERE q.study_id = :study_id
                GROUP BY q.question_id
                ORDER BY RAND()
                LIMIT :limit
            ");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':study_id', $study_id, PDO::PARAM_INT);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organize questions with choices and answers
            $organized_questions = [];
            foreach ($questions as $row) {
                $question_id = $row['question_id'];
                if (!isset($organized_questions[$question_id])) {
                    $organized_questions[$question_id] = [
                        'question_id' => $question_id,
                        'question' => htmlspecialchars_decode($row['question']),
                        'category' => htmlspecialchars_decode($row['category']),
                        'hint' => htmlspecialchars_decode($row['hint']),
                        'choices' => [],
                        'answer' => htmlspecialchars_decode($row['answer']),
                    ];
                }

                // Split choices string into array
                if ($row['choices']) {
                    $choices = explode('||', $row['choices']);
                    foreach ($choices as $choice) {
                        // list($choice_id, $choice_text) = explode(':', $choice);
                        list($choice_id, $choice_text) = explode(':', $choice, 2); // DISPLAYS 1:30 am INSTEAD OF 1 ONLY
                        $organized_questions[$question_id]['choices'][] = [
                            'choice_id' => $choice_id,
                            'choice' => htmlspecialchars_decode($choice_text),
                        ];
                    }
                }
            }

            $_SESSION['questions'] = array_values($organized_questions); // Re-index array
            $_SESSION['form_questions_count'] = count($_SESSION['questions']); // Update the count based on fetched questions
        } catch (PDOException $e) {
            die("Error fetching questions: " . $e->getMessage());
        }

        // Set the flag to show the welcome modal
        $show_welcome_modal = true;
        $_SESSION['welcome_modal_shown'] = true;
    }

    // Increment attempt count on quiz submission
    if (isset($_POST['attempt_action']) && $_POST['attempt_action'] === 'increment_attempt_count') {
        $_SESSION['attempt_count']++;
    }
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
$current_question_data = $_SESSION['questions'][$current_question_index] ?? null;

// Handle showing the welcome modal
if ($show_welcome_modal || ($_SESSION['welcome_modal_shown'] === false || ($_SESSION['form_questions_count'] === 0 && $_SESSION['form_score'] === 0))) {
    $_SESSION['welcome_modal_shown'] = true;
    $show_welcome_modal = true;
} else {
    $show_welcome_modal = false;
}

// Calculate the percentage of correct answers
$form_total_questions = $_SESSION['form_questions_count']; // Use the session variable directly
$form_correct_answers = $_SESSION['form_score']; // Use the session variable directly
$form_percentage = ($form_total_questions > 0) ? ($form_correct_answers / $form_total_questions) * 100 : 0;

?>




<!--HERE-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Questions Form</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            /* font-family: Arial, sans-serif; */
            background-color: #031525;
            padding-bottom: 70px;
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
            padding: 30px;
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

        .icon-kikit-face {
            margin-bottom: -13px;
            max-width: 70%; /* Limit image size */
            position: relative;
            z-index: 2; /* Place the image above the background overlay */  
            animation: zoomAndWave 1.2s ease-in-out;
            /*animation-play-state: paused;  Start with the animation paused */
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

        /* Form */
        #numItemsForm {
            position: relative;
            z-index: 2; /* Ensure form is above background overlay */
        }

        #numItemsForm label,
        #numItemsForm input,
        #numItemsForm button {
            display: block; /* Ensure elements are block level for proper display */
            margin: 10px auto; /* Center-align elements */
            border-radius: 5px;
        }
        #numItemsForm input {
            text-align: center;
            border: none;
            border-radius: 5px;
            background: #FDEDF1;
        }
        #start-exam-button {
            width: 120px;
        }
        /* #start-exam-button:focus, #start-exam-button:active {
            box-shadow: 0 0 0 0.2rem rgba(255, 111, 145, 0.5); /* Outline on focus *
        } */

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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 70px !important;
            display: flex;
            align-items: center;
            margin-top: 40px;
            margin-bottom: -20px;
            background-color: #031525;
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

        #study-name {
            text-align: center;
            color: gold;
            text-decoration: underline;
            text-decoration-color: gold;
        }

        #head-questionnaire {
            color: #7cacf8;
            margin-top: -5px;
        }

        #form_scoreDisplay {
            text-align: center;
            margin-top: 0px;
            margin-bottom: 0px;
            font-size: 18px;
            font-weight: bold;
            color: #b5e3ff;
        }

        .takes {
            margin-top: -6px;
        }
        .takes .take-count {
            font-size: 1.2em; /* Adjust size as needed */
            color: #81accf;
        }

        #scoreEmoji {
            font-size: 1.5em; /* Adjust size as needed */
            margin-left: 10px;
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

        .question-container {
            margin-top: 20px !important;
            max-width: 600px;
            min-height: 546px;
            position: relative;
            margin-bottom: 0px;
        }


        .card {
            background-color: #0d2136;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 15px;
            max-width: 600px;
            padding: 20px;
            position: relative;
            padding-bottom: 40px; /* Space for the correct answer */
        }
        .card.correct .status-icon {
            color: green;
        }
        .card.wrong .status-icon {
            color: red;
        }
        .status-icon {
            font-size: 24px;
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .question-id {
            color: #81accf;
            font-size: 14px;
            margin-bottom: 15px;
        }           
        .item-number {
            color: #81accf;
            font-size: 21px;
            margin-bottom: 15px;
        }
        .category {
            color: #81accf;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .question {
            color: #b5e3ff;
            font-weight: bold;
            margin-bottom: 0px;
        }
        #hr {
            border: none; /* Remove default border */
            /* border-top: 1px solid darkslateblue; */
            border-top: 1px solid #173151;
            margin-top: 15px; /* Adjust margin as needed */
            margin-bottom: 10px; /* Adjust margin as needed */
        }
        .choices {
            margin-bottom: 20px;
        }

        .choice-label {
            display: block;
            color: gold;
            font-size: 18px;
            margin: 40px 0;
            padding: 10px 5px;
            cursor: pointer;
            user-select: none; /* Prevent text selection on click */
        }

        .choice-label input[type="radio"] {
            margin-top: 7px;
            margin-right: 10px; /* Add space between radio button and text */
        }

        
        .bottom-buttons {
            /* position: fixed; */
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-bottom: 0px !important;
            /* background: linear-gradient(to top, #0d0e1f 20%, rgba(255, 255, 255, 0) 100%); */
            padding-top: 20px;
            z-index: 1000;
        }

        .bottom-buttons .button-group {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        #submit-button {
            border-radius: 4px;
            cursor: pointer;
            padding: 0px;
            font-size: 16px;
            display: block;
            width: 120px;
            height: 40px;
        }

        #retake-button, #next-test-button {
            border-radius: 4px;
            cursor: pointer;
            padding: 0px;
            font-size: 16px;
            margin-left: 5px 5px;
            width: 120px;
            height: 40px;
        }

        /* #submit-button:hover, #retake-button:hover, #next-test-button:hover {
            background-color: #ff5073;
            border-color: #ff5073;
        } */

        /* #submit-button:focus, #submit-button:active, #retake-button:focus, #retake-button:active, #next-test-button:focus, #next-test-button:active {
            background-color: #e64a70;
            border-color: #e64a70;
            box-shadow: 0 0 0 0.2rem rgba(255, 111, 145, 0.5);
        } */

        
        .correct-answer {
            font-size: 16px;
            color: #333;
            position: absolute;
            bottom: 10px;
            left: 20px;
            display: none; /* Hidden by default */
        }
        .card.correct .status-icon::before {
            content: "\f058"; /* FontAwesome check icon */
            color: #007bff; /* Blue color */
            font-family: 'FontAwesome';
        }
        .card.wrong .status-icon::before {
            content: "\f057"; /* FontAwesome times icon */
            color: red;
            font-family: 'FontAwesome';
        }

        /* Hide audio element */
        audio {
            display: none;
        }
        
        @media (min-width: 768px) {
            .question-container {
                margin-top: 50px !important;
                max-width: 600px;
                min-height: 565px;
                position: relative;
            }
            .card {
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <!-- <iframe src="./audio_player.php" style="display:none;" aria-hidden="true"></iframe> -->
    <nav class="navbar navbar-expand-lg" id="navbar">
        <!-- <img src="../go-kikit/icons/LOGO-GO-KIKIT-V2.png" alt="Go-Kikit-Logo" class="Go-Kikit-Logo" id="Go-Kikit-Logo"> -->
        <img src="../go-kikit/icons/LOGO-GO-KIKIT-V2.png" alt="Go-Kikit-Face" class="Go-Kikit-Face" id="Go-Kikit-Face">
        <div class="form-group" style="position: absolute; margin-left: 60px; margin-top: 15px;">
            <select id="studyOptions" class="form-control" onchange="window.location.href=this.value;">
                <option value="questions_form.php">Form</option>
                <option value="questions_game.php">Game</option>
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
            <div class="dropdown-menu dropdown-menu-right mr-1" aria-labelledby="profile-icon">
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




<!-- <php if (empty($questions)): ?> -->
    <!-- Modal for selecting number of items -->
    <!-- <div class="modal" id="numItemsModal">
        <div class="modal-content text-center">
            <h2>Number of Items</h2>
            <form id="numItemsForm" method="POST">
                <label for="numItems">How many items do you want to answer?</label>
                <input type="number" id="numItems" name="num_items" min="1" max="100" value="50" required>
                <br><br>
                <button type="submit" class="submit-btn" id="start-exam-button">Start Exam</button>
            </form>
        </div>
    </div> -->
<!-- <php else: ?> -->


    
<!-- Welcome Modal -->
<?php if ($show_welcome_modal): ?>
    <div class="modal fade p-4" id="welcomeModal" tabindex="-1" role="dialog" aria-labelledby="welcomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="modal-body text-center">
                    <!-- <img src="../go-kikit/icons/ICON_WELCOME_TO.png" alt="Welcome To" class="Welcome-To mt-5 mb-0"> -->
                    <img src="../go-kikit/icons/ICON_KIKIT.png" alt="icon-kikit-face" class="icon-kikit-face mt-5 mb-0"> <!--added "mt-5 mb-0" because the welcome message is commented-->
                    <!-- <img src="../go-kikit/icons/ICON_KIKIT.png" alt="Kikit" class="kikit-face"> -->
                    <!-- <img src="../go-kikit/icons/ICON-GOSTUDY-ARCDOWN.png" alt="Go-Kikit-ArcDown" class="Go-Kikit-ArcDown"> -->
                    <p class="mt-4">How many items do you want to answer?</p>
                    <form id="numItemsForm" method="POST">
                        <!-- <input type="number" id="numItems" name="num_items" min="1" max="500" value="50" required> -->
                        <input type="number" id="numItems" name="num_items" min="1" max="<?= $total_questions ?>" value="<?= $total_questions ?>" required>
                        <button type="submit" class="btn text-light bg-primary" id="start-exam-button">Start!</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>



<div class="container question-container">
    <h5 class="mb-3" id="study-name"><?php echo htmlspecialchars($study_name); ?></h5>
    <?php if (!empty($_SESSION['questions'])): ?>
        <div class="text-center">
            <h2 class="text-center" id="head-questionnaire">Questionnaire <?php echo $_SESSION['questionnaire_count']; ?></h2>
            <div class="takes">
                <span class="take-count">Take <?php echo $_SESSION['attempt_count']; ?></span>
            </div>
        </div>

        <!-- Display questions and form_score -->
        <div id="form_scoreDisplay">
            Score: <?php echo $form_correct_answers; ?> / <?php echo $form_total_questions; ?>
        </div>
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-bar-correct" id="progress-bar-correct"></div>
                <div class="progress-bar-incorrect" id="progress-bar-incorrect"></div>
                <span class="percentage-label" id="percentage-label"><?php echo $form_percentage; ?>%</span>
            </div>
        </div>
        <form id="examForm" method="POST">
            <div id="questions-container">
                <?php $count = 1; ?>
                <?php foreach ($_SESSION['questions'] as $row): ?>
                    <div class="card" data-answer="<?php echo htmlspecialchars($row['answer']); ?>" data-question-id="<?php echo $row['question_id']; ?>">
                        <div class="status-icon"></div>
                        <!-- <div class="question-id">Q-ID: <?php echo $row['question_id']; ?></div> -->
                        <div class="mb-2">
                            <p class="item-number d-inline"><strong><?php echo $count . '. '; ?></strong></p>
                            <p class="category d-inline"><i><?php echo htmlspecialchars($row['category']); ?></i></p>
                        </div>

                        <div>
                            <?php
                            // Fetch the image for the current question using the question_id
                            $sql_image = "SELECT image FROM tbl_questions WHERE question_id = :question_id";
                            try {
                                $stmt_image = $pdo->prepare($sql_image);
                                $stmt_image->bindParam(':question_id', $row['question_id'], PDO::PARAM_INT);
                                $stmt_image->execute();
                                $image_data = $stmt_image->fetch(PDO::FETCH_ASSOC);
                                $image_filename = $image_data['image'] ?? null; // Use 'null' if no image exists
                            } catch (PDOException $e) {
                                die("Error fetching image: " . $e->getMessage());
                            } 
                            ?>
                            <!-- Display the image if it exists -->
                            <?php if ($image_filename): ?>
                                <img src="images/<?php echo htmlspecialchars($image_filename); ?>" alt="Question Image" class="img-fluid mt-2 mb-4" style="max-width: 100%; height: auto;">
                            <?php endif; ?>
                        </div>

                        <div class="question">
                            <h5 class="d-inline"><?php echo htmlspecialchars($row['question']); ?></h5>
                        </div>
                        <hr id="hr"></hr>
                        <div class="choices mt-0 mb-0">
                            <?php foreach ($row['choices'] as $choice): ?>
                                <label class="choice-label d-flex align-items-start mt-0 mb-0">
                                    <input type="radio" name="question_<?php echo $row['question_id']; ?>" value="<?php echo htmlspecialchars($choice['choice']); ?>" required> 
                                    <?php echo htmlspecialchars($choice['choice']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php $count++; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="bottom-buttons mb-0">
                <button type="button" class="btn text-light mb-1 bg-primary" id="submit-button" onclick="submitExam()">Submit</button>
                <div class="button-group">
                    <button type="button" class="btn text-light mb-1 bg-primary" id="retake-button" style="display:none;" onclick="retakeTest()">Retake</button>
                    <button type="button" class="btn text-light mb-1 bg-primary" id="next-test-button" style="display:none;" onclick="NextTest()">Next Test</button>
                </div>
            </div>

            <input type="hidden" name="attempt_action" id="attempt_action" value="">
            <input type="hidden" name="next_test" id="next_test" value="0">
        </form>

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



<script>
    document.addEventListener('DOMContentLoaded', function() {

            // Show the welcome modal if applicable
            <?php if ($show_welcome_modal): ?>
                $('#welcomeModal').modal('show');
            <?php endif; ?>


    const numItemsForm = document.getElementById('numItemsForm');
        if (numItemsForm) {
            numItemsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Directly submit the form to reload the page with new number of items
                numItemsForm.submit();
            });
        }
    });


    
    document.addEventListener("DOMContentLoaded", function() {
            var icon = document.getElementById("Go-Kikit-Face");

            function triggerAnimation() {
                icon.style.animation = "none"; // Reset the animation
                icon.offsetHeight; // Trigger a reflow to restart the animation
                icon.style.animation = "zoomAndWave 1.2s ease-in-out"; // Apply the animation

                setTimeout(function() {
                    icon.style.animationPlayState = "paused"; // Pause the animation after it completes
                }, 2000);
            }

            // Trigger the animation every 5 seconds
            setInterval(triggerAnimation, 5000);
            
            // Initial trigger
            triggerAnimation();
        });








//HERE


// Function to apply the bounce effect when scrolling to an element
function applyBounceEffect(element, isBounceDown = true) {
    // Create a unique ID for the element to target with CSS
    const elementId = 'bounce-effect-' + Date.now();
    element.id = elementId;

    // Define a CSS animation for the bounce effect with a slow initial movement
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes bounce {
            0% { transform: translateY(0); }
            20% { transform: translateY(${isBounceDown ? '5px' : '-5px'}); } /* Slow initial movement */
            50% { transform: translateY(${isBounceDown ? '-30px' : '30px'}); } /* Main bounce movement */
            80% { transform: translateY(${isBounceDown ? '5px' : '-5px'}); } /* Return to slightly opposite movement */
            100% { transform: translateY(0); } /* End at original position */
        }
        #${elementId} {
            animation: bounce 1.5s ease-in-out; /* Adjusted duration for slower effect */
        }
    `;
    document.head.appendChild(style);

    // Smoothly scroll to the element
    // element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    const targetY = element.getBoundingClientRect().top + window.scrollY - window.innerHeight / 2 + element.offsetHeight / 2;
    smoothScrollTo(targetY, 1000); // 2000 ms = 2 seconds


    // Remove the animation after it completes
    setTimeout(() => {
        element.removeAttribute('id');
        document.head.removeChild(style);
    }, 2000); // Duration of the animation
}


// Function to find the next unanswered card below the current card
function getNextUnansweredCard(cards, currentCard) {
    const currentIndex = Array.from(cards).indexOf(currentCard);
    for (let i = currentIndex + 1; i < cards.length; i++) {
        if (!cards[i].querySelector('input:checked')) {
            return cards[i];
        }
    }
    return null;
}

// Function to find the highest unanswered card above the current card
function getHighestUnansweredCardAbove(cards, currentCard) {
    const currentIndex = Array.from(cards).indexOf(currentCard);
    for (let i = currentIndex - 1; i >= 0; i--) {
        if (!cards[i].querySelector('input:checked')) {
            return cards[i];
        }
    }
    return null;
}

// Add event listeners to apply bounce effect on page load
window.addEventListener('load', () => {
    const cards = document.querySelectorAll('.card');
    let answeredCards = new Set();

    cards.forEach(card => {
        const questionId = card.getAttribute('data-question-id');
        const radioButtons = card.querySelectorAll(`input[name="question_${questionId}"]`);

        radioButtons.forEach(radio => {
            radio.addEventListener('change', () => {
                // Apply bounce effect to the next unanswered card below
                const nextCard = getNextUnansweredCard(cards, card);
                if (nextCard) {
                    applyBounceEffect(nextCard, true);
                }

                // Add the current card to the set of answered cards
                answeredCards.add(card);

                // Check if all cards below are answered
                const allBelowAnswered = Array.from(cards).slice(Array.from(cards).indexOf(card) + 1).every(c => answeredCards.has(c) || c.querySelector('input:checked'));
                if (allBelowAnswered) {
                    // Scroll up to the highest unanswered card above, if any
                    const highestUnansweredCardAbove = getHighestUnansweredCardAbove(cards, card);
                    if (highestUnansweredCardAbove) {
                        applyBounceEffect(highestUnansweredCardAbove, false);
                    }
                }
            });
        });
    });
});


function smoothScrollTo(targetY, duration = 2000) {
    const startY = window.scrollY;
    const distance = targetY - startY;
    const startTime = performance.now();

    function scroll(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        window.scrollTo(0, startY + distance * easeInOutQuad(progress));

        if (elapsed < duration) {
            requestAnimationFrame(scroll);
        }
    }

    requestAnimationFrame(scroll);
}

function easeInOutQuad(t) {
    return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
}




// Function to add blinking effect using a CSS class
function applyBlinkEffect(element) {
    const existingStyle = document.getElementById('dynamic-styles');
    
    if (existingStyle) {
        existingStyle.remove(); // Remove previous styles to prevent duplicates
    }

    // Create a style element for keyframes if it doesn't already exist
    const style = document.createElement('style');
    style.id = 'dynamic-styles'; // Give it an ID for easy removal
    style.innerHTML = `
        @keyframes blink {
            0% { background-color: #003366; } /* Dark blue to start */
            75% { background-color: #3451a0; } /* Vibrant blue for the end */
            100% { background-color: #003366; } /* Dark blue to end */
        }
        .blink-effect {
            animation: blink 1s ease-in-out infinite;
            border-radius: 10px;
        }
    `;
    document.head.appendChild(style);

    // Apply the animation class
    element.classList.add('blink-effect');
}

function submitExam() {
    const cards = document.querySelectorAll('.card');
    let form_score = 0;
    let totalQuestions = cards.length;
    let allAnswered = true; // Assume all questions are answered initially
    let unansweredCards = []; // To store all unanswered cards

    // First pass: Check if all questions are answered
    cards.forEach(card => {
        const questionId = card.getAttribute('data-question-id');
        const selectedAnswer = document.querySelector(`input[name="question_${questionId}"]:checked`);

        if (!selectedAnswer) {
            allAnswered = false;
            card.classList.add('missing-answer'); // Add a class for unanswered questions
            unansweredCards.push(card); // Add to the list of unanswered cards
        } else {
            card.classList.remove('missing-answer'); // Remove the class if the question is answered
        }
    });

    if (!allAnswered) {
        alert('Please answer all questions before submitting.');
        
        // Scroll to the first unanswered card with bounce effect
        if (unansweredCards.length > 0) {
            applyBounceEffect(unansweredCards[0]);
        }

        // Add event listeners to radio buttons to scroll to the next unanswered card after any unanswered card is answered
        unansweredCards.forEach((card, index) => {
            const questionId = card.getAttribute('data-question-id');
            const radioButtons = card.querySelectorAll(`input[name="question_${questionId}"]`);

            radioButtons.forEach(radio => {
                radio.addEventListener('change', () => {
                    // Remove the card from unansweredCards when it's answered
                    unansweredCards = unansweredCards.filter(c => c !== card);

                    // Check if there are any unanswered cards left
                    if (unansweredCards.length > 0) {
                        applyBounceEffect(unansweredCards[0]);
                    }
                });
            });
        });

        return; // Stop the function if not all questions are answered
    }

    // Second pass: Calculate form_score and show icons since all questions are answered
    cards.forEach(card => {
        const correctAnswer = card.getAttribute('data-answer');
        const questionId = card.getAttribute('data-question-id');
        const selectedAnswer = document.querySelector(`input[name="question_${questionId}"]:checked`);

        if (selectedAnswer.value === correctAnswer) {
            form_score++;
            card.classList.add('correct'); // Add class for correct answers
        } else {
            card.classList.add('wrong'); // Add class for incorrect answers
        }

        // Highlight the correct answer
        const correctAnswerElement = Array.from(card.querySelectorAll('input')).find(input => input.value === correctAnswer);

        if (correctAnswerElement) {
            const parentElement = correctAnswerElement.parentElement;
            parentElement.style.position = 'relative';
            applyBlinkEffect(parentElement);
        }
    });

    // Disable all radio buttons after submission
    const radioButtons = document.querySelectorAll('input[type="radio"]');
    radioButtons.forEach(radio => {
        radio.disabled = true; // Disable all radio buttons
    });

    // Re-enable the selected radio buttons
    cards.forEach(card => {
        const questionId = card.getAttribute('data-question-id');
        const selectedAnswer = document.querySelector(`input[name="question_${questionId}"]:checked`);

        if (selectedAnswer) {
            selectedAnswer.disabled = false; // Re-enable the selected radio button
        }
    });

    const form_scoreDisplay = document.getElementById('form_scoreDisplay');
    if (form_scoreDisplay) {
        form_scoreDisplay.textContent = `Score: ${form_score} / ${totalQuestions}`;
        form_scoreDisplay.style.display = 'block'; // Show the form_score display
    }

    // Update progress bar
    updateProgressBar(form_score, totalQuestions);

    // Hide submit button and show retake and next test buttons
    document.getElementById('submit-button').style.display = 'none';
    document.getElementById('retake-button').style.display = 'block';
    document.getElementById('next-test-button').style.display = 'block';

    // Clear local storage after successful submission
    if (allAnswered) {
        localStorage.removeItem('quizProgress');
    }
}







function updateProgressBar(form_correctAnswers, form_totalQuestions) {
    var form_correctPercentage = form_totalQuestions > 0 ? (form_correctAnswers / form_totalQuestions) * 100 : 0;
    var form_incorrectPercentage = 100 - form_correctPercentage;

    form_correctPercentage = form_correctPercentage.toFixed(2); // Limit to two decimal places

    var form_correctBar = document.getElementById('progress-bar-correct');
    var form_incorrectBar = document.getElementById('progress-bar-incorrect');
    var form_percentageLabel = document.getElementById('percentage-label');

    if (form_correctBar && form_incorrectBar && form_percentageLabel) {
        // Update the widths of the bars and percentage label
        form_correctBar.style.width = form_correctPercentage + '%';
        form_incorrectBar.style.width = form_incorrectPercentage + '%';
        form_percentageLabel.textContent = form_correctPercentage + '%';
    }
}



function retakeTest() {
    // Increment attempt count in the session
    document.getElementById('attempt_action').value = 'increment_attempt_count';

    // Reset the form
    document.getElementById('examForm').reset();

    // Clear any highlighted areas and animation
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.remove('correct', 'wrong', 'missing-answer');
        const choices = card.querySelectorAll('.choices label');
        choices.forEach(choice => {
            choice.style.backgroundColor = '';
            choice.style.borderRadius = '';
        });
        const highlightElements = card.querySelectorAll('.highlight-container');
        highlightElements.forEach(elem => elem.classList.remove('highlight-container'));

        // Remove blinking effect
        const highlightedElements = card.querySelectorAll('.blink-effect');
        highlightedElements.forEach(elem => {
            elem.classList.remove('blink-effect');
            elem.style.animation = ''; // Clear the animation style
        });
    });

    // Reset the form_score and progress
    document.getElementById('form_scoreDisplay').textContent = `Score: 0 / ${<?php echo $_SESSION['form_questions_count']; ?>}`;
    document.getElementById('progress-bar-correct').style.width = '0%';
    document.getElementById('progress-bar-incorrect').style.width = '100%';
    document.getElementById('percentage-label').textContent = '0%';

    // Hide retake and next test buttons
    document.getElementById('retake-button').style.display = 'none';
    document.getElementById('next-test-button').style.display = 'none';

    // Show the submit button
    document.getElementById('submit-button').style.display = 'block';

    // Enable all radio buttons and remove button selection
    const radioButtons = document.querySelectorAll('input[type="radio"]');
    radioButtons.forEach(button => {
        button.disabled = false;
        button.checked = false;
    });
    const buttons = document.querySelectorAll('.choice-button');
    buttons.forEach(button => {
        button.classList.remove('selected');
    });

    // Shuffle the existing questions
    shuffleQuestions();

    // Submit the form to handle the increment of attempt count
    document.getElementById('examForm').submit(); //RESPONSIBLE OF TAKES COUNT BUT ALSO DISSHUFFLES QUESTIONS
}



function NextTest() {
    document.getElementById('next_test').value = '1'; // Set hidden input to indicate next test
    document.getElementById('examForm').submit(); // Submit the form
}


function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]]; // Swap elements
    }
    return array;
}

function shuffleQuestions() {
    const questionsContainer = document.getElementById('questions-container');
    const cards = Array.from(questionsContainer.querySelectorAll('.card'));

    // Shuffle the cards
    const shuffledCards = shuffleArray(cards);

    // Clear current questions
    questionsContainer.innerHTML = '';

    // Append shuffled questions with preserved numbering
    shuffledCards.forEach((card, index) => {
        const questionNumber = index + 1; // Maintain numbering

        // Update the card content with the preserved numbering
        card.querySelector('.question h5').innerHTML = `${questionNumber}. ${card.querySelector('.question h5').innerHTML.split('. ')[1]}`;
        
        questionsContainer.appendChild(card);
    });
}





// Whenever a user selects an answer, save it to local storage:
function saveAnswer(questionId, selectedValue) {
    let savedAnswers = JSON.parse(localStorage.getItem('quizProgress')) || {};
    savedAnswers[questionId] = selectedValue;
    localStorage.setItem('quizProgress', JSON.stringify(savedAnswers));
}

document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const questionId = this.name.split('_')[1];
        saveAnswer(questionId, this.value);
    });
});

// When the page loads, check if there are any saved answers in local storage and apply them:
window.addEventListener('DOMContentLoaded', () => {
    let savedAnswers = JSON.parse(localStorage.getItem('quizProgress')) || {};

    document.querySelectorAll('.card').forEach(card => {
        const questionId = card.getAttribute('data-question-id');
        const savedAnswer = savedAnswers[questionId];

        if (savedAnswer) {
            const radioButton = document.querySelector(`input[name="question_${questionId}"][value="${savedAnswer}"]`);
            if (radioButton) {
                radioButton.checked = true;
            }
        }
    });
});




$(document).ready(function() {
    // Function to update the progress bar and percentage label
    function updateProgressBar(form_correctAnswers, form_totalQuestions) {
        var form_correctPercentage = form_totalQuestions > 0 ? (form_correctAnswers / form_totalQuestions) * 100 : 0;
        var form_incorrectPercentage = 100 - form_correctPercentage;

        form_correctPercentage = form_correctPercentage.toFixed(2); // Limit to two decimal places

        var form_correctBar = document.getElementById('progress-bar-correct');
        var form_incorrectBar = document.getElementById('progress-bar-incorrect');
        var form_percentageLabel = document.getElementById('percentage-label');

        // Update the widths of the bars and percentage label
        form_correctBar.style.width = form_correctPercentage + '%';
        form_incorrectBar.style.width = form_incorrectPercentage + '%';
        form_percentageLabel.textContent = form_correctPercentage + '%';
    }

    // Initialize progress bar
    var form_totalQuestions = <?php echo json_encode($form_total_questions); ?>;
    var form_correctAnswers = <?php echo json_encode($form_correct_answers); ?>;

    if (form_totalQuestions === 0 && form_correctAnswers === 0) {
        // Start with 50% blue and 50% red
        document.getElementById('progress-bar-correct').style.width = '50%';
        document.getElementById('progress-bar-incorrect').style.width = '50%';
        document.getElementById('percentage-label').textContent = '50%';
    } else {
        // Update the progress bar based on actual data
        updateProgressBar(form_correctAnswers, form_totalQuestions);
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
