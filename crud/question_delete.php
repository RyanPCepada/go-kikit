<?php
// Process delete operation after confirmation
if (isset($_POST["question_id"]) && !empty($_POST["question_id"])) {
    // Include config file
    require_once "../config.php";

    // Begin a transaction
    $pdo->beginTransaction();

    try {
        // Delete related choices
        $sql_choices = "DELETE FROM tbl_choices WHERE question_id = :question_id";
        $stmt_choices = $pdo->prepare($sql_choices);
        $stmt_choices->bindParam(":question_id", $param_question_id);
        $param_question_id = trim($_POST["question_id"]);
        $stmt_choices->execute();

        // Delete related answers
        $sql_answers = "DELETE FROM tbl_answers WHERE question_id = :question_id";
        $stmt_answers = $pdo->prepare($sql_answers);
        $stmt_answers->bindParam(":question_id", $param_question_id);
        $stmt_answers->execute();

        // Delete the question
        $sql_question = "DELETE FROM tbl_questions WHERE question_id = :question_id";
        $stmt_question = $pdo->prepare($sql_question);
        $stmt_question->bindParam(":question_id", $param_question_id);
        $stmt_question->execute();

        // Commit the transaction
        $pdo->commit();

        // Records deleted successfully. Redirect to landing page
        header("location: ../questions_list.php");
        exit();
    } catch (PDOException $e) {
        // Rollback the transaction if something goes wrong
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }

    // Close statement
    unset($stmt_question);
    unset($stmt_answers);
    unset($stmt_choices);
    
    // Close connection
    unset($pdo);
} else {
    // Check existence of id parameter
    if (empty(trim($_GET["question_id"]))) {
        // URL doesn't contain question_id parameter. Redirect to error page
        header("location: ../crud/error.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Delete Question</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #031525;
        }
        .wrapper {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
            background-color: #031525;
        }
        #label-delete-question {
            text-align: left;
            color: #7cacf8;
            color: #b5e3ff;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="mt-5 mb-3" id="label-delete-question">Delete Question</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="alert alert-danger">
                            <input type="hidden" name="question_id" value="<?php echo htmlspecialchars(trim($_GET["question_id"])); ?>"/>
                            <p>Are you sure you want to delete this question?</p>
                            <p>
                                <input type="submit" value="Yes" class="btn btn-danger">
                                <a class="btn btn-secondary ml-2" href="javascript:history.back()">No</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>        
        </div>
    </div>
</body>
</html>
