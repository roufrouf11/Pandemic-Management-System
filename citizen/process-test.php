<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../includes/auth.php';
checkRole('citizen');
require __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ola dedomena polith *
        $stmt = $conn->prepare("SELECT * FROM πολιτης WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $citizen = $stmt->get_result()->fetch_assoc();

        if (!$citizen) {
            throw new Exception("Citizen not found");
        }

        $doctor_id = $_POST['doctor'] ?? '';
        $comments = $_POST['comments'] ?? '';
        
        // upload arxeiou
        $media_file = null;
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'request_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $target_path)) {
                $media_file = $file_name;
            }
        }
        
        // to bazw stis aithseis sthn bash
        $stmt = $conn->prepare("INSERT INTO αιτησεις_τεστ 
                              (αμκα, όνομα, επίθετο, τηλέφωνο, id_γιατρου, media_file, σχόλια, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssiss", 
            $citizen['αμκα'],
            $citizen['όνομα'],
            $citizen['επώνυμο'],
            $citizen['τηλέφωνο'],
            $doctor_id,
            $media_file,
            $comments
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Η αίτηση υποβλήθηκε επιτυχώς! Ο γιατρός θα ενημερωθεί.";
        } else {
            throw new Exception("Database error: " . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Error in process-test.php: " . $e->getMessage());
        $_SESSION['error'] = "Σφάλμα κατά την υποβολή: " . $e->getMessage();
    }
}


header("Location: dashboard.php");
exit();
?>