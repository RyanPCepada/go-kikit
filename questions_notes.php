<?php
session_start();
require_once 'config.php'; // Include your database configuration file

// Get the study_id from session
$study_id = isset($_SESSION['study_id']) ? $_SESSION['study_id'] : 1;

// Fetch questions, choices, and answers for the specific study_id
$sql = "
    SELECT 
        q.question, 
        a.answer AS answer_text
    FROM tbl_questions q
    INNER JOIN tbl_answers a ON q.question_id = a.question_id
    WHERE q.study_id = :study_id
    ORDER BY a.answer ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':study_id', $study_id, PDO::PARAM_INT);
$stmt->execute();

$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

unset($stmt);
unset($pdo);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Notes List</title>
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
        .container {
            margin-top: 20px;
        }
        #label-notes-list {
            color: #7cacf8;
        }
        #searchInput, #searchInputFixed {
            font-size: 24px;
            padding: 10px;
            width: 100%; /* Make the input field take the full width of its container */
            color: #b5e3ff;
            border: 2px solid #1e2a44;
            border-radius: 5px; /* Rounded corners */
            background-color: #0d0e1f;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Light shadow */
            transition: border-color 0.3s, box-shadow 0.3s; /* Smooth transitions */
        }
        #searchInput:focus, #searchInputFixed:focus {
            border-color: #7cacf8;
            box-shadow: 0 2px 4px rgba(181, 227, 255, 0.3); /* Shadow with #b5e3ff tint */
            outline: none; /* Remove default outline */
            color: #b5e3ff;
        }
        #searchInput::placeholder, #searchInputFixed::placeholder {
            color: #81accf;
        }
        .table {
            background-color: #0d2136;
            border: 2px solid #1e2a44;
        }
        .table th, .table td {
            text-align: left;
            vertical-align: middle;
            border: 2px solid #1e2a44 !important;
            /* border: none !important; */
        }
        .table th {
            background-color: #031525;
            color: #b5e3ff;
        }
        .table td {
            color: #b5e3ff;
            padding: 0.75rem;
        }
        .table .question-col {
            width: 75%;
        }
        .table .answer-col {
            width: 25%;
        }
        .fixed-back-button {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000; /* Ensure it is above other content */
            color: #81accf;
            background-color: #031525;
            border-radius: 50%;
            border: 2px solid transparent; /* Initial border is transparent */
            transition: color 0.3s, background-color 0.3s, box-shadow 0.3s, border 0.3s; /* Smooth transitions */
        }

        .fixed-back-button:hover {
            color: #000; /* Change icon color on hover */
            background-color: #81accf; /* Change background color on hover */
            border: 2px solid #1e2a44; /* Optional: border color on hover */
        }

        .fixed-back-button.scrolled {
            color: black; /* Icon color when scrolled */
            background-color: #81accf; /* Background color when scrolled */
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); /* Drop shadow */
            border: 2px solid transparent; /* Remove border in scrolled state */
        }

        .fixed-back-button.scrolled:hover {
            color: black; /* Icon color when scrolled and hovered */
            background-color: #81accf; /* Background color when scrolled and hovered */
            border: 2px solid transparent; /* Ensure no border in scrolled and hovered state */
        }

        .fixed-back-button .fas {
            transition: color 0.3s; /* Smooth transition for icon color */
        }
        .search-container-top {
            color: #b5e3ff;
            margin-bottom: 20px;
        }
        .fixed-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: transparent;
            background: linear-gradient(to top, #0d0e1f 5%, rgba(13, 14, 31, 0) 100%);
            border-top: none;
            padding: 10px;
            z-index: 1000; /* Ensure it is above other content */
            display: none; /* Hide by default */
        }
        .fixed-bottom .search-container-bottom {
            color: #b5e3ff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        #recordCount, #recordCountFixed {
            font-size: 14px;
            color: blue;
        }
        .wrapper {
            padding-bottom: 60px; /* Ensure content doesn't hide behind the fixed element */
        }
        .highlight {
            background-color: #66b2ff; /* Bootstrap lighter primary blue color */
            color: white; /* Ensure text is readable */
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
                    <div class="mt-1 mb-3 clearfix">
                        <button class="fixed-back-button btn" onclick="history.back();">
                            <i class="fas fa-arrow-left" style="font-size: 14px;"></i>
                        </button>
                        <h2 class="text-center mb-0 mt-5" id="label-notes-list">Notes List</h2>
                    </div>
                    <div class="search-container-top">
                        <input class="form-control" type="search" placeholder="Search" aria-label="Search" id="searchInput">
                        <h6 class="text-center mt-2" id="recordCount">Showing <span id="visibleRows"><?php echo count($questions); ?></span> / <span id="totalRows"><?php echo count($questions); ?></span> Records</h6>
                    </div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th class="answer-col">Answer</th>
                                <th class="question-col">Question</th>
                            </tr>
                        </thead>
                        <tbody id="notesTbody">
                            <?php if (count($questions) > 0): ?>
                                <?php foreach ($questions as $row): ?>
                                    <tr>
                                        <td class="answer-col"><?php echo htmlspecialchars($row['answer_text']); ?></td>
                                        <td class="question-col"><?php echo htmlspecialchars($row['question']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center">No notes found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div id="noResultsMessage" class="alert alert-danger" style="display:none;"><em>No results found.</em></div>
                </div> <!-- Close col-md-12 -->
            </div> <!-- Close row -->
        </div> <!-- Close container-fluid -->
    </div>

    <div class="fixed-bottom">
        <div class="container text-center align-items-center justify-content-center">
            <div class="row">
                <div class="search-container-bottom col-md-7">
                    <input class="form-control" type="search" placeholder="Search" aria-label="Search" id="searchInputFixed">
                </div>
                <div class="col-md-5 d-flex align-items-center justify-content-center mt-2">
                    <h6 id="recordCountFixed" class="record-count mb-0">Showing <span id="visibleRowsFixed"></span> / <span id="totalRowsFixed"></span> Records</h6>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            function updateRecordCount(visibleRows) {
                var totalRows = $("#notesTbody tr").length;
                if (typeof visibleRows === 'undefined') {
                    visibleRows = totalRows; // Initialize visibleRows if not provided
                }
                $("#visibleRows").text(visibleRows);
                $("#totalRows").text(totalRows);

                $("#visibleRowsFixed").text(visibleRows);
                $("#totalRowsFixed").text(totalRows);

                if (visibleRows === 0) {
                    $("#noResultsMessage").show();
                } else {
                    $("#noResultsMessage").hide();
                }
            }

            function highlightText(text, searchTerm) {
                if (searchTerm.trim() === '') {
                    return text;
                }
                var regex = new RegExp('(' + searchTerm + ')', 'gi');
                return text.replace(regex, '<span class="highlight">$1</span>');
            }

            function performSearch() {
                var value = $("#searchInput, #searchInputFixed").val().toLowerCase();
                var visibleRows = 0;

                $("#notesTbody tr").each(function() {
                    var $row = $(this);
                    var isVisible = false;

                    $row.find('td').each(function() {
                        var $cell = $(this);
                        var cellText = $cell.text();

                        var highlightedText = highlightText(cellText, value);
                        $cell.html(highlightedText);
                        if (cellText.toLowerCase().indexOf(value) > -1) {
                            isVisible = true;
                        }
                    });

                    $row.toggle(isVisible);
                    if (isVisible) {
                        visibleRows++;
                    }
                });

                updateRecordCount(visibleRows);
            }

            $("#searchInput, #searchInputFixed").on("input", function() {
                var value = $(this).val();
                $("#searchInput, #searchInputFixed").val(value); // Sync the input fields
                performSearch(); // Perform the search
            });

            $(window).on('scroll', function() {
                var topSearchButtonHeight = $('.fixed-back-button').outerHeight();
                if ($(this).scrollTop() > topSearchButtonHeight) {
                    $('.fixed-bottom').fadeIn(); // Show bottom search bar
                } else {
                    $('.fixed-bottom').fadeOut(); // Hide bottom search bar
                }

                if ($(this).scrollTop() > 0) {
                    $('.fixed-back-button').addClass('scrolled');
                } else {
                    $('.fixed-back-button').removeClass('scrolled');
                }
                
                var button = document.querySelector('.fixed-back-button');
                if (window.scrollY > 50) { // Adjust scroll threshold as needed
                    button.classList.add('scrolled');
                } else {
                    button.classList.remove('scrolled');
                }
            });


            // Initialize record count on page load
            updateRecordCount();
        });
    </script>
</body>
</html>
