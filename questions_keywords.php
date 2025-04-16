<?php
session_start();
require_once 'config.php'; // Include your database configuration file

// Get the study_id from session
$study_id = isset($_SESSION['study_id']) ? $_SESSION['study_id'] : 1;

// Handle the category update if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoryName']) && isset($_POST['oldCategoryName'])) {
    $categoryName = trim($_POST['categoryName']);
    $oldCategoryName = trim($_POST['oldCategoryName']);
    
    // Validate input
    if (empty($categoryName)) {
        $_SESSION['error_message'] = 'Category name cannot be empty.';
        header('Location: questions_keywords.php'); // Redirect to the current page
        exit();
    }

    // Check if the new category name already exists
    $sql_check = "SELECT COUNT(*) FROM tbl_questions WHERE category = :category AND study_id = :study_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([
        'category' => $categoryName,
        'study_id' => $study_id
    ]);
    $count = $stmt_check->fetchColumn();
    
    if ($count > 0 && $categoryName !== $oldCategoryName) {
        $_SESSION['error_message'] = 'Category already exists.';
        header('Location: questions_keywords.php'); // Redirect to the current page
        exit();
    }

    // Update the category name
    $sql_update = "UPDATE tbl_questions SET category = :category WHERE category = :oldCategory AND study_id = :study_id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([
        'category' => $categoryName,
        'oldCategory' => $oldCategoryName,
        'study_id' => $study_id
    ]);

    header('Location: questions_keywords.php'); // Redirect to the current page
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer']) && isset($_POST['oldAnswer'])) {
    $answer = trim($_POST['answer']);
    $oldAnswer = trim($_POST['oldAnswer']);
    $keyword = trim($_POST['keyword']);
    $category = trim($_POST['category']);

    // Validate input
    if (empty($answer)) {
        $_SESSION['error_message'] = 'Answer cannot be empty.';
        header('Location: questions_keywords.php'); // Redirect to the current page
        exit();
    }

    // Check if the new answer already exists
    $sql_check = "SELECT COUNT(*) FROM tbl_answers WHERE answer = :answer AND answer <> :oldAnswer AND question_id IN (SELECT question_id FROM tbl_questions WHERE study_id = :study_id)";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([
        'answer' => $answer,
        'oldAnswer' => $oldAnswer,
        'study_id' => $study_id
    ]);
    $count = $stmt_check->fetchColumn();
    
    if ($count > 0) {
        $_SESSION['error_message'] = 'Answer already exists.';
        header('Location: questions_keywords.php'); // Redirect to the current page
        exit();
    }

    // Update the answer, keyword, and category
    $sql_update = "UPDATE tbl_answers a
                   JOIN tbl_questions q ON a.question_id = q.question_id
                   SET a.answer = :answer, q.keyword = :keyword, q.category = :category
                   WHERE a.answer = :oldAnswer AND q.study_id = :study_id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([
        'answer' => $answer,
        'keyword' => $keyword,
        'category' => $category,
        'oldAnswer' => $oldAnswer,
        'study_id' => $study_id
    ]);

    header('Location: questions_keywords.php'); // Redirect to the current page
    exit();
}


// Fetch existing categories
$sql_categories = "SELECT DISTINCT category FROM tbl_questions WHERE study_id = :study_id ORDER BY category ASC";
$stmt_categories = $pdo->prepare($sql_categories);
$stmt_categories->execute(['study_id' => $study_id]);
$categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN);


// Fetch answers and keywords, including those with empty keywords
$sql = "
    SELECT 
        q.category,
        q.keyword,
        a.answer AS answer_text
    FROM tbl_questions q
    INNER JOIN tbl_answers a ON q.question_id = a.question_id
    WHERE q.study_id = :study_id
    ORDER BY q.category ASC, a.answer ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['study_id' => $study_id]);

$keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group keywords by category
$groupedKeywords = [];
$uncategorizedAnswers = [];

// Iterate through the fetched results
foreach ($keywords as $row) {
    $category = $row['category'];
    if (!empty($category)) {
        if (!isset($groupedKeywords[$category])) {
            $groupedKeywords[$category] = [];
        }
        $groupedKeywords[$category][] = [
            'answer_text' => $row['answer_text'],
            'keyword' => !empty($row['keyword']) ? $row['keyword'] : 'None'
        ];
    } else {
        $uncategorizedAnswers[] = [
            'answer_text' => $row['answer_text'],
            'keyword' => 'None'
        ];
    }
}

// Find answers without keywords (for uncategorized answers)
$sql_no_keywords = "
    SELECT 
        a.answer AS answer_text
    FROM tbl_answers a
    LEFT JOIN tbl_questions q ON a.question_id = q.question_id
    WHERE q.keyword IS NULL OR q.keyword = ''
      AND q.study_id = :study_id
    GROUP BY a.answer
    HAVING COUNT(q.keyword) = 0
    ORDER BY a.answer ASC
";
$stmt_no_keywords = $pdo->prepare($sql_no_keywords);
$stmt_no_keywords->execute(['study_id' => $study_id]);
$additionalUncategorizedAnswers = $stmt_no_keywords->fetchAll(PDO::FETCH_ASSOC);

// Merge with previously fetched uncategorized answers
$uncategorizedAnswers = array_merge($uncategorizedAnswers, $additionalUncategorizedAnswers);
?>


<!--HERE-->
<!--HERE-->
<!--HERE-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Kikit! - Keywords List</title>
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
        #label-keywords-list {
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
        .table .keyword-col {
            width: 60%;
        }
        .table .answer-col {
            width: 40%;
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
        .form-select {
            width: 100%; /* Make sure select field takes full width */
        }

        #hr {
            border: none;
            border-top: 1px solid #173151;
            border-top: 1px solid #1e2a44;
            border-top: 1px solid red;
            margin-top: 50px;
            margin-bottom: 20px;
        }

    </style>
</head>
<body>

<!-- Display session messages for errors or success -->
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_SESSION['error_message']); ?>
        <?php unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        <?php unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>


    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="mt-1 mb-3 clearfix">
                        <button class="fixed-back-button btn" onclick="window.location.href='./questions_game.php';">
                            <i class="fas fa-arrow-left" style="font-size: 14px;"></i>
                        </button>
                        <h2 class="text-center mb-0 mt-5" id="label-keywords-list">Keywords List</h2>
                    </div>
                    <div class="search-container-top">
                        <input class="form-control" type="search" placeholder="Search" aria-label="Search" id="searchInput">
                        <h6 class="text-center mt-2" id="recordCount">Showing <span id="visibleRows"><?php echo count($keywords); ?></span> / <span id="totalRows"><?php echo count($keywords); ?></span> Records</h6>
                    </div>

                    <!-- Loop through categories and display the table for each category -->
                    <?php foreach ($groupedKeywords as $category => $rows): ?>
                        <h3 class="text-center mt-4 mb-3" style="color: gold;">
                            <?php echo htmlspecialchars($category); ?>
                            <a href="#" class="edit-category" data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-category="<?php echo htmlspecialchars($category); ?>" title="Edit Category">
                                <span class="fa fa-pencil-alt text-success" style="font-size: 18px; color: gray;"></span>
                            </a>

                        </h3>
                        <?php
                        $hasKeywords = false;
                        foreach ($rows as $row) {
                            if (!empty($row['keyword'])) {
                                $hasKeywords = true;
                                break;
                            }
                        }
                        ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="answer-col">Answer</th>
                                    <th class="keyword-col">Keywords</th>
                                    <th class="edit-col">Edit</th> <!-- Add an extra column for the edit icon -->
                                </tr>
                            </thead>
                            <tbody id="keywordsTbody">
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td class="answer-col"><?php echo htmlspecialchars($row['answer_text']); ?></td>
                                        <td class="keyword-col"><?php echo htmlspecialchars($row['keyword']); ?></td>
                                        <td class="edit-col">
                                        <a href="#" 
                                            class="edit-answer" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editAnswerKeywordModal"
                                            data-answer="<?php echo htmlspecialchars($row['answer_text']); ?>"
                                            data-keyword="<?php echo htmlspecialchars($row['keyword']); ?>" 
                                            title="Edit Answer and Keyword">
                                                <span class="fa fa-pencil-alt"></span>
                                        </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (!$hasKeywords): ?>
                            <div class="alert alert-warning" role="alert">
                                <em>Some of the answers have no keywords.</em>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <hr id="hr"></hr>

                    <!-- Display uncategorized answers -->
                    <?php if (!empty($uncategorizedAnswers)): ?>
                        <h3 class="text-center mt-4 mb-3" style="color: red;">Uncategorized</h3>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="answer-col">Answer</th>
                                    <th class="keyword-col">Keywords</th>
                                    <th class="edit-col">Edit</th> <!-- Add an extra column for the edit icon -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uncategorizedAnswers as $row): ?>
                                    <tr>
                                        <td class="answer-col"><?php echo htmlspecialchars($row['answer_text']); ?></td>
                                        <td class="keyword-col">None</td>
                                        <td class="edit-col">
                                            <a href="#" 
                                                class="edit-answer" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editAnswerKeywordModal"
                                                data-answer="<?php echo htmlspecialchars($row['answer_text']); ?>"
                                                data-keyword="None" 
                                                data-category="None" 
                                                title="Edit Answer and Keyword">
                                                    <span class="fa fa-pencil-alt"></span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <div id="noResultsMessage" class="alert alert-danger" style="display:none;"><em>Some of the categories may not have keywords.</em></div>
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


    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="font-size: 2rem; color: blue;">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm" method="POST">
                        <div class="mb-3">
                            <label for="categoryNameInput" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="categoryNameInput" name="categoryName" required>
                            <input type="hidden" id="categoryOldName" name="oldCategoryName">
                        </div>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Edit Answer and Keyword Modal -->
<div class="modal fade" id="editAnswerKeywordModal" tabindex="-1" aria-labelledby="editAnswerKeywordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAnswerKeywordModalLabel">Edit Answer and Keyword</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="font-size: 2rem; color: blue;">&times;</span>
                    </button>
            </div>
            <div class="modal-body">
                <form id="editAnswerKeywordForm" method="POST">
                    <div class="mb-3">
                        <label for="answerInput" class="form-label">Answer</label>
                        <input type="text" class="form-control" id="answerInput" name="answer" required>
                    </div>
                    <div class="mb-3">
                        <label for="keywordInput" class="form-label">Keyword</label>
                        <input type="text" class="form-control" id="keywordInput" name="keyword">
                    </div>
                    <div class="mb-3">
                        <label for="categorySelect" class="form-label">Category</label>
                        <select class="form-select" id="categorySelect" name="category">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" id="answerOldValue" name="oldAnswer">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>





    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    
    <script>
        $(document).ready(function() {
            function updateRecordCount(visibleRows) {
                var totalRows = $("#keywordsTbody tr").length;
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

                $("#keywordsTbody tr").each(function() {
                    var $row = $(this);
                    var isVisible = false;

                    $row.find('td').each(function(index) {
                        var $cell = $(this);

                        // Skip the last column which contains action icons (adjust index as needed)
                        if (index < $row.find('td').length - 1) {
                            var cellText = $cell.text();
                            var highlightedText = highlightText(cellText, value);
                            $cell.html(highlightedText);
                            if (cellText.toLowerCase().indexOf(value) > -1) {
                                isVisible = true;
                            }
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
            });

            // Initialize record count on page load
            updateRecordCount();
        });

        document.addEventListener('DOMContentLoaded', function() {
            var editCategoryLinks = document.querySelectorAll('.edit-category');
            var editAnswerLinks = document.querySelectorAll('.edit-answer');

            // Existing Category Edit Modal
            editCategoryLinks.forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    var categoryName = this.getAttribute('data-category');
                    var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                    document.getElementById('categoryNameInput').value = categoryName;
                    document.getElementById('categoryOldName').value = categoryName;
                    modal.show();
                });
            });

            // New Answer and Keyword Edit Modal
            editAnswerLinks.forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    var answer = this.getAttribute('data-answer');
                    var keyword = this.getAttribute('data-keyword');
                    var modal = new bootstrap.Modal(document.getElementById('editAnswerKeywordModal'));
                    document.getElementById('answerInput').value = answer;
                    document.getElementById('keywordInput').value = keyword;
                    document.getElementById('answerOldValue').value = answer;
                    modal.show();
                });
            });

            // Handle new category selection
            document.getElementById('editAnswerKeywordForm').addEventListener('submit', function(event) {
                var categorySelect = document.getElementById('categorySelect');
                if (categorySelect.value === 'new-category') {
                    event.preventDefault();
                    var newCategory = prompt('Enter the new category name:');
                    if (newCategory) {
                        var form = event.target;
                        var categoryInput = document.createElement('input');
                        categoryInput.type = 'hidden';
                        categoryInput.name = 'category';
                        categoryInput.value = newCategory;
                        form.appendChild(categoryInput);
                        form.submit();
                    }
                }
            });
        });

    </script>
</body>
</html>
