<?php
// Check existence of id parameter before processing further
if(isset($_GET["question_id"]) && !empty(trim($_GET["question_id"]))){
    // Include config file
    require_once "../../config.php";
    
    // Prepare to select the question details
    $sql = "SELECT * FROM gokikit_tbl_questions WHERE question_id = :question_id";
    
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":question_id", $param_question_id);
        
        // Set parameters
        $param_question_id = trim($_GET["question_id"]);
        
        // Attempt to execute the prepared statement
        if($stmt->execute()){
            if($stmt->rowCount() == 1){
                // Fetch result row as an associative array
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Retrieve individual field values with "None" as default if empty
                $question = !empty($row["question"]) ? $row["question"] : 'None';
                $category = !empty($row["category"]) ? $row["category"] : 'None';
                $keyword = !empty($row["keyword"]) ? $row["keyword"] : 'None';
                $hint = !empty($row["hint"]) ? $row["hint"] : 'None';
                $questionType = $row["questionType"]; // No default value needed
                
                // Prepare to select choices
                $choices_sql = "SELECT choice FROM gokikit_tbl_choices WHERE question_id = :question_id ORDER BY choice_id";
                $choices_stmt = $pdo->prepare($choices_sql);
                $choices_stmt->bindParam(":question_id", $param_question_id);
                
                if ($choices_stmt->execute()) {
                    $choices = $choices_stmt->fetchAll(PDO::FETCH_COLUMN);
                    // Ensure there are exactly 4 choices (adjust if necessary)
                    $choices = array_pad($choices, 4, 'None'); // Fill with "None" if less than 4 choices
                } else {
                    $choices = array('None', 'None', 'None', 'None'); // Default if choices fetch fails
                }

                // Prepare to select the answer
                $answer_sql = "SELECT answer FROM gokikit_tbl_answers WHERE question_id = :question_id";
                $answer_stmt = $pdo->prepare($answer_sql);
                $answer_stmt->bindParam(":question_id", $param_question_id);
                
                if ($answer_stmt->execute()) {
                    $answer_row = $answer_stmt->fetch(PDO::FETCH_ASSOC);
                    $answer = !empty($answer_row["answer"]) ? $answer_row["answer"] : 'None';
                } else {
                    $answer = 'None'; // Default if answer fetch fails
                }
                
            } else{
                // URL doesn't contain valid id parameter. Redirect to error page
                header("location: ../public/error.php");
                exit();
            }
            
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
     
    // Close statement
    unset($stmt);
    
    // Close connection
    unset($pdo);
} else{
    // URL doesn't contain id parameter. Redirect to error page
    header("location: ../public/error.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - View Question</title>
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
        /* .container-fluid {
            background-color: #031525;
            padding: 15px;
            border: 1px solid #1e2a44;
            border-radius: 8px; /* Rounded corners
        } */
        #label-view-question {
            text-align: left;
            color: #7cacf8;
            color: #b5e3ff;
        }
        .form-group label {
            color: #7cacf8; /* Label color */
        }
        .btn-primary {
            background-color: #7cacf8; /* Button background color */
            border: none;
        }
        .btn-primary:hover {
            background-color: #66b2ff; /* Button hover color */
        }
        .form-group p {
             /*background-color: #0d2136; Choice and answer background color */
            padding: 0px;
            border-radius: 5px;
        }
        /* .btn {
            width: 50%;
        } */
        .highlight {
            background-color: #66b2ff; /* Highlight color */
            color: #031525; /* Highlight text color */
            padding: 0 0.2em;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                <h2 class="mt-5 mb-3" id="label-view-question">View Question</h2>
                    <div class="form-group">
                        <label><b>Question:</b></label>
                        <p><?php echo htmlspecialchars($question); ?></p>
                    </div>
                    <div class="form-group">
                        <label><b>Choice A:</b></label>
                        <p><?php echo htmlspecialchars($choices[0]); ?></p>
                    </div>
                    <div class="form-group">
                        <label><b>Choice B:</b></label>
                        <p><?php echo htmlspecialchars($choices[1]); ?></p>
                    </div>
                    <div class="form-group">
                        <label><b>Choice C:</b></label>
                        <p><?php echo htmlspecialchars($choices[2]); ?></p>
                    </div>
                    <div class="form-group">
                        <label><b>Choice D:</b></label>
                        <p><?php echo htmlspecialchars($choices[3]); ?></p>
                    </div>
                    <div class="form-group">
                        <label><b>Correct Answer:</b></label>
                        <p><?php echo htmlspecialchars(ucfirst($answer)); ?></p>
                    </div>
                    <div class="form-group">
                        <label><b>Category:</b></label>
                        <p><?php echo htmlspecialchars($category); ?></p>
                    </div>
                    <div class="form-group">
                        <label><b>Keyword:</b></label>
                        <p><?php echo htmlspecialchars($keyword); ?></p>
                    </div>
                    <div class="form-group">
                        <label><b>Hint:</b></label>
                        <p><?php echo htmlspecialchars($hint); ?></p>
                    </div>
                    <p><a href="#" class="btn btn-primary" onclick="window.history.back(); return false;">Back</a></p>
                </div>
            </div>        
        </div>
    </div>
</body>
</html>
