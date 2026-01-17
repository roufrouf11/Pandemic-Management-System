<?php 
require __DIR__ . '/../includes/auth.php';
checkRole('citizen');
include __DIR__ . '/../includes/header.php';


$stmt = $conn->prepare("SELECT * FROM πολιτης WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$citizen = $stmt->get_result()->fetch_assoc();

//error messages
if(isset($_SESSION['success'])): ?>
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

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h2 mb-1">
                <i class="bi bi-file-earmark-medical me-2"></i>
                Νέα αίτηση για προγραμματισμό Τεστ ΖΧ1 
            </h1>
            <p class="text-muted mb-0">Συμπληρώστε τα παρακάτω στοιχεία για την υποβολή αίτησης</p>
        </div>
        <div class="steps">
            <span class="badge bg-primary px-3 py-2">
                <i class="bi bi-1-circle me-1"></i>Στοιχεία Πολίτη:
            </span>
        </div>
    </div>

    <!-- genarate deepseek gia na einai automata symplhrwmena ta pedia amka , kin , onoma k epwnumo-->
    <div class="card border-0 shadow-lg">
        <div class="card-body p-4 p-xl-5">
            <form action="process-test.php" method="POST" enctype="multipart/form-data" id="testRequestForm">
                <div class="row g-4">
                    <!-- AMKA (readonly) -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="amka" 
                                   value="<?= htmlspecialchars($citizen['αμκα'] ?? '') ?>" readonly>
                            <label for="amka"><i class="bi bi-person-badge me-2"></i>ΑΜΚΑ</label>
                        </div>
                    </div>
                    
                    <!-- Phone (readonly) -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="tel" class="form-control" id="phone" 
                                   value="<?= htmlspecialchars($citizen['τηλέφωνο'] ?? '') ?>" readonly>
                            <label for="phone"><i class="bi bi-telephone me-2"></i>Τηλέφωνο</label>
                        </div>
                    </div>

                    <!-- Name (readonly) -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="name" 
                                   value="<?= htmlspecialchars($citizen['όνομα'] ?? '') ?>" readonly>
                            <label for="name"><i class="bi bi-person me-2"></i>Όνομα</label>
                        </div>
                    </div>

                    <!-- Surname (readonly) -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="surname" 
                                   value="<?= htmlspecialchars($citizen['επώνυμο'] ?? '') ?>" readonly>
                            <label for="surname"><i class="bi bi-person-bounding-box me-2"></i>Επώνυμο</label>
                        </div>
                    </div>

                    
                    <div class="col-md-12">
                        <div class="form-floating">
                            <select class="form-control" id="doctor" name="doctor" required>
                                <option value="">Επιλέξτε Γιατρό</option>
                                <?php 
                                $doctors = $conn->query("SELECT id_γιατρου, όνομα, επώνυμο, ειδικότητα FROM γιατρος");
                                while($doctor = $doctors->fetch_assoc()): ?>
                                    <option value="<?= $doctor['id_γιατρου'] ?>">
                                        <?= htmlspecialchars($doctor['όνομα'] . ' ' . $doctor['επώνυμο']) ?> 
                                        (<?= htmlspecialchars($doctor['ειδικότητα']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <label for="doctor"><i class="bi bi-person-badge me-2"></i>Γιατρός</label>
                        </div>
                    </div>

                    
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="comments" name="comments" 
                                      placeholder="Σχόλια" style="height: 100px"></textarea>
                            <label for="comments"><i class="bi bi-chat-square-text me-2"></i>Σχόλια</label>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="col-12">
                        <div class="card border-dashed">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-file-earmark-arrow-up fs-1 text-muted mb-3"></i>
                                <h6 class="mb-2">Συνυποστηρικτικό Αρχείο</h6>
                                <p class="text-muted small mb-3">Μέγιστο μέγεθος: 5MB (PDF, JPG, PNG)</p>
                                <input type="file" class="form-control" name="media_file" 
                                       accept=".pdf,.jpg,.jpeg,.png" style="max-width: 300px; margin: 0 auto;">
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-3 pt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-send-check me-2"></i>Υποβολή Αίτησης
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

document.getElementById('testRequestForm').addEventListener('submit', function(e) {
    const doctorSelect = document.getElementById('doctor');
    if (doctorSelect.value === '') {
        e.preventDefault();
        alert('Παρακαλώ επιλέξτε γιατρό');
        doctorSelect.focus();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>