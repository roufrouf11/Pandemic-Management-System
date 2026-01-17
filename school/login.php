<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../includes/db.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $schoolId = $_POST['school_id'] ?? '';
    $location = $_POST['location'] ?? '';

    try {
        $stmt = $conn->prepare("SELECT id_σχολειου, τοποθεσία 
                              FROM σχολειο 
                              WHERE id_σχολειου = ? AND τοποθεσία = ?");
        $stmt->bind_param("is", $schoolId, $location);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id_σχολειου'];
            $_SESSION['role'] = 'school';
            $_SESSION['location'] = htmlspecialchars($user['τοποθεσία']);
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Λάθος διαπιστευτήρια";
        }
    } catch (Exception $e) {
        error_log("School login error: " . $e->getMessage());
        $error = "Σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.";
    }
}
?>



<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σύνδεση Σχολείου - ZX1 Πλατφόρμα</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body class="bg-light-gradient" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-xl-5 col-lg-6 col-md-8">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-success text-white py-4 rounded-top-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-building fs-2 me-3"></i>
                            <div>
                                <h2 class="h4 mb-0">Σχολικές Μονάδες</h2>
                                <p class="mb-0 opacity-75">Εκπαιδευτική Πρόσβαση</p>
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
                                <label class="form-label fw-medium">ID Σχολείου</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-building-gear"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-lg" 
                                           name="school_id" placeholder="Εισάγετε ID" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Τοποθεσία</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-geo"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-lg" 
                                           name="location" placeholder="Εισάγετε τοποθεσία" required>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg fw-medium py-3">
                                    <i class="bi bi-door-open me-2"></i>Σύνδεση
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