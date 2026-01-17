<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../includes/db.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amka = $_POST['amka'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';

    try {
        $stmt = $conn->prepare("SELECT id, όνομα, επώνυμο FROM πολιτης 
                              WHERE αμκα = ? AND όνομα = ? AND επώνυμο = ?");
        $stmt->bind_param("sss", $amka, $firstName, $lastName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'citizen';
            $_SESSION['user_name'] = htmlspecialchars($user['όνομα'] . ' ' . $user['επώνυμο']);
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Λάθος ΑΜΚΑ ή στοιχεία ταυτοποίησης";
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "Σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.";
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σύνδεση Πολίτη - ZX1 Πλατφόρμα</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body class="bg-light-gradient" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-xl-5 col-lg-6 col-md-8">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-primary text-white py-4 rounded-top-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-lock-fill fs-2 me-3"></i>
                            <div>
                                <h2 class="h4 mb-0">ZX1 Πλατφόρμα</h2>
                                <p class="mb-0 opacity-75">Ασφαλής Σύνδεση Πολίτη</p>
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
                                <label for="amka" class="form-label fw-medium">ΑΜΚΑ</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-person-badge"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-lg border-start-0" 
                                           id="amka" name="amka" placeholder="11ψηφιο ΑΜΚΑ" 
                                           pattern="[0-9]{11}" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label fw-medium">Όνομα</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-person-circle"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" 
                                               id="first_name" name="first_name" 
                                               placeholder="Όνομα" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label fw-medium">Επώνυμο</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-person-vcard"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" 
                                               id="last_name" name="last_name" 
                                               placeholder="Επώνυμο" required>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-medium py-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Σύνδεση
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