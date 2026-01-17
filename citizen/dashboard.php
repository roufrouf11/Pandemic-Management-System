<?php
require __DIR__ . '/../includes/auth.php';
checkRole('citizen');
include __DIR__ . '/../includes/header.php';

// data polith
$stmt = $conn->prepare("SELECT * FROM πολιτης WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$citizen = $stmt->get_result()->fetch_assoc();

// data apotelesmatwn polith
$tests_stmt = $conn->prepare("SELECT * FROM αποτελεσματα_τεστ WHERE αμκα = ? ORDER BY ημερομηνία DESC LIMIT 15");
$tests_stmt->bind_param("s", $citizen['αμκα']);
$tests_stmt->execute();
$tests = $tests_stmt->get_result();


// request poliths
$requests_stmt = $conn->prepare("SELECT id, status, created_at, updated_at, response 
                               FROM αιτησεις_τεστ 
                               WHERE αμκα = ? 
                               ORDER BY updated_at DESC 
                               LIMIT 5");
$requests_stmt->bind_param("s", $citizen['αμκα']);
$requests_stmt->execute();
$test_requests = $requests_stmt->get_result();









// apotelesmata
$all_tests_stmt = $conn->prepare("SELECT * FROM αποτελεσματα_τεστ WHERE αμκα = ? ORDER BY ημερομηνία");
$all_tests_stmt->bind_param("s", $citizen['αμκα']);
$all_tests_stmt->execute();
$all_tests = $all_tests_stmt->get_result();


$debug_test_types = [];
while($test = $all_tests->fetch_assoc()) {
    $debug_test_types[] = $test['τύπος'];
}


$all_tests->data_seek(0);

// gia grafhma metrw thetika k arnhtika
$positive = 0;
$negative = 0;
$test_dates = [];
while($test = $all_tests->fetch_assoc()) {
    
    $test_type = strtolower(trim($test['τύπος']));
    
    if(strpos($test_type, 'θετ') !== false) { // "Θετικό", "θετικό"
        $positive++;
    } elseif(strpos($test_type, 'αρν') !== false) {  "Αρνητικό", "αρνητικό"
        $negative++;
    }
    $test_dates[] = $test['ημερομηνία'];
}
$total_tests = $positive + $negative;


echo "<!-- Debug: Test types found: " . implode(", ", $debug_test_types) . " -->";
echo "<!-- Debug: Positive: $positive, Negative: $negative -->";






// elegxos polith karantina
$quarantine_stmt = $conn->prepare("
    SELECT *, 
           DATE_ADD(NOW(), INTERVAL -διάρκεια DAY) AS estimated_start_date,
           DATE_ADD(NOW(), INTERVAL διάρκεια DAY) AS estimated_end_date
    FROM καραντινα 
    WHERE id_πολιτη = ? 
    ORDER BY id_καραντινας DESC 
    LIMIT 1
");
$quarantine_stmt->bind_param("i", $citizen['id']);
$quarantine_stmt->execute();
$quarantine = $quarantine_stmt->get_result()->fetch_assoc();
$is_in_quarantine = ($quarantine !== null);


$_SESSION['amka'] = $citizen['αμκα'];


?>

<div class="container-fluid px-xxl-5 px-lg-4 px-md-3">
    <div class="row g-4">
        <!-- Side Navigation -->
        <div class="col-lg-3 col-xl-2">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <div class="avatar avatar-xl bg-primary-subtle text-primary mb-3">
                            <i class="bi bi-person-gear fs-3"></i>
                        </div>
                        <h6 class="mb-1"><?= htmlspecialchars($citizen['όνομα'] . ' ' . $citizen['επώνυμο']) ?></h6>
                        <span class="text-muted small">ΑΜΚΑ: <?= htmlspecialchars($citizen['αμκα']) ?></span>




                    </div>

                    <nav class="nav flex-column gap-2">
                        <a class="nav-link active bg-primary-subtle text-primary rounded-3" href="#">
                            <i class="bi bi-speedometer2 me-2"></i>Πίνακας Ελέγχου Πολίτη
                        </a>
                        <a class="nav-link text-dark" href="#historyModal" data-bs-toggle="modal">
                            <i class="bi bi-clock-history me-2"></i>Ιστορικό Τεστ ΖΧ1
                        </a>
                        <a class="nav-link text-dark" href="test-request.php">
                            <i class="bi bi-file-medical me-2"></i>Νέα Αίτηση για Τεστ ΖΧ1
                        </a>
                        
                    </nav>
                </div>
            </div>
        </div>




                <!-- etoimo main -->
        <div class="col-lg-9 col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h1 class="h3 mb-2">Πρόσφατα Τεστ ΖΧ1</h1>
                    <p class="text-muted mb-0">Ιστορικό τελευταίων 15 Τεστ που έγιναν </p>
                </div>
                <div class="d-flex gap-3">
                    <div class="card-stat bg-danger text-white">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span><?= $positive ?> Θετικό</span>
                    </div>
                    <div class="card-stat bg-success text-white">
                        <i class="bi bi-check-circle"></i>
                        <span><?= $negative ?> Αρνητικό</span>
                    </div>
                </div>
            </div>

            <!-- karantina eidopoihsh -->
            <?php if($is_in_quarantine): ?>
                <div class="alert alert-danger mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                        <div>
                            <h5 class="alert-heading">Βρίσκεστε σε καραντίνα!</h5>
                            <p class="mb-1">
                                Τοποθεσία: <?= htmlspecialchars($quarantine['τοποθεσία']) ?><br>
                            </p>
                            
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- apanthsh giatrou -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-chat-square-text me-2"></i>Απαντήσεις Γιατρών</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Ημερομηνία Αίτησης</th>
                                    <th>Κατάσταση</th>
                                    <th>Απάντηση</th>
                                    <th class="pe-4">Ημερομηνία Απάντησης</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($test_requests->num_rows > 0): ?>
                                    <?php while($req = $test_requests->fetch_assoc()): ?>
                                        <?php if(!empty($req['response'])): ?>
                                        <tr class="border-top">
                                            <td class="ps-4"><?= htmlspecialchars($req['created_at']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $req['status'] == 'approved' ? 'success' : 
                                                    ($req['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                                    <?= htmlspecialchars($req['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($req['response']) ?></td>
                                            <td class="pe-4"><?= htmlspecialchars($req['updated_at']) ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="bi bi-chat-left-text fs-1 text-muted"></i>
                                            <p class="mt-2 mb-0">Δεν υπάρχουν απαντήσεις από γιατρούς</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- apotelesmata test -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Αποτελέσματα</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Ημερομηνία</th>
                                    <th>Τύπος Τεστ</th>
                                    <th>Αποτέλεσμα</th>
                                    <th>Σχόλια</th>
                                    <th class="pe-4">Λήψη</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($tests->num_rows > 0): ?>
                                    <?php while($test = $tests->fetch_assoc()): ?>
                                    <tr class="border-top">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-calendar-check me-2 text-primary"></i>
                                                <?= htmlspecialchars($test['ημερομηνία']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                ZeroX-Virus-1 Test
                                            </span>
                                        </td>
                                        <td>
                                            <div class="status-indicator">
                                                <span class="dot bg-<?= $test['τύπος'] === 'Θετικό' ? 'danger' : 'success' ?>"></span>
                                                <?= htmlspecialchars($test['τύπος']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($test['σχόλια']) ?>
                                        </td>
                                        <td class="pe-4">
                                            <form action="download_test.php" method="POST">
                                                <input type="hidden" name="test_data" value="<?= htmlspecialchars(json_encode($test)) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-download"></i> TXT
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="bi bi-clipboard-x fs-1 text-muted"></i>
                                            <p class="mt-2 mb-0">Δεν βρέθηκαν αποτελέσματα κάποιου Τεστ</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- quit actions etoima -->
            <div class="row g-4 mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-hourglass-split me-2"></i>Κατάσταση Αιτήσεων</h5>
                            <div class="mt-3">
                                <?php 
                                $test_requests->data_seek(0); 
                                if($test_requests->num_rows > 0): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php while($req = $test_requests->fetch_assoc()): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <?= htmlspecialchars($req['created_at']) ?>
                                                </span>
                                                <span class="badge bg-<?= 
                                                    $req['status'] == 'approved' ? 'success' : 
                                                    ($req['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                                    <?= htmlspecialchars($req['status']) ?>
                                                </span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Δεν υπάρχουν πρόσφατες αιτήσεις!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-graph-up me-2"></i>Στατιστικά</h5>
                            <div class="mt-3" style="height: 150px">
                                <canvas id="testChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="row g-4 mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-plus-circle me-2"></i>Γρήγορες Ενέργειες</h5>
                            <div class="d-grid gap-2 mt-3">
                                <a href="test-request.php" class="btn btn-outline-primary text-start">
                                    <i class="bi bi-file-medical me-2"></i>Νέα Αίτηση Τεστ ΖΧ1
                                </a>
                                <button class="btn btn-outline-primary text-start" data-bs-toggle="modal" data-bs-target="#historyModal">
                                    <i class="bi bi-printer me-2"></i>Ιστορικό Τεστ ΖΧ1
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                  
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-images me-2"></i>Ιατρικές Εικόνες</h5>
                            <div class="mt-3">
                                <?php
                                // eikones polith
                                $images_stmt = $conn->prepare("SELECT id, title, description, created_at 
                                                            FROM medical_images 
                                                            WHERE amka = ? 
                                                            ORDER BY created_at DESC 
                                                            LIMIT 5");
                                $images_stmt->bind_param("s", $citizen['αμκα']);
                                $images_stmt->execute();
                                $medical_images = $images_stmt->get_result();
                                
                                if($medical_images->num_rows > 0): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php while($image = $medical_images->fetch_assoc()): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($image['title']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($image['description']) ?></small>
                                                </div>
                                                <a href="view-image.php?id=<?= $image['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Προβολή
                                                </a>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Δεν υπάρχουν διαθέσιμες ιατρικές εικόνες!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>




            
        
    </div>
</div>






    


<!-- etoimo grafhma -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">
                    <i class="bi bi-clock-history me-2"></i>Ιστορικό των Τεστ 
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Έχετε πραγματοποιήσει <strong><?= $total_tests ?></strong> Τεστ συνολικά.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Α/Α</th>
                                <th>Ημερομηνία</th>
                                <th>Αποτέλεσμα</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($test_dates as $index => $date): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($date) ?></td>
                                <td>
                                    <?php 
                                    $all_tests->data_seek(0);
                                    $result = 'Αρνητικό';
                                    while($t = $all_tests->fetch_assoc()) {
                                        if($t['ημερομηνία'] == $date) {
                                            $result = $t['τύπος'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-<?= $result === 'Θετικό' ? 'danger' : 'success' ?>">
                                        <?= htmlspecialchars($result) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Κλείσιμο</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('testChart').getContext('2d');
        
        // Handle case where no tests are found
        const positiveCount = <?= $positive ?: 0 ?>;
        const negativeCount = <?= $negative ?: 0 ?>;
        
        // If both are zero, show a "no data" message
        if (positiveCount === 0 && negativeCount === 0) {
            ctx.font = '16px Arial';
            ctx.fillStyle = '#666';
            ctx.textAlign = 'center';
            ctx.fillText('Δεν υπάρχουν δεδομένα', ctx.canvas.width/2, ctx.canvas.height/2);
            return;
        }

        const testChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Θετικό', 'Αρνητικό'],
                datasets: [{
                    data: [positiveCount, negativeCount],
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(25, 135, 84, 0.8)'
                    ],
                    borderColor: [
                        'rgba(220, 53, 69, 1)',
                        'rgba(25, 135, 84, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>



