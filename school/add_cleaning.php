<?php
require __DIR__ . '/../includes/auth.php';
checkRole('school');
include __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = (int)$_SESSION['user_id'];
    $date = $conn->real_escape_string($_POST['date']);
    $responsible = $conn->real_escape_string($_POST['responsible']);
    
    $stmt = $conn->prepare("INSERT INTO καθαρισμός (ID_Σχολείου, Ημερομηνία, Υπεύθυνος_καθαρισμου) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $school_id, $date, $responsible);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Ο καθαρισμός προστέθηκε με επιτυχία!";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Προέκυψε σφάλμα κατά την προσθήκη.";
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσθήκη Καθαρισμού</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5>Προσθήκη Νέου Καθαρισμού</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="date" class="form-label">Ημερομηνία</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="responsible" class="form-label">Υπεύθυνος</label>
                            <input type="text" class="form-control" id="responsible" name="responsible" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                        <a href="dashboard.php" class="btn btn-secondary">Ακύρωση</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>