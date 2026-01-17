<?php
require __DIR__ . '/../includes/auth.php';
checkRole('doctor');
include __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amka = $_POST['amka'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // epibebaiwna AMKA
    $stmt = $conn->prepare("SELECT αμκα FROM πολιτης WHERE αμκα = ?");
    $stmt->bind_param("s", $amka);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Ο πολίτης με ΑΜΚΑ $amka δεν βρέθηκε.";
        header("Location: upload-image.php");
        exit();
    }
    
    // anebasma arxeiou eikonas
    if (isset($_FILES['medical_image']) && $_FILES['medical_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['medical_image'];
        
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error'] = "Μη επιτρεπτός τύπος αρχείου. Επιτρέπονται μόνο JPEG, PNG, GIF ή PDF.";
            header("Location: upload-image.php");
            exit();
        }
        
        if ($file['size'] > $max_size) {
            $_SESSION['error'] = "Το αρχείο υπερβαίνει το μέγιστο επιτρεπτό μέγεθος των 5MB.";
            header("Location: upload-image.php");
            exit();
        }
        
        // Read file c
        $file_data = file_get_contents($file['tmp_name']);
        
        // Save to database
        
        $stmt = $conn->prepare("INSERT INTO medical_images 
                            (amka, id_γιατρου, title, description, file_type, file_size, file_data) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $null = NULL; 
        $stmt->bind_param("sisssib", 
                        $amka, 
                        $_SESSION['user_id'], 
                        $title, 
                        $description, 
                        $file['type'], 
                        $file['size'],
                        $null);
        $stmt->send_long_data(6, file_get_contents($file['tmp_name'])); // Send the BLOB data
        $stmt->execute();
        
        $_SESSION['success'] = "Η εικόνα ανέβηκε με επιτυχία!";
        header("Location: dashboard.php");
        exit();
    }






}
?>

<div class="container-fluid px-xxl-5 px-lg-4 px-md-3">
    <div class="row g-4">
        

        
        <div class="col-lg-9 col-xl-10">
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0"><i class="bi bi-upload me-2"></i>Ανέβασμα Ιατρικής Εικόνας</h5>
                </div>
                <div class="card-body">
                    <form action="upload-image.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="amka" class="form-label">ΑΜΚΑ Ασθενούς</label>
                            <input type="text" class="form-control" id="amka" name="amka" required>
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Τίτλος</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Περιγραφή</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-4">
                            <label for="medical_image" class="form-label">Εικόνα/Αρχείο</label>
                            <input class="form-control" type="file" id="medical_image" name="medical_image" required>
                            <div class="form-text">Επιτρέπονται αρχεία JPEG, PNG, GIF ή PDF (μέγιστο μέγεθος 5MB)</div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>Ανέβασμα
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>