<?php
require __DIR__ . '/../includes/auth.php';
checkRole('doctor');
include __DIR__ . '/../includes/header.php';

// forma giatrou
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        
        $amka = trim($_POST['amka'] ?? '');
        $result_type = trim($_POST['result'] ?? '');
        $comments = trim($_POST['comments'] ?? '');
        
        // psifia AMKA (11)
        if (!preg_match('/^[0-9]{11}$/', $amka)) {
            throw new Exception("Το ΑΜΚΑ πρέπει να αποτελείται από 11 ψηφία");
        }
        
        
        $allowed_results = ['θετικό', 'αρνητικό'];
        if (!in_array($result_type, $allowed_results)) {
            throw new Exception("Μη έγκυρο αποτέλεσμα τεστ");
        }

        

        // elegxw apo thn bash atheni
        $patient_check = $conn->prepare("SELECT αμκα FROM πολιτης WHERE αμκα = ?");
        $patient_check->bind_param("s", $amka);
        $patient_check->execute();
        $patient_check->store_result();
        
        if ($patient_check->num_rows === 0) {
            throw new Exception("Δεν βρέθηκε πολίτης με το συγκεκριμένο ΑΜΚΑ");
        }

        
        $id_query = $conn->query("SELECT MAX(id_τεστ) + 1 AS next_id FROM αποτελεσματα_τεστ");
        $id_result = $id_query->fetch_assoc();
        $next_id = $id_result['next_id'] ?? 1;

        // pernaw to apotelesma sthn bash
        $insert = $conn->prepare("INSERT INTO αποτελεσματα_τεστ 
            (id_τεστ, αμκα, id_γιατρου, ημερομηνία, τύπος, σχόλια, κατηγορία, κατάσταση) 
            VALUES (?, ?, ?, NOW(), ?, ?, 'ZX1', 'completed')");
        $insert->bind_param("isiss", 
            $next_id,
            $amka, 
            $_SESSION['user_id'], 
            $result_type, 
            $comments
        );

        if ($insert->execute()) {
            $_SESSION['success'] = "Το αποτέλεσμα ZX1 προστέθηκε επιτυχώς";
            header("Location: dashboard.php");
            exit();
        } else {
            throw new Exception("Σφάλμα κατά την εισαγωγή: " . $conn->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>


<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0">
                        <i class="bi bi-file-medical me-2"></i>Νέο Αποτέλεσμα ZX1
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="row g-3">
                            <!-- AMKA  -->
                            <div class="col-md-12">
                                <label for="amka" class="form-label">ΑΜΚΑ Ασθενούς <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="amka" id="amka"
                                       pattern="[0-9]{11}" title="11 ψηφία" required
                                       placeholder="Εισάγετε 11ψηφιο ΑΜΚΑ"
                                       value="<?= isset($_POST['amka']) ? htmlspecialchars($_POST['amka']) : '' ?>">
                                <div class="invalid-feedback">Παρακαλώ εισάγετε έγκυρο ΑΜΚΑ (11 ψηφία)</div>
                            </div>

                            <!-- apotelesma -->
                            <div class="col-md-12">
                                <label class="form-label">Αποτέλεσμα <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="result" id="positive" value="θετικό" required
                                        <?= (isset($_POST['result']) && $_POST['result'] === 'θετικό') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-danger" for="positive">
                                        <i class="bi bi-exclamation-triangle me-2"></i>Θετικό
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="result" id="negative" value="αρνητικό"
                                        <?= (isset($_POST['result']) && $_POST['result'] === 'αρνητικό') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="negative">
                                        <i class="bi bi-check-circle me-2"></i>Αρνητικό
                                    </label>
                                </div>
                                <div class="invalid-feedback d-block">Παρακαλώ επιλέξτε αποτέλεσμα</div>
                            </div>

                            =
                            <div class="col-12">
                                <label for="comments" class="form-label">Σχόλια</label>
                                <textarea class="form-control" name="comments" id="comments" rows="3"
                                          placeholder="Προαιρετικά σχόλια"><?= isset($_POST['comments']) ? htmlspecialchars($_POST['comments']) : '' ?></textarea>
                            </div>

                            =
                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-end gap-3">
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-2"></i>Ακύρωση
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>Αποθήκευση
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>