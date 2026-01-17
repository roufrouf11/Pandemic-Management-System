<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../includes/db.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $civilId = $_POST['civil_id'] ?? '';
    $region = $_POST['region'] ?? '';

    try {
        $stmt = $conn->prepare("SELECT id_πολιτικης, περιφέρεια 
                              FROM πολιτικη_προστασια 
                              WHERE id_πολιτικης = ? AND περιφέρεια = ?");
        $stmt->bind_param("is", $civilId, $region);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id_πολιτικης'];
            $_SESSION['role'] = 'civil_protection';
            $_SESSION['region'] = htmlspecialchars($user['περιφέρεια']);
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Λάθος διαπιστευτήρια";
        }
    } catch (Exception $e) {
        error_log("Civil Protection login error: " . $e->getMessage());
        $error = "Σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.";
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σύνδεση Πολιτικής Προστασίας - ZX1 Πλατφόρμα</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body class="bg-light-gradient" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-xl-5 col-lg-6 col-md-8">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-danger text-white py-4 rounded-top-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-check fs-2 me-3"></i>
                            <div>
                                <h2 class="h4 mb-0">Πολιτική Προστασία</h2>
                                <p class="mb-0 opacity-75">Εξουσιοδοτημένη Σύνδεση</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 p-xl-5">
                        <?php if($error): ?>
                            <div class="alert alert-danger d-flex align-items-center mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-medium">ID Υπευθύνου</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-person-rolodex"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-lg" 
                                           name="civil_id" placeholder="Εισάγετε ID" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Περιφέρεια</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-geo-alt"></i>
                                    </span>
                                    <select class="form-select form-select-lg" name="region" required>
                                        <option value="Αττική">Αττική</option>
                                        <option value="Μακεδονία">Μακεδονία</option>
                                        <option value="Θεσσαλία">Θεσσαλία</option>
                                        <option value="Κρήτη">Κρήτη</option>
                                        <option value="Πελοπόννησος">Πελοπόννησος</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg fw-medium py-3">
                                    <i class="bi bi-shield-lock me-2"></i>Σύνδεση
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>