<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "db.php";

// Enable strict error reporting for safety
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 🧠 Handle Borrow Book submission
if (isset($_POST['confirm_borrow'])) {
    $book_id = $_POST['book_id'];
    $borrower_id = trim($_POST['borrower_id']);
    $borrower_name = trim($_POST['borrower_name']);
    $course = $_POST['course'];
    $copies_borrowed = $_POST['copies_borrowed'];
    $return_date = $_POST['return_date'];
    $today = date("Y-m-d");

    // ✅ Basic validation
    if (
        empty($book_id) || empty($borrower_id) || empty($borrower_name) ||
        empty($course) || empty($copies_borrowed) || empty($return_date)
    ) {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['message_type'] = "error";
        header("Location: available_books.php");
        exit();
    }

    if (!is_numeric($copies_borrowed) || $copies_borrowed <= 0) {
        $_SESSION['message'] = "Invalid number of copies.";
        $_SESSION['message_type'] = "error";
        header("Location: available_books.php");
        exit();
    }

    try {
        // ✅ Start transaction
        $conn->begin_transaction();

        // Step 1: Check available copies
        $checkBook = $conn->prepare("SELECT available_copies FROM books WHERE id = ?");
        $checkBook->bind_param("i", $book_id);
        $checkBook->execute();
        $checkBook->bind_result($available_copies);
        $checkBook->fetch();
        $checkBook->close();

        if ($copies_borrowed > $available_copies) {
            throw new Exception("Not enough copies available to borrow.");
        }

        // Step 2: Deduct copies from books table
        $updateBook = $conn->prepare("
            UPDATE books 
            SET available_copies = available_copies - ? 
            WHERE id = ?
        ");
        $updateBook->bind_param("ii", $copies_borrowed, $book_id);
        $updateBook->execute();
        $updateBook->close();

        // Step 3: Insert into borrow_records table
        $insertRecord = $conn->prepare("
            INSERT INTO borrow_records 
                (book_id, borrower_id, borrower_name, borrower_course, date_borrowed, expected_return_date, copies_borrowed)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertRecord->bind_param(
            "isssssi",
            $book_id,
            $borrower_id,
            $borrower_name,
            $course,
            $today,
            $return_date,
            $copies_borrowed
        );
        $insertRecord->execute();
        $insertRecord->close();

        // ✅ Commit the transaction
        $conn->commit();

        $_SESSION['message'] = "Book borrowed successfully!";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error borrowing book: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }

    header("Location: available_books.php");
    exit();
}
?>
