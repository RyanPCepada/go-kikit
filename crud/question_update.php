<?php
// Include config file
require_once "../config.php";

// Define variables and initialize with empty values
$question = $category = $keyword = $hint = $questionType = "";
$choice_a = $choice_b = $choice_c = $choice_d = "";
$answer = "";
$question_err = $category_err = $keyword_err = $hint_err = $questionType_err = "";
$choice_err = $answer_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate question
    if (empty(trim($_POST["question"]))) {
        $question_err = "Please enter a question.";
    } else {
        $question = trim($_POST["question"]);
    }

    // Validate choices
    if (empty(trim($_POST["choice_a"])) || empty(trim($_POST["choice_b"])) || empty(trim($_POST["choice_c"])) || empty(trim($_POST["choice_d"]))) {
        $choice_err = "Please enter all choices.";
    } else {
        $choice_a = trim($_POST["choice_a"]);
        $choice_b = trim($_POST["choice_b"]);
        $choice_c = trim($_POST["choice_c"]);
        $choice_d = trim($_POST["choice_d"]);
    }

    // Validate answer
    if (empty(trim($_POST["answer"]))) {
        $answer_err = "Please enter the correct answer.";
    } else {
        $answer = trim($_POST["answer"]);
    }

    // Validate category
    if (empty(trim($_POST["category"]))) {
        $category_err = "Please enter a category.";
    } else {
        $category = trim($_POST["category"]);
    }

    // Validate keyword
    if (empty(trim($_POST["keyword"]))) {
        $keyword_err = "Please enter a keyword.";
    } else {
        $keyword = trim($_POST["keyword"]);
    }

    // Validate hint
    if (empty(trim($_POST["hint"]))) {
        $hint_err = "Please enter a hint.";
    } else {
        $hint = trim($_POST["hint"]);
    }

    // Validate question type
    if (empty(trim($_POST["questionType"]))) {
        $questionType_err = "Please enter the question type.";
    } else {
        $questionType = trim($_POST["questionType"]);
    }

    // Check input errors before updating in the database
    if (empty($question_err) && empty($choice_err) && empty($answer_err) && empty($category_err) && empty($keyword_err) && empty($hint_err) && empty($questionType_err)) {
        try {
            // Update tbl_questions
            $sql = "UPDATE tbl_questions SET question = :question, category = :category, keyword = :keyword, hint = :hint, questionType = :questionType WHERE question_id = :question_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':question' => $question,
                ':category' => $category,
                ':keyword' => $keyword,
                ':hint' => $hint,
                ':questionType' => $questionType,
                ':question_id' => $_POST["question_id"]
            ]);

            // Update tbl_choices
            $choice_ids = [
                'a' => $_POST["choice_a_id"],
                'b' => $_POST["choice_b_id"],
                'c' => $_POST["choice_c_id"],
                'd' => $_POST["choice_d_id"]
            ];

            $choices = [
                'a' => trim($_POST["choice_a"]),
                'b' => trim($_POST["choice_b"]),
                'c' => trim($_POST["choice_c"]),
                'd' => trim($_POST["choice_d"])
            ];

            foreach ($choice_ids as $key => $choice_id) {
                $choice_sql = "UPDATE tbl_choices SET choice = :choice WHERE choice_id = :choice_id AND question_id = :question_id";
                $choice_stmt = $pdo->prepare($choice_sql);
                $choice_stmt->execute([
                    ':choice_id' => $choice_id,
                    ':choice' => $choices[$key],
                    ':question_id' => $_POST["question_id"]
                ]);
            }

            // Update tbl_answers
            $answer_sql = "UPDATE tbl_answers SET answer = :answer WHERE question_id = :question_id";
            $answer_stmt = $pdo->prepare($answer_sql);
            $answer_stmt->execute([
                ':answer' => $answer,
                ':question_id' => $_POST["question_id"]
            ]);

            // Redirect to success page
            header("location: ../questions_list.php");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    // Check if the question ID is set and valid, then fetch the current data
    if (isset($_GET["question_id"]) && !empty(trim($_GET["question_id"]))) {
        $question_id = trim($_GET["question_id"]);

        try {
            // Fetch question details
            $sql = "SELECT * FROM tbl_questions WHERE question_id = :question_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':question_id' => $question_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Retrieve individual field values
                $question = $row["question"];
                $category = $row["category"];
                $keyword = $row["keyword"];
                $hint = $row["hint"];
                $questionType = $row["questionType"];
                
                // Fetch choices
                $choice_sql = "SELECT * FROM tbl_choices WHERE question_id = :question_id";
                $choice_stmt = $pdo->prepare($choice_sql);
                $choice_stmt->execute([':question_id' => $question_id]);
                $choices = $choice_stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($choices) >= 4) {
                    // Assign the choices to variables
                    $choice_a = $choices[0]['choice'];
                    $choice_b = $choices[1]['choice'];
                    $choice_c = $choices[2]['choice'];
                    $choice_d = $choices[3]['choice'];
                    $choice_a_id = $choices[0]['choice_id'];
                    $choice_b_id = $choices[1]['choice_id'];
                    $choice_c_id = $choices[2]['choice_id'];
                    $choice_d_id = $choices[3]['choice_id'];
                } else {
                    echo "Error: Not enough choices found for this question.";
                }

                // Fetch the correct answer
                $answer_sql = "SELECT * FROM tbl_answers WHERE question_id = :question_id";
                $answer_stmt = $pdo->prepare($answer_sql);
                $answer_stmt->execute([':question_id' => $question_id]);
                $answer_row = $answer_stmt->fetch(PDO::FETCH_ASSOC);
                $answer = $answer_row['answer'];
            } else {
                echo "Error: No question found with this ID.";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Error: Missing question ID.";
    }
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Update Question</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #031525; /* Background color */
            color: #b5e3ff; /* Text color */
        }
        .wrapper {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
        }
        #label-update-question {
            text-align: left;
            color: #7cacf8;
            color: #b5e3ff;
        }
        .form-control {
            font-size: 24px;
            padding: 10px;
            width: 100%;
            color: #81accf;
            border: 2px solid #1e2a44;
            border-radius: 5px;
            background-color: #0d0e1f !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #7cacf8;
            box-shadow: 0 2px 4px rgba(181, 227, 255, 0.3);
            outline: none;
            color: #b5e3ff;
        }
        .form-control::placeholder {
            color: #81accf;
        }
        .btn-primary {
            background-color: #007bff; /* Blue button */
            border-color: #007bff; /* Blue border */
        }
        .btn-primary:hover {
            background-color: #0056b3; /* Darker blue on hover */
            border-color: #004085; /* Darker blue border on hover */
        }
        .invalid-feedback {
            color: #dc3545; /* Bootstrap red color for errors */
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2 class="mt-5" id="label-update-question">Update Question</h2>
        <p class="mb-3">Please fill this form to update the question.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <!-- <div class="form-group">
                <label>Question ID</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($question_id); ?>" readonly>
            </div> -->
            <div class="form-group">
                <label>Question</label>
                <input type="text" name="question" class="form-control <?php echo (!empty($question_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($question); ?>" required>
                <span class="invalid-feedback"><?php echo $question_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Choice A</label>
                <input type="text" name="choice_a" class="form-control <?php echo (!empty($choice_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($choice_a); ?>" required>
                <input type="hidden" name="choice_a_id" value="<?php echo htmlspecialchars($choice_a_id); ?>">
            </div>
            <div class="form-group">
                <label>Choice B</label>
                <input type="text" name="choice_b" class="form-control <?php echo (!empty($choice_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($choice_b); ?>" required>
                <input type="hidden" name="choice_b_id" value="<?php echo htmlspecialchars($choice_b_id); ?>">
            </div>
            <div class="form-group">
                <label>Choice C</label>
                <input type="text" name="choice_c" class="form-control <?php echo (!empty($choice_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($choice_c); ?>" required>
                <input type="hidden" name="choice_c_id" value="<?php echo htmlspecialchars($choice_c_id); ?>">
            </div>
            <div class="form-group">
                <label>Choice D</label>
                <input type="text" name="choice_d" class="form-control <?php echo (!empty($choice_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($choice_d); ?>" required>
                <input type="hidden" name="choice_d_id" value="<?php echo htmlspecialchars($choice_d_id); ?>">
            </div>
            <div class="form-group">
                <label>Answer</label>
                <input type="text" name="answer" class="form-control <?php echo (!empty($answer_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($answer); ?>" required>
                <span class="invalid-feedback"><?php echo $answer_err; ?></span>
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($category); ?>">
                <span class="invalid-feedback"><?php echo $category_err; ?></span>
            </div>
            <div class="form-group">
                <label>Keyword</label>
                <input type="text" name="keyword" class="form-control <?php echo (!empty($keyword_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($keyword); ?>">
                <span class="invalid-feedback"><?php echo $keyword_err; ?></span>
            </div>
            <div class="form-group">
                <label>Hint</label>
                <input type="text" name="hint" class="form-control <?php echo (!empty($hint_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($hint); ?>">
                <span class="invalid-feedback"><?php echo $hint_err; ?></span>
            </div>
            <div class="form-group">
                <label>Question Type</label>
                <select name="questionType" class="form-control <?php echo (!empty($questionType_err)) ? 'is-invalid' : ''; ?>">
                    <option value="Identification" <?php echo ($questionType == "Identification") ? 'selected' : ''; ?>>Identification</option>
                    <option value="Enumeration" <?php echo ($questionType == "Enumeration") ? 'selected' : ''; ?>>Enumeration</option>
                </select>
                <span class="invalid-feedback"><?php echo $questionType_err; ?></span>
            </div>
            <input type="hidden" name="question_id" value="<?php echo htmlspecialchars($question_id); ?>"/>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <a class="btn btn-secondary ml-2" onclick="window.history.back(); return false;">Cancel</a>
            </div>
        </form>

    </div>
</body>
</html>
