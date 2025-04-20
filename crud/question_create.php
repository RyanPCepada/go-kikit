<?php
session_start(); // Ensure session is started
require_once "../config.php";

// Define variables and initialize with empty values
$questionType = $question = $hint = $questionType_err = $question_err = $hint_err = "";
$choices = [];
$choice_errs = [];
$answer = "";
$answer_err = "";
$category = $keyword = "";
$category_err = $keyword_err = "";


// Check if study_id is set in session
if (!isset($_SESSION['study_id']) || empty($_SESSION['study_id'])) {
    die("Study ID is not set in the session.");
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate question type
    $input_questionType = trim($_POST["questionType"]);
    if (empty($input_questionType)) {
        $questionType_err = "Please select the question type.";
    } else {
        $questionType = $input_questionType;
    }

    // Validate question
    $input_question = trim($_POST["question"]);
    if (empty($input_question)) {
        $question_err = "Please enter the question.";
    } else {
        $question = $input_question;
    }

    // Validate choices
    foreach ($_POST['choices'] as $index => $choice) {
        $input_choice = trim($choice);
        if (empty($input_choice)) {
            $choice_errs[$index] = "Please enter a choice.";
        } else {
            $choices[$index] = $input_choice;
        }
    }

    // Validate answer
    $input_answer = trim($_POST["answer"]);
    if (empty($input_answer)) {
        $answer_err = "Please select the correct answer.";
    } else {
        $answer = $input_answer;
    }

    // Validate category
    $input_category = trim($_POST["category"]);
    if (empty($input_category)) {
        $category = $input_category;
    } else {
        $category = $input_category;
    }

    // Validate keyword
    $input_keyword = trim($_POST["keyword"]);
    if (empty($input_keyword)) {
        $keyword = $input_keyword;
    } else {
        $keyword = $input_keyword;
    }

    // Validate hint
    $input_hint = trim($_POST["hint"]);
    if (empty($input_hint)) {
        $hint = $input_hint;
    } else {
        $hint = $input_hint;
    }

    // Check input errors before inserting into the database
    if (empty($questionType_err) && empty($question_err) && empty($answer_err) && empty($hint_err) && empty($choice_errs)) {
        try {
            $pdo->beginTransaction();

            // Insert question into the database
            $sql = "INSERT INTO tbl_questions (question, hint, category, keyword, questionType, dateAdded, dateModified, study_id) 
                    VALUES (:question, :hint, :category, :keyword, :questionType, NOW(), NOW(), :study_id)";

            $stmt = $pdo->prepare($sql);

            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":question", $question);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":keyword", $keyword);
            $stmt->bindParam(":hint", $hint);
            $stmt->bindParam(":questionType", $questionType);
            $stmt->bindParam(":study_id", $_SESSION['study_id']); // Ensure this is correct

            // Execute the prepared statement
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert question: " . implode(", ", $stmt->errorInfo()));
            }

            $question_id = $pdo->lastInsertId();

            // Insert choices into the database
            $sql = "INSERT INTO tbl_choices (choice, dateAdded, dateModified, question_id) VALUES (:choice, NOW(), NOW(), :question_id)";
            $stmt = $pdo->prepare($sql);
            foreach ($choices as $choice) {
                $stmt->bindParam(":choice", $choice);
                $stmt->bindParam(":question_id", $question_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert choice: " . implode(", ", $stmt->errorInfo()));
                }
            }

            // Insert the correct answer into the database
            $sql = "INSERT INTO tbl_answers (answer, dateModified, question_id) VALUES (:answer, NOW(), :question_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":answer", $answer);
            $stmt->bindParam(":question_id", $question_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert answer: " . implode(", ", $stmt->errorInfo()));
            }

            // Commit the transaction
            $pdo->commit();

            // Redirect to the questions page or display success message
            echo "<script>alert('Question added successfully!');</script>";
            echo "<script>window.location.href = '../crud/question_create.php';</script>";
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Oops! Something went wrong. Please try again later. Error: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Create Question</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #121429;
            color: #ffffff;
            padding: 5%;
        }
        body #label-add-new-question{
            text-align: center;
        }
        #label-add-new-question {
            margin-top: 20px;
            color: #7cacf8;
        }
        .container {
            margin-top: 50px;
            max-width: 600px;
            background-color: #0d0e1f;
            background-color: #0d2136;
            color: #ffffff;
            padding: 20px;
            border-radius: 8px;
        }
        .form-group label { /*Gi usa kesa isa-isahon*/
            color: #b5e3ff;
            font-size: 20px;
            margin-bottom: 0px;
        }
        .form-control {
            background-color: #1a1d29; /* Darker input background */
            font-size: 24px;
            color: #92a2b9;
            border-color: #444857;
        }
        .form-control:focus {
            background-color: #1a1d29;
            color: #b5e3ff;
        }

        .input-box {
            background-color: #031525;
            color: #b5e3ff;
            padding: 10px;
            border: 2px solid #1e2a44;
            border-radius: 5px; /* Add slight border radius for a boxy effect */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Light shadow */
            transition: border-color 0.3s, box-shadow 0.3s; /* Smooth transitions */
        }
        .input-box:focus {
            background-color: #031525;
            color: #b5e3ff;
            border-color: #7cacf8;
            box-shadow: 0 2px 4px rgba(181, 227, 255, 0.3); /* Shadow with #b5e3ff tint */
            outline: none; /* Remove default outline */
        }
        .input-box::placeholder {
            color: #81accf;
        }
        .btn-success {
            color: #ffffff;
            border: none;
        }
        .btn-cancel {
            background-color: transparent;
            color: #ffffff;
            border: 1px solid #ffffff;
        }
        .choice-box {
            margin-bottom: 10px;
        }
        .btn-sm {
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <h2 id="label-add-new-question">Add New Question</h2>
    <div class="container mt-4 mb-3">
        <form method="post" action="">
            <div class="form-group">
                <label for="questionType">Question Type:</label>
                <select id="questionType" name="questionType" class="form-control input-box mt-2" required>
                    <option value="Identification">Identification</option>
                    <option value="Enumeration">Enumeration</option>
                </select>
                <span class="text-danger"><?php echo $questionType_err; ?></span>
            </div>
            <div class="form-group">
                <label for="question">Question:</label>
                <textarea id="question" name="question" class="form-control input-box mt-2" rows="3" required></textarea>
                <span class="text-danger"><?php echo $question_err; ?></span>
            </div>
            <div id="choicesContainer">
                <div class="form-group choice-box">
                    <label for="choice_0">Choice 1:</label>
                    <input type="text" id="choice_0" name="choices[]" class="form-control input-box mt-2" required>
                    <span class="text-danger"><?php echo $choice_errs[0] ?? ''; ?></span>
                </div>
                <div class="form-group choice-box">
                    <label for="choice_1">Choice 2:</label>
                    <input type="text" id="choice_1" name="choices[]" class="form-control input-box mt-2" required>
                    <span class="text-danger"><?php echo $choice_errs[1] ?? ''; ?></span>
                </div>
            </div>
            <button type="button" id="addChoiceBtn" class="btn btn-info btn-sm mb-3">Add More Choice</button>
            <div class="form-group">
                <label for="answer">Correct Answer:</label>
                <select id="answer" name="answer" class="form-control input-box mt-2" required>
                </select>
                <span class="text-danger"><?php echo $answer_err; ?></span>
            </div>
            <div class="form-group">
                <label for="category">Category:</label>
                <input type="text" id="category" name="category" class="form-control input-box mt-2" value="<?php echo htmlspecialchars($category ?? ''); ?>">
                <span class="text-danger"><?php echo $category_err; ?></span>
            </div>
            <div class="form-group">
                <label for="keyword">Keyword:</label>
                <input type="text" id="keyword" name="keyword" class="form-control input-box mt-2" value="<?php echo htmlspecialchars($keyword ?? ''); ?>">
                <span class="text-danger"><?php echo $keyword_err; ?></span>
            </div>
            <div class="form-group">
                <label for="hint">Hint:</label>
                <input type="text" id="hint" name="hint" class="form-control input-box mt-2">
                <span class="text-danger"><?php echo $hint_err; ?></span>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-success btn-block mt-5">Add Question</button>
                <a onclick="history.back();" class="btn btn-transparent text-light mt-2">Go Back</a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function () {
            let choiceCount = 2;

            $('#addChoiceBtn').click(function () {
                choiceCount++;
                let choiceHtml = `
                    <div class="form-group choice-box">
                        <label for="choice_${choiceCount}">Choice ${choiceCount}</label>
                        <input type="text" id="choice_${choiceCount}" name="choices[]" class="form-control input-box mt-2">
                        <span class="text-danger"><?php echo ''; ?></span>
                    </div>`;
                $('#choicesContainer').append(choiceHtml);
                updateAnswerOptions();
            });

            function updateAnswerOptions() {
                $('#answer').empty().append('<option value="">Select Answer</option>');
                $('input[name="choices[]"]').each(function () {
                    let choiceValue = $(this).val();
                    if (choiceValue) {
                        $('#answer').append(`<option value="${choiceValue}">${choiceValue}</option>`);
                    }
                });
            }

            $(document).on('input', 'input[name="choices[]"]', function () {
                updateAnswerOptions();
            });
        });
    </script>
</body>
</html>
