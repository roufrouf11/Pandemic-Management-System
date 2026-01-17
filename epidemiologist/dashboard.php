<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../includes/auth.php';
checkRole('epidemiologist');
include __DIR__ . '/../includes/header.php';

// Get new tests that haven't been evaluated yet
$stmt = $conn->prepare("SELECT t.id_τεστ, t.αμκα, t.ημερομηνία, t.τύπος, t.σχόλια, 
                       p.όνομα as patient_name, p.επώνυμο as patient_surname,
                       d.όνομα as doctor_name, d.επώνυμο as doctor_surname
                       FROM αποτελεσματα_τεστ t
                       JOIN πολιτης p ON t.αμκα = p.αμκα
                       JOIN γιατρος d ON t.id_γιατρου = d.id_γιατρου
                       WHERE t.κατάσταση = 'pending'
                       ORDER BY t.ημερομηνία DESC");
$stmt->execute();
$tests = $stmt->get_result();


// Get test data for the map - modified query
$test_data = $conn->query("
    SELECT 
        p.lat,
        p.lng,
        COUNT(*) as count,
        SUM(CASE WHEN t.τύπος = 'Θετικό' THEN 1 ELSE 0 END) as positive,
        SUM(CASE WHEN t.τύπος = 'Αρνητικό' THEN 1 ELSE 0 END) as negative
    FROM αποτελεσματα_τεστ t
    JOIN πολιτης p ON t.αμκα = p.αμκα
    WHERE p.lat IS NOT NULL AND p.lng IS NOT NULL
    GROUP BY p.lat, p.lng
");

// Debug: Check if query worked
if (!$test_data) {
    die("Query failed: " . $conn->error);
}

// Debug: Check number of rows
$test_count = $test_data->num_rows;
error_log("Found $test_count test locations in database");

// Prepare map data
$map_points = [];
$total_positive = 0;
$total_negative = 0;

while($row = $test_data->fetch_assoc()) {
    if (!empty($row['lat']) && !empty($row['lng'])) {
        $positive = (int)$row['positive'];
        $negative = (int)$row['negative'];
        $total = $positive + $negative;
        
        $map_points[] = [
            'lat' => (float)$row['lat'],
            'lon' => (float)$row['lng'],
            'count' => $total,
            'positive' => $positive,
            'negative' => $negative,
            'positive_percentage' => $total > 0 ? round(($positive / $total) * 100, 1) : 0
        ];
        
        $total_positive += $positive;
        $total_negative += $negative;
    }
}

// Debug output (remove this in production)
echo "<!-- Map points data: " . print_r($map_points, true) . " -->";

// Handle evaluation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    $test_id = $_POST['test_id'];
    $category = $_POST['category'];
    $severity = $_POST['severity'];
    $comments = $_POST['comments'];
    
    try {
        $conn->begin_transaction();
        
        // Update the test result
        $update_stmt = $conn->prepare("UPDATE αποτελεσματα_τεστ SET 
                                     κατάσταση = 'evaluated',
                                     κατηγορία = ?,
                                     βαθμός_σοβαρότητας = ?,
                                     σχόλια = CONCAT(IFNULL(σχόλια,''), '\n\nΕκτίμηση Επιδημιολόγου: ', ?)
                                     WHERE id_τεστ = ?");
        $update_stmt->bind_param("sssi", $category, $severity, $comments, $test_id);
        $update_stmt->execute();
        
        // Create epidemiologist report
        $report_stmt = $conn->prepare("INSERT INTO αναφορές_επιδημιολόγων 
                                     (id_τεστ, id_επιδημιολόγου, κατηγορία, βαθμός_σοβαρότητας, σχόλια, ημερομηνία)
                                     VALUES (?, ?, ?, ?, ?, NOW())");
        $report_stmt->bind_param("iisss", $test_id, $_SESSION['user_id'], $category, $severity, $comments);
        $report_stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Η αξιολόγηση καταχωρήθηκε επιτυχώς!";
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Σφάλμα: " . $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}


// Handle quarantine submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quarantine'])) {
    $amka = $_POST['citizen_id']; // This should be the AMKA from the form
    $duration = (int)$_POST['duration'];
    
    try {
        // First get the actual id_πολιτη from the πολιτης table
        $stmt = $conn->prepare("SELECT id, διεύθυνση, lat, lng FROM πολιτης WHERE αμκα = ?");
        $stmt->bind_param("s", $amka);
        $stmt->execute();
        $citizen = $stmt->get_result()->fetch_assoc();
        
        if (!$citizen) {
            throw new Exception("Ο πολίτης δεν βρέθηκε");
        }

        // Extract city from address (assuming format: "Street, Number, City, Country")
        $address_parts = array_map('trim', explode(',', $citizen['διεύθυνση']));
        $city = 'Αγνωστη τοποθεσία'; // Default value
        
        if (count($address_parts) >= 2) {
            // Get the second-to-last part (usually the city)
            $city = $address_parts[count($address_parts) - 2];
            
            // Clean up city name
            $city = preg_replace('/\d+/', '', $city); // Remove any numbers
            $city = trim($city);
            
            // Standardize common Greek city names
            $city = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');
            $city = str_replace(
                ['Θεσσαλονικη', 'θεσσαλονικη', 'Thessaloniki', 'thessaloniki'],
                'Θεσσαλονίκη',
                $city
            );
            $city = str_replace(
                ['Αθηνα', 'αθηνα', 'Athens', 'athens'],
                'Αθήνα',
                $city
            );
        }

        // Debug output
        error_log("Citizen ID: " . $citizen['id']);
        error_log("AMKA: " . $amka);
        error_log("Duration: " . $duration);
        error_log("Original Address: " . $citizen['διεύθυνση']);
        error_log("Extracted City: " . $city);
        error_log("Lat: " . $citizen['lat']);
        error_log("Lng: " . $citizen['lng']);

        // Insert into quarantine
        $insert_stmt = $conn->prepare("INSERT INTO καραντινα 
                                    (id_πολιτη, διάρκεια, τοποθεσία, lat, lng)
                                    VALUES (?, ?, ?, ?, ?)");
        
        $insert_stmt->bind_param("iisdd", 
            $citizen['id'],  // Use the actual id from πολιτης table
            $duration,
            $city,  // Use the extracted city instead of full address
            $citizen['lat'],
            $citizen['lng']
        );
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Database error: " . $insert_stmt->error);
        }
        
        $_SESSION['success'] = "Ο πολίτης μπήκε σε καραντίνα επιτυχώς!";
        header("Location: dashboard.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Σφάλμα: " . $e->getMessage();
        error_log("Quarantine error: " . $e->getMessage());
        header("Location: dashboard.php");
        exit();
    }
}




?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πίνακας Επιδημιολόγου - ZX1 Πλατφόρμα</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://code.highcharts.com/maps/highmaps.js"></script>
    <script src="https://code.highcharts.com/maps/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/maps/modules/accessibility.js"></script>
    <style>
        .evaluation-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 700px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .test-positive {
            background-color: #ffe6e6;
        }
        .test-negative {
            background-color: #e6ffe6;
        }
        .map-container {
            height: 400px;
            background-color: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">Καλώς όρισες, <?= htmlspecialchars($_SESSION['user_name']) ?></h1>
            <p class="text-muted">Επιδημιολογική Αξιολόγηση Τεστ</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary">
                <i class="bi bi-person-badge me-1"></i> Επιδημιολόγος
            </span>
        </div>
    </div>

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

    <!-- Map and Stats Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-map me-2"></i>Κατανομή Τεστ ανά Περιοχή
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="map-container"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Ποσοστά Θετικών Τεστ
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="testDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="card-title mb-0">
                <i class="bi bi-clipboard2-pulse me-2"></i>Τεστ προς Αξιολόγηση
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ΑΜΚΑ</th>
                            <th>Ασθενής</th>
                            <th>Γιατρός</th>
                            <th>Ημερομηνία</th>
                            <th>Τύπος</th>
                            <th>Σχόλια</th>
                            <th class="pe-4">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($tests->num_rows > 0): ?>
                            <?php while($test = $tests->fetch_assoc()): ?>
                            <tr class="<?= $test['τύπος'] === 'Θετικό' ? 'test-positive' : 'test-negative' ?>">
                                <td class="ps-4"><?= htmlspecialchars($test['αμκα']) ?></td>
                                <td><?= htmlspecialchars($test['patient_name'].' '.$test['patient_surname']) ?></td>
                                <td>Δρ. <?= htmlspecialchars($test['doctor_name'].' '.$test['doctor_surname']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($test['ημερομηνία'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $test['τύπος'] === 'Θετικό' ? 'danger' : 'success' ?>">
                                        <?= htmlspecialchars($test['τύπος']) ?>
                                    </span>
                                </td>
                                <td><?= nl2br(htmlspecialchars($test['σχόλια'] ?? '-')) ?></td>
                                <td class="pe-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="openEvaluationModal(
                                                <?= $test['id_τεστ'] ?>,
                                                '<?= $test['τύπος'] ?>',
                                                '<?= addslashes($test['patient_name'].' '.$test['patient_surname']) ?>'
                                            )">
                                        <i class="bi bi-clipboard-check"></i> Αξιολόγηση
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="openQuarantineModal('<?= $test['αμκα'] ?>')">
                                        <i class="bi bi-house-exclamation"></i> Καραντίνα
                                    </button>
                                </div>
                            </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                                    <p class="mt-2 mb-0">Δεν υπάρχουν τεστ προς αξιολόγηση</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Evaluation Modal -->
<div id="evaluationModal" class="evaluation-modal">
    <div class="modal-content">
        <span class="close" onclick="closeEvaluationModal()">&times;</span>
        <h4><i class="bi bi-clipboard2-pulse me-2"></i> Αξιολόγηση Τεστ</h4>
        <p id="patientInfo" class="text-muted mb-4"></p>
        
        <form method="POST" id="evaluationForm">
            <input type="hidden" name="test_id" id="modalTestId">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Κατηγορία *</label>
                    <select class="form-select" name="category" required>
                        <option value="" selected disabled>Επιλέξτε...</option>
                        <option value="negative">Αρνητικό</option>
                        <option value="positive">Θετικό</option>
                        <option value="inconclusive">Αόριστο</option>
                        <option value="suspected">Ύποπτο</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Βαθμός Σοβαρότητας *</label>
                    <select class="form-select" name="severity" required>
                        <option value="" selected disabled>Επιλέξτε...</option>
                        <option value="low">Χαμηλός</option>
                        <option value="medium">Μέτριος</option>
                        <option value="high">Υψηλός</option>
                        <option value="critical">Κρίσιμος</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Σχόλια Αξιολόγησης *</label>
                    <textarea class="form-control" name="comments" rows="4" required
                              placeholder="Συμπληρώστε τις παρατηρήσεις σας..."></textarea>
                </div>
                
                <div class="col-12 mt-3">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" onclick="closeEvaluationModal()">
                            <i class="bi bi-x-circle me-1"></i> Ακύρωση
                        </button>
                        <button type="submit" name="submit_evaluation" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Υποβολή
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>



<div id="quarantineModal" class="evaluation-modal">
    <div class="modal-content">
        <span class="close" onclick="closeQuarantineModal()">&times;</span>
        <h4><i class="bi bi-house-exclamation me-2"></i> Καραντίνα Πολίτη</h4>
        
        <form method="POST" id="quarantineForm">
            <input type="hidden" name="citizen_id" id="modalCitizenId">
            
            <div class="row g-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Η τοποθεσία και οι συντεταγμένες θα συμπληρωθούν αυτόματα
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">ΑΜΚΑ Πολίτη *</label>
                    <input type="text" class="form-control" id="displayCitizenId" readonly>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Διάρκεια (ημέρες) *</label>
                    <input type="number" class="form-control" name="duration" min="1" max="30" required>
                </div>
                
                <div class="col-12 mt-3">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" onclick="closeQuarantineModal()">
                            <i class="bi bi-x-circle me-1"></i> Ακύρωση
                        </button>
                        <button type="submit" name="submit_quarantine" class="btn btn-danger">
                            <i class="bi bi-house-exclamation me-1"></i> Καραντίνα
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>




<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Modal functions
function openEvaluationModal(testId, testType, patientName) {
    document.getElementById('modalTestId').value = testId;
    document.getElementById('patientInfo').textContent = 
        `Ασθενής: ${patientName} | Τύπος: ${testType}`;
    document.getElementById('evaluationModal').style.display = 'block';
}

function closeEvaluationModal() {
    document.getElementById('evaluationModal').style.display = 'none';
    document.getElementById('evaluationForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('evaluationModal');
    if (event.target == modal) {
        closeEvaluationModal();
    }
}


document.addEventListener('DOMContentLoaded', function() {
    // Test Distribution Pie Chart - this part can stay the same
    const testData = {
        labels: ['Θετικά', 'Αρνητικά'],
        datasets: [{
            data: [
                <?= array_sum(array_column($map_points, 'positive')) ?>,
                <?= array_sum(array_column($map_points, 'negative')) ?>
            ],
            backgroundColor: ['#dc3545', '#28a745'],
            borderWidth: 0
        }]
    };

    const testCtx = document.getElementById('testDistributionChart').getContext('2d');
    new Chart(testCtx, {
        type: 'pie',
        data: testData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Initialize the map - FIXED VERSION
    (async () => {
        console.log("Map points data:", <?= json_encode($map_points) ?>);
        
        // Check if we have any data
        if (<?= count($map_points) ?> === 0) {
            document.getElementById('map-container').innerHTML = 
                '<div class="alert alert-warning">No test location data available</div>';
            return;
        }

        try {
            // Load Greece map topology
            const topology = await fetch(
                'https://code.highcharts.com/mapdata/countries/gr/gr-all.topo.json'
            ).then(response => response.json());
            
            // Create the map
            Highcharts.mapChart('map-container', {
                chart: {
                    map: topology,
                    backgroundColor: 'transparent'
                },
                
                title: {
                    text: 'Κατανομή Τεστ ανά Περιοχή',
                    style: {
                        color: '#495057',
                        fontWeight: '600'
                    }
                },
                
                subtitle: {
                    text: 'Πηγές: Υπουργείο Υγείας',
                    style: {
                        color: '#6c757d'
                    }
                },
                
                mapNavigation: {
                    enabled: true,
                    buttonOptions: {
                        verticalAlign: 'bottom',
                        theme: {
                            fill: 'white',
                            stroke: '#e0e0e0',
                            'stroke-width': 1,
                            states: {
                                hover: {
                                    fill: '#f8f9fa'
                                },
                                select: {
                                    fill: '#4361ee',
                                    style: {
                                        color: 'white'
                                    }
                                }
                            }
                        }
                    }
                },
                
                colorAxis: {
                    min: 0,
                    minColor: '#e6f7ff',
                    maxColor: '#0066cc'
                },
                
                tooltip: {
                    headerFormat: '<span style="font-size: 14px; font-weight: 600">{point.key}</span><br/>',
                    pointFormat: '<b>Σύνολο Τεστ:</b> {point.count}<br/>' +
                                '<b>Θετικά:</b> {point.positive}<br/>' +
                                '<b>Αρνητικά:</b> {point.negative}<br/>' +
                                '<b>Ποσοστό Θετικών:</b> {point.positive_percentage}%'
                },
                
                series: [{
                    name: 'Επαρχίες',
                    borderColor: '#A0A0A0',
                    nullColor: 'rgba(200, 200, 200, 0.3)',
                    showInLegend: false,
                    enableMouseTracking: false
                }, {
                    name: 'Σύνορα',
                    type: 'mapline',
                    color: '#707070',
                    showInLegend: false,
                    enableMouseTracking: false
                }, {
                    // Test locations - FIXED VERSION
                    type: 'mappoint',
                    name: 'Τεστ',
                    color: Highcharts.getOptions().colors[1],
                    data: <?= json_encode($map_points) ?>.map(point => ({
                        name: `Θετικά: ${point.positive} (${point.positive_percentage}%)`,
                        lat: point.lat,
                        lon: point.lon,
                        count: point.count,
                        positive: point.positive,
                        negative: point.negative,
                        positive_percentage: point.positive_percentage
                    })),
                    dataLabels: {
                        enabled: true,
                        format: '{point.name}',
                        style: {
                            color: '#212529',
                            textOutline: 'none',
                            fontWeight: '500'
                        }
                    },
                    marker: {
                        radius: 5 + Math.log(<?= json_encode(array_column($map_points, 'count')) ?>[0]) * 2,
                        lineColor: '#FFFFFF',
                        lineWidth: 2,
                        fillColor: '#dc3545',
                        states: {
                            hover: {
                                radius: 7 + Math.log(<?= json_encode(array_column($map_points, 'count')) ?>[0]) * 2
                            }
                        }
                    }
                }],
                
                credits: {
                    enabled: false
                }
            });
        } catch (error) {
            console.error("Error loading map:", error);
            document.getElementById('map-container').innerHTML = 
                '<div class="alert alert-danger">Error loading map data</div>';
        }
    })();
});









function openQuarantineModal(citizenId) {
    document.getElementById('modalCitizenId').value = citizenId;
    document.getElementById('displayCitizenId').value = citizenId;
    document.getElementById('quarantineModal').style.display = 'block';
}

function closeQuarantineModal() {
    document.getElementById('quarantineModal').style.display = 'none';
    document.getElementById('quarantineForm').reset();
}

// Update your window.onclick function
window.onclick = function(event) {
    const modal = document.getElementById('evaluationModal') || 
                 document.getElementById('quarantineModal');
    if (event.target == modal) {
        if (modal.id === 'evaluationModal') closeEvaluationModal();
        if (modal.id === 'quarantineModal') closeQuarantineModal();
    }
}







</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>