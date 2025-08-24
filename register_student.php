<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../register_student.html?error=Invalid request method");
    exit;
}

try {
    // Get form data
    $firstname = sanitize_input($_POST['firstname'] ?? '');
    $lastname = sanitize_input($_POST['lastname'] ?? '');
    $student_id = sanitize_input($_POST['student_id'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $gender = sanitize_input($_POST['gender'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');

    // Validation
    $errors = [];
    if (empty($firstname)) $errors[] = "First name is required";
    if (empty($lastname)) $errors[] = "Last name is required";
    if (empty($student_id)) $errors[] = "Student ID is required";
    if (strlen($student_id) < 3) $errors[] = "Student ID must be at least 3 characters";
    if (empty($email) || !validate_email($email)) $errors[] = "Valid email is required";
    if (empty($gender) || !in_array($gender, ['M', 'F', 'O'])) $errors[] = "Valid gender is required";
    if (empty($department)) $errors[] = "Department is required";

    if (!empty($errors)) {
        $error_message = implode(", ", $errors);
        header("Location: ../register_student.html?error=" . urlencode($error_message));
        exit;
    }

    // Database operations
    $pdo = getConnection();
    
    // Check if student exists
    $check_stmt = $pdo->prepare("SELECT student_id, email FROM students WHERE student_id = ? OR email = ?");
    $check_stmt->execute([$student_id, $email]);
    $existing = $check_stmt->fetch();

    if ($existing) {
        if ($existing['student_id'] === $student_id) {
            header("Location: ../register_student.html?error=" . urlencode("Student ID already exists"));
        } else {
            header("Location: ../register_student.html?error=" . urlencode("Email already registered"));
        }
        exit;
    }

    // Insert new student
    $insert_stmt = $pdo->prepare("INSERT INTO students (student_id, firstname, lastname, email, gender, department) VALUES (?, ?, ?, ?, ?, ?)");
    $result = $insert_stmt->execute([$student_id, $firstname, $lastname, $email, $gender, $department]);

    if ($result) {
        header("Location: ../enroll_club.html?success=registered&student_id=" . urlencode($student_id));
    } else {
        throw new Exception("Failed to register student");
    }

} catch (Exception $e) {
    header("Location: ../register_student.html?error=" . urlencode("Registration failed. Please try again."));
}
?>