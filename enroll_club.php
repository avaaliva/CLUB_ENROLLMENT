<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../enroll_club.html?error=Invalid request method");
    exit;
}

try {
    $student_id = sanitize_input($_POST['student_id'] ?? '');
    $clubs = $_POST['clubs'] ?? [];

    // Validation
    if (empty($student_id)) {
        header("Location: ../enroll_club.html?error=" . urlencode("Student ID is required"));
        exit;
    }

    if (empty($clubs) || !is_array($clubs)) {
        header("Location: ../enroll_club.html?error=" . urlencode("Please select at least one club") . "&student_id=" . urlencode($student_id));
        exit;
    }

    $valid_clubs = ['Dance', 'Music', 'Drama', 'Sports', 'Photography', 'Art'];
    foreach ($clubs as $club) {
        if (!in_array($club, $valid_clubs)) {
            header("Location: ../enroll_club.html?error=" . urlencode("Invalid club selection") . "&student_id=" . urlencode($student_id));
            exit;
        }
    }

    $pdo = getConnection();

    // Check if student exists
    $student_check = $pdo->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $student_check->execute([$student_id]);
    if (!$student_check->fetch()) {
        header("Location: ../enroll_club.html?error=" . urlencode("Student not found. Please register first") . "&student_id=" . urlencode($student_id));
        exit;
    }

    // Remove existing enrollments
    $pdo->beginTransaction();
    $delete_stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?");
    $delete_stmt->execute([$student_id]);

    // Insert new enrollments
    $insert_stmt = $pdo->prepare("INSERT INTO enrollments (student_id, club_name) VALUES (?, ?)");
    $successful_enrollments = 0;

    foreach ($clubs as $club) {
        if ($insert_stmt->execute([$student_id, $club])) {
            $successful_enrollments++;
        }
    }

    $pdo->commit();

    if ($successful_enrollments > 0) {
        header("Location: ../enroll_club.html?success=enrolled&count=" . $successful_enrollments . "&student_id=" . urlencode($student_id));
    } else {
        throw new Exception("No clubs were enrolled");
    }

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $student_id_param = !empty($student_id) ? "&student_id=" . urlencode($student_id) : "";
    header("Location: ../enroll_club.html?error=" . urlencode("Enrollment failed. Please try again.") . $student_id_param);
}
?>