<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    $search = $_GET['search'] ?? '';
    $search = sanitize_input($search);

    if (!empty($search)) {
        $sql = "
            SELECT 
                s.student_id, s.firstname, s.lastname, s.email, s.department, s.gender,
                GROUP_CONCAT(e.club_name ORDER BY e.club_name) as clubs_concat
            FROM students s
            LEFT JOIN enrollments e ON s.student_id = e.student_id
            WHERE s.student_id LIKE ? OR s.firstname LIKE ? OR s.lastname LIKE ? OR s.email LIKE ? OR s.department LIKE ? OR CONCAT(s.firstname, ' ', s.lastname) LIKE ?
            GROUP BY s.student_id, s.firstname, s.lastname, s.email, s.department, s.gender
            ORDER BY s.firstname, s.lastname
        ";
        $searchParam = "%$search%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    } else {
        $sql = "
            SELECT 
                s.student_id, s.firstname, s.lastname, s.email, s.department, s.gender,
                GROUP_CONCAT(e.club_name ORDER BY e.club_name) as clubs_concat
            FROM students s
            LEFT JOIN enrollments e ON s.student_id = e.student_id
            GROUP BY s.student_id, s.firstname, s.lastname, s.email, s.department, s.gender
            ORDER BY s.firstname, s.lastname
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $results = $stmt->fetchAll();
    $enrollments = [];
    
    foreach ($results as $row) {
        $clubs = [];
        if (!empty($row['clubs_concat'])) {
            $clubs = explode(',', $row['clubs_concat']);
        }

        $enrollments[] = [
            'student_id' => $row['student_id'],
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'email' => $row['email'],
            'department' => $row['department'],
            'gender' => $row['gender'],
            'clubs' => $clubs
        ];
    }

    echo json_encode($enrollments);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching enrollments']);
}
?>