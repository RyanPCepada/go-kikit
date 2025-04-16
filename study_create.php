<?php
// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$title = $userName = "";
$title_err = $pin1_err = $pin2_err = $userName_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate study name
    $input_title = trim($_POST["title"]);
    if (empty($input_title)) {
        $title_err = "Please enter the study name.";
    } else {
        $title = $input_title;
    }

    // Validate username
    $input_userName = trim($_POST["userName"]);
    if (empty($input_userName)) {
        $userName = "Anonymous"; // Default value if username is not provided
    } else {
        $userName = $input_userName;
    }

    // Validate PIN 1
    $pin1 = implode("", $_POST["pin1"]);
    if (!ctype_digit($pin1) || strlen($pin1) !== 6) {
        $pin1_err = "Please enter a valid 6-digit PIN.";
    }

    // Validate PIN 2
    $pin2 = implode("", $_POST["pin2"]);
    if (!ctype_digit($pin2) || strlen($pin2) !== 6) {
        $pin2_err = "Please enter a valid 6-digit PIN.";
    }

    // Check input errors before inserting into the database
    if (empty($title_err) && empty($pin1_err) && empty($pin2_err) && empty($userName_err)) {
        // Prepare an insert statement for tbl_studies
        $sql_study = "INSERT INTO tbl_studies (title, pin1, pin2, dateAdded, dateModified) 
                      VALUES (:title, :pin1, :pin2, NOW(), NOW())";

        $stmt_study = $pdo->prepare($sql_study);

        // Bind variables to the prepared statement as parameters
        $stmt_study->bindParam(":title", $title);
        $stmt_study->bindParam(":pin1", $pin1);
        $stmt_study->bindParam(":pin2", $pin2);

        // Attempt to execute the prepared statement for tbl_studies
        if ($stmt_study->execute()) {
            // Get the last inserted study_id
            $study_id = $pdo->lastInsertId();

            // Prepare an insert statement for tbl_creator
            $sql_creator = "INSERT INTO tbl_creator (userName, profilePic, study_id, dateAdded, dateModified) 
                          VALUES (:userName, :profilePic, :study_id, NOW(), NOW())";

            $stmt_creator = $pdo->prepare($sql_creator);

            // Default profile picture
            $default_profilePic = "PROF_PIC.png";

            // Bind variables to the prepared statement as parameters
            $stmt_creator->bindParam(":userName", $userName);
            $stmt_creator->bindParam(":profilePic", $default_profilePic);
            $stmt_creator->bindParam(":study_id", $study_id);

            // Attempt to execute the prepared statement for tbl_creator
            if ($stmt_creator->execute()) {
                // Records created successfully. Redirect to another page or display success message
                echo "<script>alert('Reviewer added successfully!');</script>";
                echo "<script>window.location.href = 'studies.php';</script>";
                exit();
            } else {
                echo "Oops! Something went wrong with the creator profile insertion. Please try again later.";
            }

            // Close statement for tbl_creator
            unset($stmt_creator);
        } else {
            echo "Oops! Something went wrong with the study insertion. Please try again later.";
        }

        // Close statement for tbl_studies
        unset($stmt_study);
    }

    // Close connection
    unset($pdo);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Create Study</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
    background-color: #121429; /* Body color */
    color: white;
    padding: 5%;
}
body #label-create-study{
    text-align: center;
}
#label-create-study {
    margin-top: 20px;
    color: #7cacf8;
}
.container {
    margin-top: 30px;
    max-width: 600px;
    background-color: #0d2136;
    padding: 20px;
    border-radius: 10px;
    /* box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5); */
}
#label-study-name, #label-pin1, #label-pin2, #label-username {
    color: #b5e3ff;
    font-size: 20px;
    margin-bottom: 0px;
}
#label-pin1-description, #label-pin2-description, #label-creator-username {
    color: #92a2b9;
    font-size: 14px;
    margin: auto;
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
.btn-primary {
    background-color: #343a40; /* Navbar color */
    border-color: #343a40;
}
.btn-primary:hover {
    background-color: #454d54;
    border-color: #454d54;
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
.pin-container {
    display: flex;
    justify-content: center;
    gap: 10px;
}
.pin-box {
    text-align: center;
    width: 40px;
    background-color: #031525;
    color: #b5e3ff;
    border: 2px solid #1e2a44;
    border-radius: 5px; /* Add slight border radius for a boxy effect */
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Light shadow */
    transition: border-color 0.3s, box-shadow 0.3s; /* Smooth transitions */
}
.pin-box:focus {
    background-color: #031525;
    color: #b5e3ff;
    border-color: #7cacf8;
    box-shadow: 0 2px 4px rgba(181, 227, 255, 0.3); /* Shadow with #b5e3ff tint */
    outline: none; /* Remove default outline */
}
#hr {
    border: none;
    border-top: 1px solid #173151;
    /* border-top: 1px solid #1e2a44; */
    margin-top: 15px;
    margin-bottom: 20px;
}

    /* Responsive Design */
    @media (min-width: 768px) {
        .container {
            padding: 50px;
        }
        #label-create-study {
            margin-top: 0px;
        }
        .pin-box {
            width: 10%;
        }
    }

    </style>
</head>
<body>
    <h2 id="label-create-study">Create Your Own<br>Reviewer!</h2>
    <div class="container text-center">
    <form method="post" action="">
        <div class="form-group">
            <label for="title" id="label-study-name">Reviewer title:</label>
            <input type="text" id="title" name="title" placeholder="" class="form-control input-box mt-2" required>
            <span class="text-danger"><?php echo $title_err; ?></span>
        </div>
        <hr id="hr"></hr>
        <div class="form-group">
            <label for="pin1" id="label-pin1">PIN 1:</label>
            <div class="pin-container mt-2 mb-2">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" id="pin1" name="pin1[]" maxlength="1" class="form-control pin-box" required pattern="\d*" inputmode="numeric">
                <?php endfor; ?>
            </div>
            <span class="text-danger"><?php echo $pin1_err; ?></span>
            <label id="label-pin1-description"><i>PIN 1 will be used to open your Go-Study.</i></label>
        </div>
        <hr id="hr"></hr>
        <div class="form-group">
            <label for="pin2" id="label-pin2">PIN 2:</label>
            <div class="pin-container mt-2 mb-2">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" id="pin2" name="pin2[]" maxlength="1" class="form-control pin-box" required pattern="\d*" inputmode="numeric">
                <?php endfor; ?>
            </div>
            <span class="text-danger"><?php echo $pin2_err; ?></span>
            <label id="label-pin1-description"><i>PIN 2 will be used for other operations.</i></label>
        </div>
        <hr id="hr"></hr>
        <div class="form-group">
            <label for="userName" id="label-username">Creator username:</label>
            <input type="text" id="userName" name="userName" placeholder="" class="form-control input-box mt-2 mb-2">
            <label id="label-creator-username"><i>Username is optional. Leave it if unwanted.</i></label>
        </div>
        <hr id="hr"></hr>
        <div class="text-center">
            <button type="submit" class="btn btn-success btn-block">Add Reviewer</button>
            <a href="studies.php" class="btn btn-transparent text-light">Cancel</a>
        </div>
    </form>
</div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.querySelectorAll('.pin-box').forEach((box, index, boxes) => {
    box.addEventListener('input', function() {
        // Remove non-numeric characters
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // If the box has a value and it's the last box, prevent focus from moving to the next box
        if (this.value.length === 1 && index < boxes.length - 1) {
            boxes[index + 1].focus();
        }
    });

    box.addEventListener('keydown', function(e) {
        // Handle backspace
        if (e.key === "Backspace" && this.value.length === 0 && index > 0) {
            boxes[index - 1].focus();
        }
    });
});

    </script>
</body>
</html>
