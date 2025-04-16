<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $study_id = $_POST['study_id'];
    $pin = $_POST['pin'];

    $sql = "SELECT pin1 FROM tbl_studies WHERE study_id = :study_id";
    if ($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(':study_id', $study_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() == 1) {
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($row['pin1'] == $pin) {
                        $_SESSION['study_id'] = $study_id; // Set the session variable
                        echo 'success';
                        exit;
                    } else {
                        echo 'failure';
                        exit;
                    }
                }
            } else {
                echo 'failure';
                exit;
            }
        } else {
            echo 'failure';
            exit;
        }

        unset($stmt);
    }

    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Studies Page</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: #031525;
        }

        .study-container {
            margin-top: 40px;
            max-width: 600px;
            min-height: 546px;
            position: relative;
            margin-bottom: 0px;
        }

        #head-what-study {
            color: #7cacf8;
        }

        .study-box {
            position: relative;
            padding: 10px;
            border-radius: 8px;
            color: white;
        }

        .study-box ul {
            padding: 0;
            margin: 0;
            list-style-type: none;
        }

        .study-button {
            display: block;
            width: 100%;
            min-height: 80px;
            margin: 0 0 10px 0;
            padding: 10px;
            background-color: #0d2136;
            color: #b5e3ff;
            border: 2px solid #1e2a44;
            border-radius: 5px;
            text-align: left;
            background: linear-gradient(to bottom, #0d2136 10%, rgba(13, 14, 31, 0) 100%);
        }

        .study-button:hover {
            background-color: #182d63;
            color: white;
            border: 2px solid #2c3a5e;
        }

        .study-button.selected {
            background-color: #3451a0;
            border: solid 2px #173151 !important;
            color: white;
        }

        .username-and-profpic {
            display: flex;
            align-items: center; /* Vertically center the content */
            justify-content: end;
            margin-top: -20px;
            margin-bottom: 10px;
            background-color: transparent; /* Keep the background transparent */
        }

        #profile-icon {
            height: 38px;
            margin-left: 3%;
            margin-right: 3%; /* Add some spacing between the profile picture and username */
            border: 2px solid #7cacf8;
            border-radius: 50%;
            align-self: flex-start; /* Keep the profile picture to the right */
        }

        .creator-username {
            color: #92a2b9;
            font-size: 14px;
            max-width: 80%; /* Allow full width usage */
            word-wrap: break-word; /* Allow breaking the word to the next line */
            text-align: right; /* Align the text to the right */
            margin-top: 5px; /* Adjust the spacing between the username and profile picture */
        }

        #hr {
            border: none;
            border-top: 1px solid #173151;
            border-top: 1px solid #1e2a44;
            margin-top: 15px;
            margin-bottom: 20px;
        }


        .btn-container {
            position: fixed;
            bottom: 0px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            padding: 15px !important;
            box-sizing: border-box;
            /* background: linear-gradient(to bottom, #0d0e1f 10%, #031525 100%); */
            background: linear-gradient(to top, #0d0e1f 10%, rgba(13, 14, 31, 0) 100%);
        }

        #start-studying-button:disabled {
            background-color: rgba(0, 123, 255, 1); /* Primary color with 50% opacity */
            border-color: rgba(0, 123, 255, 0.5); /* Primary color with 50% opacity */
            color: #ffffff; /* White text color */
            cursor: not-allowed;
        }
        #start-studying-button:not(:disabled) {
            background-color: #007bff;
            border: 2px solid transparent;
            color: #ffffff;
            animation: blink-text 1s infinite;
        }
        #create-new-button {
            padding: 0px;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: rgba(0, 0, 255, 0.5) !important; /* Blue with 50% opacity */
            background-color: blue;
        }

        #create-new-button p {
            color: white;
            font-size: 50px;
            margin-top: -8px;
            opacity: 1; /* fully visible */
        }

        
        @keyframes blink-text {
            0% {
                color: white
            }
            50% {
                color: lightgray;
            }
            100% {
                color: white;
            }
        }


        #pinModal {
            padding: 15px;
        }

        #pinModal .modal-content {
            background-color: #132a45;
            border-radius: 15px;
        }

        .pin-input-container {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        #pinModal .modal-header,
        #pinModal .modal-body,
        #pinModal .modal-footer {
            border-color: #173151;
        }
        
        #label-study-title {
            color: gold;
        }

        #label-enter-pin1 {
            color: #b5e3ff;
        }

        .pin-input {
            width: 40px;
            text-align: center;
            color: #b5e3ff;
            background-color: #0d2136;
            border: solid 2px #173151 !important;
        }

        .pin-input:focus {
            background-color: #0d2136;
            color: #b5e3ff;
        }

        .alert-container {
            margin-top: 10px;
            display: none;
        }


        @media (min-width: 768px) {
            .study-container {
                margin-top: 50px;
                max-width: 600px;
                min-height: 565px;
                position: relative;
            }
        }

    </style>
</head>
<body>
<div class="container study-container text-center">
    <h2 id="head-what-study">What to review?</h2>
    
    <div class="studies-container mt-3 mb-5">
        <div class="study-box">
            <ul>
                <?php
                require_once "config.php";

                // Fetch studies with profile pictures and creator usernames, excluding deleted or missing records
                $sql = "
                    SELECT s.study_id, s.title, o.profilePic, o.username
                    FROM tbl_studies s
                    LEFT JOIN tbl_creator o ON s.study_id = o.study_id
                    WHERE s.study_id IS NOT NULL AND o.profilePic IS NOT NULL AND o.username IS NOT NULL
                    ORDER BY s.title ASC
                ";

                if ($result = $pdo->query($sql)) {
                    $totalRows = $result->rowCount();

                    if ($totalRows > 0) {
                        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                            // Check for a valid profile picture and username
                            if (!empty($row['profilePic']) && !empty($row['username'])) {
                                $profilePic = htmlspecialchars($row['profilePic']);
                                $username = htmlspecialchars($row['username']);
                                echo '<li><button type="button" class="btn btn-block btn-transparent study-button text-center pl-3" 
                                    data-study-id="' . htmlspecialchars($row['study_id']) . '" 
                                    data-study-name="' . htmlspecialchars($row['title']) . '">
                                    <h4>' . htmlspecialchars($row['title']) . '</h4>
                                </button></li>
                                <div class="username-and-profpic">
                                    <span class="creator-username mt-2">Creator: ' . htmlspecialchars($username) . '</span>
                                    <img src="images/' . $profilePic . '" id="profile-icon" alt="Profile Picture">
                                </div>
                                ';
                            }
                        }
                    } else {
                        echo '<div class="alert alert-danger"><em>No studies found.</em></div>';
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }

                unset($pdo);
                ?>
            </ul>
        </div>
    </div>
    
    <div class="btn-container">
        <!-- <button type="button" class="btn btn-primary" id="start-studying-button" disabled>
            <h5>Go Review!</h5>
        </button> -->
        <button type="button" class="btn btn-transparent text-center align-items-center" id="create-new-button" onclick="window.location.href='study_create.php';">
            <p>+</p>
        </button>
    </div>
</div>

<!-- Modal -->
<div class="modal fade text-center" id="pinModal" tabindex="-1" role="dialog" aria-labelledby="pinModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="label-study-title"></h5>
        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <h5 class="modal-body-title mt-2 mb-3" id="label-enter-pin1">Enter PIN 1</h5>
        <div class="pin-input-container mb-5">
          <input type="text" class="pin-input form-control d-inline-block" maxlength="1" size="1">
          <input type="text" class="pin-input form-control d-inline-block" maxlength="1" size="1">
          <input type="text" class="pin-input form-control d-inline-block" maxlength="1" size="1">
          <input type="text" class="pin-input form-control d-inline-block" maxlength="1" size="1">
          <input type="text" class="pin-input form-control d-inline-block" maxlength="1" size="1">
          <input type="text" class="pin-input form-control d-inline-block" maxlength="1" size="1">
        </div>
        <div class="alert-container mt-0 mb-0" style="display:none;">
            <p class="text-danger">Incorrect PIN. Please try again.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    let selectedStudyId = null;
    let selectedtitle = '';

    $('.study-button').on('click', function() {
        selectedStudyId = $(this).data('study-id');
        selectedtitle = $(this).data('study-name'); // Get the study name
        
        // Update the modal title with the selected study name
        $('#label-study-title').text(selectedtitle);

        $('#pinModal').modal('show');
        setTimeout(function() {
            $('.pin-input').first().focus(); // Focus the first PIN input when the modal is shown
        }, 500); // Adjust the timeout value if needed

        $('.study-button').removeClass('selected');
        $(this).addClass('selected');
        // $('#start-studying-button').prop('disabled', false);
    });

    // $('#start-studying-button').on('click', function() {
    //     // Update the modal title with the selected study name
    //     $('#label-study-title').text(selectedtitle);

    //     $('#pinModal').modal('show');
    //     setTimeout(function() {
    //         $('.pin-input').first().focus(); // Focus the first PIN input when the modal is shown
    //     }, 500); // Adjust the timeout value if needed
    // });

    $('#pinModal').on('hidden.bs.modal', function () {
        $('.alert-container').hide();
        $('.pin-input').val(''); // Clear PIN inputs when the modal is closed
    });

    $('#pinModal').on('input', '.pin-input', function() {
        const pin = $('.pin-input').map(function() { return $(this).val(); }).get().join('');
        if (pin.length === 6) {
            $.ajax({
                url: 'studies.php',
                type: 'POST',
                data: {
                    study_id: selectedStudyId,
                    pin: pin
                },
                success: function(response) {
                    if (response.trim() === 'success') {
                        window.location.href = 'questions_game.php'; // Redirect to questions_game.php
                    } else {
                        $('.alert-container').show(); // Show error message
                        $('.pin-input').val(''); // Clear all PIN inputs
                        $('.pin-input').first().focus(); // Set focus to the first PIN input
                    }
                }
            });
        }
    });

    // Automatically move to the next input box
    $('.pin-input').on('input', function() {
        if (this.value.length == this.maxLength) {
            $(this).next('.pin-input').focus();
        }
    });

    // Handle backspace to move to the previous input box
    $('.pin-input').on('keydown', function(e) {
        if (e.key === 'Backspace' && this.value === '') {
            $(this).prev('.pin-input').focus();
            $(this).prev('.pin-input').val(''); // Clear the previous input box
        }
    });

    $(document).on('click', function(event) {
        if (!$(event.target).closest('.study-button, #start-studying-button, #pinModal').length) {
            $('.study-button').removeClass('selected');
            $('#start-studying-button').prop('disabled', true);
        }
    });

    // The "Please try again" link will no longer clear the PIN inputs
    $('#clear-pin-link').on('click', function(e) {
        e.preventDefault(); // Prevent default link behavior
    });
});

</script>
</body>
</html>
