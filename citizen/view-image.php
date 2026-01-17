<?php
require __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

$image_id = (int)($_GET['id'] ?? 0);

if ($image_id <= 0) {
    header("HTTP/1.0 400 Bad Request");
    exit("Invalid image ID");
}

// mono an poliths exei prosbash se eikona
if ($_SESSION['role'] === 'doctor') {
    $stmt = $conn->prepare("SELECT file_type, file_data FROM medical_images 
                          WHERE id = ? AND id_γιατρου = ?");
    $stmt->bind_param("ii", $image_id, $_SESSION['user_id']);
} else { 
    if (empty($_SESSION['amka'])) {
        header("HTTP/1.0 403 Forbidden");
        exit("Access denied");
    }
    $stmt = $conn->prepare("SELECT file_type, file_data FROM medical_images 
                          WHERE id = ? AND amka = ?");
    $stmt->bind_param("is", $image_id, $_SESSION['amka']);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $image = $result->fetch_assoc();
    
    if (empty($image['file_data'])) {
        header("HTTP/1.0 404 Not Found");
        exit("Image data not found");
    }
    
    
    header("Content-Type: " . $image['file_type']);
    header("Content-Length: " . strlen($image['file_data']));
    header("Content-Disposition: inline; filename=\"medical_image_{$image_id}\"");
    echo $image['file_data'];
} else {
    header("HTTP/1.0 404 Not Found");
    echo "Image not found or access denied";
}
exit();