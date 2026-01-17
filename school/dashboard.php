<?php
require __DIR__ . '/../includes/auth.php';
checkRole('school');
include __DIR__ . '/../includes/header.php';

// dedomena sxoleiou
$school_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM σχολειο WHERE id_σχολειου = ?");
$stmt->bind_param("i", $school_id);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();

// gia katharismo pairnw apo to database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_cleaning'])) {
    $cleaning_id = (int)$_POST['cleaning_id'];
    $completed = isset($_POST['completed']) ? 1 : 0;
    $responsible = $conn->real_escape_string($_POST['responsible']);
    
    $stmt = $conn->prepare("UPDATE καθαρισμός SET Υπεύθυνος_καθαρισμου = ?, ολοκληρωθηκε = ?, ημερομηνία_ολοκλήρωσης = NOW() WHERE ID_καθαρισμου = ? AND ID_Σχολείου = ?");
    $stmt->bind_param("siii", $responsible, $completed, $cleaning_id, $school_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success_message = "Η δήλωση καθαρισμού ενημερώθηκε με επιτυχία!";
    } else {
        $error_message = "Προέκυψε σφάλμα κατά την ενημέρωση.";
    }
}

$stmt = $conn->prepare("
    SELECT σχόλια, created_at, video_path 
    FROM ενημερωση_σχολειων_αεροδρομιων 
    WHERE id_σχολειου = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $school_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>




<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σχολικός Πίνακας - <?= htmlspecialchars($school['όνομα']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e7f5ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --dark: #212529;
            --light: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #495057;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white;
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .profile-card {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--primary);
            font-size: 1.8rem;
            border: 3px solid white;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .table-responsive {
            border-radius: 0 0 12px 12px;
        }

        .table thead th {
            border-bottom: none;
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        .notification {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: all 0.2s;
        }

        .notification:last-child {
            border-bottom: none;
        }

        .notification:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .badge {
            padding: 6px 10px;
            font-weight: 500;
            font-size: 0.75rem;
        }


                
        video {
            max-width: 100%;
            border-radius: 8px;
            background-color: #000;
            margin-top: 10px;
        }

        
        .notification {
            padding: 1.25rem;
        }


    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        
        <div class="col-lg-3 px-0 sidebar">
            <div class="d-flex flex-column h-100 p-3">
                <div class="profile-card">
                    <div class="profile-img">
                        <i class="bi bi-building"></i>
                    </div>
                    <h6 class="mb-1"><?= htmlspecialchars($school['όνομα']) ?></h6>
                    <span class="small opacity-75">Σχολική Μονάδα</span>
                </div>
                
                <nav class="nav flex-column mt-3">
                    <a class="nav-link active text-white" href="#">
                        <i class="bi bi-speedometer2 me-2"></i> Πίνακας Ελέγχου
                    </a>
                    <a class="nav-link text-white" href="#cleanings">
                        <i class="bi bi-brush me-2"></i> Καθαρισμοί
                    </a>
                    <a class="nav-link text-white" href="#notifications">
                        <i class="bi bi-bell me-2"></i> Ειδοποιήσεις
                    </a>
                </nav>
            </div>
        </div>

        <!-- Menu -->
        <div class="col-lg-9 p-4">
            <!-- stoixeia sxoleiou apo bash -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Στοιχεία Σχολείου</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Τοποθεσία:</strong> <?= htmlspecialchars($school['τοποθεσία']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Αριθμός Μαθητών:</strong> <?= htmlspecialchars($school['αριθμός_μαθητών']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card mb-4" id="cleanings">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-brush me-2"></i>Πρόγραμμα Καθαρισμών</h5>
                    <a href="add_cleaning.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Νέος Καθαρισμός
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php
                    $cleanings = $conn->query("
                        SELECT * FROM καθαρισμός 
                        WHERE ID_Σχολείου = {$_SESSION['user_id']}
                        ORDER BY Ημερομηνία DESC
                    ");
                    
                    if($cleanings->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Ημερομηνία</th>
                                    <th>Υπεύθυνος</th>
                                    <th>Κατάσταση</th>
                                    <th class="pe-4">Ενέργειες</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($clean = $cleanings->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4"><?= date('d/m/Y', strtotime($clean['Ημερομηνία'])) ?></td>
                                    <td>
                                        <?php if(!empty($clean['Υπεύθυνος_καθαρισμου'])): ?>
                                            <span class="badge bg-primary">
                                                <?= htmlspecialchars($clean['Υπεύθυνος_καθαρισμου']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Δεν δηλώθηκε</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($clean['ολοκληρωθηκε'] ?? false): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Ολοκληρώθηκε
                                            </span>
                                            <small class="text-muted d-block">
                                                <?= date('d/m/Y H:i', strtotime($clean['ημερομηνία_ολοκλήρωσης'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-clock"></i> Εκκρεμεί
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCleaningModal"
                                                data-id="<?= $clean['ID_καθαρισμου'] ?>"
                                                data-responsible="<?= htmlspecialchars($clean['Υπεύθυνος_καθαρισμου'] ?? '') ?>"
                                                data-completed="<?= $clean['ολοκληρωθηκε'] ?? 0 ?>">
                                            <i class="bi bi-pencil"></i> Επεξεργασία
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state p-4 text-center">
                        <i class="bi bi-check-circle fs-1 text-muted"></i>
                        <h5 class="mt-3">Δεν υπάρχουν προγραμματισμένοι καθαρισμοί</h5>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            
                <div class="card" id="notifications">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Ειδοποιήσεις</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if($notifications->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($note = $notifications->fetch_assoc()): ?>
                                <div class="list-group-item notification">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Πολιτική Προστασία</h6>
                                            <p class="mb-2 text-muted"><?= htmlspecialchars($note['σχόλια']) ?></p>
                                            
                                            <?php if (!empty($note['video_path'])): ?>
                                                <div class="mt-2">
                                                    <?php
                                                    // diadromh video apo thn arxh gt den apaize
                                                    $video_path = $note['video_path'];
                                                    
                                                    // Αφαίρεση του http://localhost/ αν υπάρχει
                                                    if (strpos($video_path, 'http://localhost/') === 0) {
                                                        $video_path = str_replace('http://localhost/', '/', $video_path);
                                                    }
                                                    
                                                    // Αφαίρεση του αρχικού slash αν υπάρχει διπλό
                                                    $video_path = preg_replace('/^\/(?=\/)/', '', $video_path);
                                                    
                                                    // Εξαγωγή ονόματος αρχείου
                                                    $video_filename = basename($video_path);
                                                    
                                                    // Κατασκευή σωστής διαδρομής
                                                    $video_url = '/ERGASIA3_PHP/uploads/videos/' . $video_filename;
                                                    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/ERGASIA3_PHP/uploads/videos/' . $video_filename;
                                                    
                                                    
                                                    $debug_info = [
                                                        'Original Path' => $note['video_path'],
                                                        'Normalized Path' => $video_path,
                                                        'Filename' => $video_filename,
                                                        'Video URL' => $video_url,
                                                        'File Path' => $file_path,
                                                        'File Exists' => file_exists($file_path) ? 'Yes' : 'No'
                                                    ];
                                                    ?>
                                                    
                                                    <?php if (file_exists($file_path)): ?>
                                                        <video width="100%" controls>
                                                            <source src="<?= htmlspecialchars($video_url) ?>" type="video/<?= pathinfo($video_url, PATHINFO_EXTENSION) ?>">
                                                            Το πρόγραμμα περιήγησής σας δεν υποστηρίζει αναπαραγωγή βίντεο.
                                                        </video>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning">
                                                            <p>Το βίντεο δεν βρέθηκε. Παρακαλώ ελέγξτε τη διαθεσιμότητα του αρχείου.</p>
                                                            <details>
                                                                <summary>Debug Information</summary>
                                                                <pre><?= htmlspecialchars(print_r($debug_info, true)) ?></pre>
                                                            </details>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted ms-3"><?= date('d/m/Y H:i', strtotime($note['created_at'])) ?></small>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state p-4 text-center">
                                <i class="bi bi-bell-slash fs-1 text-muted"></i>
                                <h5 class="mt-3">Δεν υπάρχουν νέες ειδοποιήσεις</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

        </div>
    </div>
</div>





<div class="modal fade" id="editCleaningModal" tabindex="-1" aria-labelledby="editCleaningModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCleaningModalLabel">Δήλωση Καθαρισμού</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                    <?php elseif(isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>
                    
                    <input type="hidden" name="cleaning_id" id="modalCleaningId" value="">
                    <input type="hidden" name="mark_cleaning" value="1">
                    
                    <div class="mb-3">
                        <label for="responsible" class="form-label">Υπεύθυνος Καθαρισμού</label>
                        <input type="text" class="form-control" id="responsible" name="responsible" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="completed" name="completed">
                        <label class="form-check-label" for="completed">Ο καθαρισμός ολοκληρώθηκε</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Άκυρο</button>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>

document.getElementById('editCleaningModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var modal = this;
    
    modal.querySelector('#modalCleaningId').value = button.getAttribute('data-id');
    modal.querySelector('#responsible').value = button.getAttribute('data-responsible');
    modal.querySelector('#completed').checked = button.getAttribute('data-completed') === '1';
});
</script>









<?php include __DIR__ . '/../includes/footer.php'; ?>












