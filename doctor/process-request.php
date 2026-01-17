<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

checkRole('doctor');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    
    try {
        
        $stmt = $conn->prepare("SELECT id, αμκα FROM αιτησεις_τεστ WHERE id = ? AND id_γιατρου = ?");
        $stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Η αίτηση δεν βρέθηκε ή δεν ανήκει σε εσάς");
        }

        
        $conn->begin_transaction();

        
        $update = $conn->prepare("UPDATE αιτησεις_τεστ SET 
                                status = ?, 
                                updated_at = NOW(),
                                response = ?
                                WHERE id = ?");
        $response = ($action === 'approve') ? 
                   "Εγκρίθηκε από τον γιατρό" : 
                   "Απορρίφθηκε από τον γιατρό";
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $update->bind_param("ssi", $status, $response, $request_id);
        
        if (!$update->execute()) {
            throw new Exception("Αποτυχία ενημέρωσης κατάστασης: " . $conn->error);
        }

        

        $conn->commit();
        $_SESSION['success'] = "Η αίτηση #$request_id " . ($action === 'approve' ? 'εγκρίθηκε' : 'απορρίφθηκε') . " επιτυχώς";
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error processing request: " . $e->getMessage());
        $_SESSION['error'] = "Σφάλμα: " . $e->getMessage();
    }
}

header("Location: dashboard.php");
exit();
?>