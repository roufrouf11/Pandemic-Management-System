<?php
require __DIR__ . '/../includes/auth.php';
checkRole('civil_protection');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ola ta pedia symplhrwmena
    if (empty($_POST['school_id']) || empty($_POST['message'])) {
        $_SESSION['error'] = "Συμπληρώστε όλα τα πεδία";
        header("Location: dashboard.php");
        exit();
    }

    
    $user_id = (int)$_SESSION['user_id'];
    $school_id = (int)$_POST['school_id'];
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    $video_path = null;

    // gia video
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/videos/';
        
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        //mono type mp4 , webm,ogg yosthrizei ayto
        $allowed_types = ['video/mp4', 'video/webm', 'video/ogg'];
        $file_type = $_FILES['video']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error'] = "Μη επιτρεπτός τύπος αρχείου. Επιτρέπονται μόνο MP4, WebM και OGG.";
            header("Location: dashboard.php");
            exit();
        }

        /
        if ($_FILES['video']['size'] > 20 * 1024 * 1024) {
            $_SESSION['error'] = "Το αρχείο υπερβαίνει το μέγιστο επιτρεπτό μέγεθος 20MB";
            header("Location: dashboard.php");
            exit();
        }

        
        $file_ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('video_') . '.' . $file_ext;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['video']['tmp_name'], $destination)) {
            // Αποθήκευση μόνο της σχετικής διαδρομής χωρίς το /ERGASIA_3_PHP/
            $video_path = 'uploads/videos/' . $filename;
            
            //dikawmata 
            chmod($destination, 0644);
        } else {
            $_SESSION['error'] = "Σφάλμα κατά τη μεταφόρτωση του βίντεο";
            header("Location: dashboard.php");
            exit();
        }
    }

    try {
        
        $stmt = $conn->prepare("
            INSERT INTO ενημερωση_σχολειων_αεροδρομιων 
            (id_πολιτικης, id_σχολειου, σχόλια, video_path) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $user_id, $school_id, $message, $video_path);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Η ειδοποίηση εστάλη επιτυχώς!" . ($video_path ? " (με βίντεο)" : "");
        } else {
            throw new Exception("Σφάλμα βάσης: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Σφάλμα: " . $e->getMessage();
        error_log("Notification Error: " . $e->getMessage());
    }
    
    header("Location: dashboard.php");
    exit();
}