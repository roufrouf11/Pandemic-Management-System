<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../includes/auth.php';
checkRole('doctor');
include __DIR__ . '/../includes/header.php';

// pairnw ta test m bash id giatrou
$tests_stmt = $conn->prepare("SELECT * FROM αποτελεσματα_τεστ 
                           WHERE id_γιατρου = ? 
                           ORDER BY ημερομηνία DESC 
                           LIMIT 10");
$tests_stmt->bind_param("i", $_SESSION['user_id']);
$tests_stmt->execute();
$tests = $tests_stmt->get_result();


$requests_stmt = $conn->prepare("SELECT 
    a.id, a.αμκα, a.όνομα, a.επίθετο, a.created_at, 
    a.status, a.response, a.σχόλια, p.τηλέφωνο 
    FROM αιτησεις_τεστ a
    JOIN πολιτης p ON a.αμκα = p.αμκα
    WHERE a.id_γιατρου = ?
    ORDER BY 
        CASE WHEN a.status = 'pending' THEN 0 ELSE 1 END,
        a.created_at DESC");
$requests_stmt->bind_param("i", $_SESSION['user_id']);
$requests_stmt->execute();
$all_requests = $requests_stmt->get_result();


$stats_stmt = $conn->prepare("SELECT 
                            SUM(CASE WHEN τύπος = 'Θετικό' THEN 1 ELSE 0 END) as positive,
                            SUM(CASE WHEN τύπος = 'Αρνητικό' THEN 1 ELSE 0 END) as negative
                            FROM αποτελεσματα_τεστ 
                            WHERE id_γιατρου = ?");
$stats_stmt->bind_param("i", $_SESSION['user_id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$positive = $stats['positive'] ?? 0;
$negative = $stats['negative'] ?? 0;




//pairnw eikones me bash id giatrou
$images_stmt = $conn->prepare("SELECT * FROM medical_images WHERE id_γιατρου = ? ORDER BY created_at DESC LIMIT 5");
$images_stmt->bind_param("i", $_SESSION['user_id']);
$images_stmt->execute();
$recent_images = $images_stmt->get_result();





?>

<div class="container-fluid px-xxl-5 px-lg-4 px-md-3">
    <div class="row g-4">
        <!-- idio opws kai ta alla dashboards -->
        <div class="col-lg-3 col-xl-2">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <div class="avatar avatar-xl bg-primary-subtle text-primary mb-3">
                            <i class="bi bi-person-badge fs-3"></i>
                        </div>
                        <h6 class="mb-1">Δρ. <?= htmlspecialchars($_SESSION['user_name']) ?></h6>
                        <span class="text-muted small"><?= htmlspecialchars($_SESSION['specialty']) ?></span>
                    </div>

                    <nav class="nav flex-column gap-2">
                        <a class="nav-link active bg-primary-subtle text-primary rounded-3" href="#">
                            <i class="bi bi-speedometer2 me-2"></i>Πίνακας Ελέγχου
                        </a>
                        <a class="nav-link text-dark" href="#requests">
                            <i class="bi bi-envelope me-2"></i>Αιτήσεις
                        </a>
                        <a href="add-test-result.php" class="nav-link text-dark">
                            <i class="bi bi-plus-circle me-2"></i>Νέο Αποτέλεσμα
                        </a>
                        <a class="nav-link text-dark" href="upload-image.php">
                            <i class="bi bi-upload me-2"></i>Ανέβασμα Εικόνας
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- menu idia logic -->
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

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h1 class="h3 mb-2">Καλώς όρισες, Δρ. <?= htmlspecialchars($_SESSION['user_name']) ?></h1>
                    <p class="text-muted mb-0">Ιστορικό τελευταίων 10 Τεστ</p>
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

            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Πρόσφατα Τεστ</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ΑΜΚΑ Ασθενούς</th>
                                    <th>Ημερομηνία</th>
                                    <th>Τύπος</th>
                                    <th>Αποτέλεσμα</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($tests->num_rows > 0): ?>
                                    <?php while($test = $tests->fetch_assoc()): ?>
                                    <tr class="border-top">
                                        <td class="ps-4"><?= htmlspecialchars($test['αμκα']) ?></td>
                                        <td><?= htmlspecialchars($test['ημερομηνία']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $test['τύπος'] === 'Θετικό' ? 'danger' : 'success' ?>">
                                                <?= htmlspecialchars($test['τύπος']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($test['σχόλια']) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="bi bi-clipboard-x fs-1 text-muted"></i>
                                            <p class="mt-2 mb-0">Δεν βρέθηκαν αποτελέσματα Τεστ</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <div class="card border-0 shadow-sm mb-4" id="requests">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-envelope me-2"></i>Αιτήσεις Τεστ</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ΑΜΚΑ</th>
                                    <th>Ονοματεπώνυμο</th>
                                    <th>Τηλέφωνο</th>
                                    <th>Ημερομηνία</th>
                                    <th>Σχόλια Πολίτη</th> <!-- Νέα στήλη -->
                                    <th>Κατάσταση</th>
                                    <th>Απάντηση</th>
                                    <th class="pe-4">Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($all_requests->num_rows > 0): ?>
                                    <?php while($request = $all_requests->fetch_assoc()): ?>
                                    <tr class="border-top">
                                        <td class="ps-4"><?= htmlspecialchars($request['αμκα']) ?></td>
                                        <td><?= htmlspecialchars($request['όνομα'].' '.$request['επίθετο']) ?></td>
                                        <td><?= htmlspecialchars($request['τηλέφωνο']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($request['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($request['σχόλια'] ?? '-') ?></td>          

                                        <td>
                                            <span class="badge bg-<?= 
                                                $request['status'] == 'approved' ? 'success' : 
                                                ($request['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                                <?= htmlspecialchars($request['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($request['response'] ?? '-') ?></td>
                                        <td class="pe-4">
                                            <?php if($request['status'] == 'pending'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <form action="process-request.php" method="POST" class="me-1">
                                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success" onclick="return confirm('Είστε σίγουρος ότι θέλετε να εγκρίνετε αυτή την αίτηση;')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                <form action="process-request.php" method="POST">
                                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Είστε σίγουρος ότι θέλετε να απορρίψετε αυτή την αίτηση;')">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted small">Ολοκληρώθηκε</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="bi bi-envelope-open fs-1 text-muted"></i>
                                            <p class="mt-2 mb-0">Δεν υπάρχουν αιτήσεις</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>











                        
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-images me-2"></i>Πρόσφατες Ιατρικές Εικόνες</h5>
                        <a href="upload-image.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> Νέα Εικόνα
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($recent_images->num_rows > 0): ?>
                        <div class="row g-3">
                            <?php while($image = $recent_images->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-img-top bg-light" style="height: 150px; overflow: hidden;">
                                        <?php if(strpos($image['file_type'], 'image/') === 0): ?>
                                            
                                                <img src="view-image.php?id=<?= $image['id'] ?>"
                                                class="img-fluid h-100 w-100 object-fit-cover" 
                                                alt="<?= htmlspecialchars($image['title']) ?>">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                <i class="bi bi-file-earmark-medical fs-1 text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($image['title']) ?></h6>
                                        <p class="card-text small text-muted"><?= htmlspecialchars($image['description'] ?? '') ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted"><?= htmlspecialchars($image['created_at']) ?></small>
                                            <a href="view-image.php?id=<?= $image['id'] ?>" 
                                            class="btn btn-sm btn-outline-primary" 
                                            target="_blank">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-images fs-1 text-muted"></i>
                            <p class="mt-2 mb-0">Δεν υπάρχουν ανεβασμένες εικόνες</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>















            <!-- statistika -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-graph-up me-2"></i>Στατιστικά Τεστ</h5>
                    <div class="mt-3" style="height: 250px">
                        <canvas id="testChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- etoimo grafhma -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('testChart').getContext('2d');
        const testChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Θετικά', 'Αρνητικά'],
                datasets: [{
                    label: 'Αποτελέσματα Τεστ',
                    data: [<?= $positive ?>, <?= $negative ?>],
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(25, 135, 84, 0.7)'
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
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>