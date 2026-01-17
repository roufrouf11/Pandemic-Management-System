<?php
require __DIR__ . '/../includes/auth.php';
checkRole('civil_protection');
include __DIR__ . '/../includes/header.php';

// pairnw tis karantines p einai energes
$quarantines = $conn->query("SELECT * FROM καραντινα ORDER BY διάρκεια DESC");
$quarantine_locations = $conn->query("
    SELECT τοποθεσία, COUNT(*) as count 
    FROM καραντινα 
    GROUP BY τοποθεσία
    ORDER BY count DESC
    LIMIT 10
");

// pairnw tous epidiomolous report
$reports = $conn->query("
    SELECT r.*, e.όνομα, e.επώνυμο, t.αμκα, p.όνομα AS πολίτης_όνομα, p.επώνυμο AS πολίτης_επώνυμο
    FROM αναφορές_επιδημιολόγων r
    JOIN επιδημιολογος e ON r.id_επιδημιολόγου = e.id_επιδημιολογου
    JOIN αποτελεσματα_τεστ t ON r.id_τεστ = t.id_τεστ
    JOIN πολιτης p ON t.αμκα = p.αμκα
    ORDER BY r.ημερομηνία DESC
    LIMIT 10
");

// pairnw ta sxoleia 
$schools = $conn->query("SELECT id_σχολειου, όνομα FROM σχολειο ORDER BY όνομα");

//gia ton xarth
$map_data = $conn->query("
    SELECT τοποθεσία, COUNT(*) as count, 
           AVG(διάρκεια) as avg_duration,
           MAX(διάρκεια) as max_duration
    FROM καραντινα
    GROUP BY τοποθεσία
");

// ftiaxnw ton xarth ta semeia
$map_points = [];
while($row = $map_data->fetch_assoc()) {
    // mesw deepseek brhka kapoia data gewgrafikou mhkoys k platous gia na anaparastithoun
    $greek_cities = [
        'Αθήνα' => ['lat' => 37.9838, 'lon' => 23.7275],
        'Θεσσαλονίκη' => ['lat' => 40.6401, 'lon' => 22.9444],
        'Πάτρα' => ['lat' => 38.2466, 'lon' => 21.7346],
        'Ηράκλειο' => ['lat' => 35.3387, 'lon' => 25.1442],
        'Λάρισα' => ['lat' => 39.6390, 'lon' => 22.4191],
        'Βόλος' => ['lat' => 39.3622, 'lon' => 22.9422],
        'Ιωάννινα' => ['lat' => 39.6650, 'lon' => 20.8537],
        'Τρίκαλα' => ['lat' => 39.5550, 'lon' => 21.7678],
        'Χανιά' => ['lat' => 35.5138, 'lon' => 24.0184],
        'Κομοτηνή' => ['lat' => 41.1139, 'lon' => 25.4044],
        'Αλεξανδρούπολη' => ['lat' => 40.8457, 'lon' => 25.8736],
        'Δράμα' => ['lat' => 41.1496, 'lon' => 24.1479],
        'Καβάλα' => ['lat' => 40.9396, 'lon' => 24.4069],
        'Σέρρες' => ['lat' => 41.0851, 'lon' => 23.5471],
        'Κιλκίς' => ['lat' => 40.9932, 'lon' => 22.8737],
        'Κατερίνη' => ['lat' => 40.2696, 'lon' => 22.5061],
        'Βέροια' => ['lat' => 40.5244, 'lon' => 22.2024],
        'Γιαννιτσά' => ['lat' => 40.7914, 'lon' => 22.4070],
        'Έδεσσα' => ['lat' => 40.8016, 'lon' => 22.0473],
        'Φλώρινα' => ['lat' => 40.7822, 'lon' => 21.4094],
        'Καστοριά' => ['lat' => 40.5197, 'lon' => 21.2687],
        'Γρεβενά' => ['lat' => 40.0833, 'lon' => 21.4167],
        'Κοζάνη' => ['lat' => 40.3000, 'lon' => 21.7833],
        'Νάουσα' => ['lat' => 40.6290, 'lon' => 22.0687],
        'Αγρίνιο' => ['lat' => 38.6218, 'lon' => 21.4073],
        'Μεσολόγγι' => ['lat' => 38.3722, 'lon' => 21.4292],
        'Άρτα' => ['lat' => 39.1606, 'lon' => 20.9855],
        'Πρέβεζα' => ['lat' => 38.9569, 'lon' => 20.7519],
        'Λευκάδα' => ['lat' => 38.8333, 'lon' => 20.7000],
        'Ζάκυνθος' => ['lat' => 37.7833, 'lon' => 20.9000],
        'Κέρκυρα' => ['lat' => 39.6200, 'lon' => 19.9200],
        'Κεφαλονιά' => ['lat' => 38.1833, 'lon' => 20.4833],
        'Ιθάκη' => ['lat' => 38.3667, 'lon' => 20.7167],
        'Σπάρτη' => ['lat' => 37.0731, 'lon' => 22.4297],
        'Ναύπλιο' => ['lat' => 37.5667, 'lon' => 22.8000],
        'Άργος' => ['lat' => 37.6333, 'lon' => 22.7333],
        'Κόρινθος' => ['lat' => 37.9401, 'lon' => 22.9513],
        'Τρίπολη' => ['lat' => 37.5100, 'lon' => 22.3800],
        'Αίγιο' => ['lat' => 38.2500, 'lon' => 22.0833],
        'Πύργος' => ['lat' => 37.6833, 'lon' => 21.4500],
        'Αμαλιάδα' => ['lat' => 37.8000, 'lon' => 21.3500],
        'Γύθειο' => ['lat' => 36.7575, 'lon' => 22.5689],
        'Κως' => ['lat' => 36.8933, 'lon' => 27.2889],
        'Ρόδος' => ['lat' => 36.4349, 'lon' => 28.2176],
        'Κάλυμνος' => ['lat' => 36.9500, 'lon' => 26.9833],
        'Λέρος' => ['lat' => 37.1500, 'lon' => 26.8500],
        'Σάμος' => ['lat' => 37.7544, 'lon' => 26.9768],
        'Χίος' => ['lat' => 38.3687, 'lon' => 26.1350],
        'Μυτιλήνη' => ['lat' => 39.1000, 'lon' => 26.5500],
        'Λήμνος' => ['lat' => 39.8750, 'lon' => 25.0583],
        'Σκύρος' => ['lat' => 38.9000, 'lon' => 24.5667],
        'Άνδρος' => ['lat' => 37.8333, 'lon' => 24.9333],
        'Νάξος' => ['lat' => 37.1000, 'lon' => 25.3667],
        'Πάρος' => ['lat' => 37.0840, 'lon' => 25.1500],
        'Μήλος' => ['lat' => 36.7333, 'lon' => 24.4167],
        'Σαντορίνη' => ['lat' => 36.3932, 'lon' => 25.4615],
        'Σύρος' => ['lat' => 37.4446, 'lon' => 24.9420],
        'Τήνος' => ['lat' => 37.5333, 'lon' => 25.1667],
        'Καρπενήσι' => ['lat' => 38.9167, 'lon' => 21.8167],
        'Λαμία' => ['lat' => 38.9000, 'lon' => 22.4333],
        'Άμφισσα' => ['lat' => 38.5239, 'lon' => 22.4291],
        'Λιβαδειά' => ['lat' => 38.4333, 'lon' => 22.8833],
        'Χαλκίδα' => ['lat' => 38.4639, 'lon' => 23.6022],
        'Θήβα' => ['lat' => 38.3250, 'lon' => 23.3186],
        'Κατερίνη' => ['lat' => 40.2700, 'lon' => 22.5000],
        'Σκύδρα' => ['lat' => 40.7667, 'lon' => 22.1667],
        'Νάξος' => ['lat' => 37.1000, 'lon' => 25.3833],
        'Σέρβια' => ['lat' => 40.1833, 'lon' => 22.0000],
        'Σιάτιστα' => ['lat' => 40.2650, 'lon' => 21.5450],
        'Αγία' => ['lat' => 39.5667, 'lon' => 22.8333],
        'Σούλι' => ['lat' => 39.4167, 'lon' => 20.6333],
        'Καλαμάτα' => ['lat' => 37.0379, 'lon' => 22.1106]
    ];
    
    $location = $row['τοποθεσία'];
    if (isset($greek_cities[$location])) {
        $map_points[] = [
            'name' => $location,
            'lat' => $greek_cities[$location]['lat'],
            'lon' => $greek_cities[$location]['lon'],
            'count' => $row['count'],
            'avg_duration' => round($row['avg_duration'], 1),
            'max_duration' => $row['max_duration']
        ];
    }
}

// kathgories tou epidhmiologou
$categoryLabels = [
    'negative' => 'Αρνητικό',
    'positive' => 'Θετικό',
    'inconclusive' => 'Αόριστο',
    'suspected' => 'Ύποπτο'
];

$severityLabels = [
    'low' => 'Χαμηλός',
    'medium' => 'Μέτριος',
    'high' => 'Υψηλός',
    'critical' => 'Κρίσιμος'
];
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πολιτική Προστασία - <?= htmlspecialchars($_SESSION['region']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.highcharts.com/maps/highmaps.js"></script>
    <script src="https://code.highcharts.com/maps/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/maps/modules/accessibility.js"></script>
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
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 6px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.15);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
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
            transition: all 0.3s ease;
            background-color: white;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 18px 25px;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-header h5 {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-header h5 i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 12px;
            color: white;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::after {
            content: "";
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .quarantine-stat {
            background: linear-gradient(135deg, var(--warning), #f3722c);
        }
        
        .report-stat {
            background: linear-gradient(135deg, var(--danger), #b5179e);
        }
        
        .school-stat {
            background: linear-gradient(135deg, var(--success), #4895ef);
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
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .badge-positive {
            background-color: var(--danger);
        }
        
        .badge-negative {
            background-color: var(--success);
        }
        
        .badge-inconclusive {
            background-color: var(--warning);
        }
        
        .badge-suspected {
            background-color: #7209b7;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .map-container {
            height: 400px;
            background-color: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        
        .map-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(67,97,238,0.1), rgba(67,97,238,0.05));
            z-index: 1;
        }
        
        .map-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 2;
            color: var(--primary);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #3a56d8;
            border-color: #3a56d8;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .section-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary);
            border-radius: 3px;
        }
        
        #map-container {
            height: 400px;
            min-width: 310px;
            max-width: 100%;
            margin: 0 auto;
        }
        
        .loading {
            margin-top: 10em;
            text-align: center;
            color: gray;
        }
        
        .highcharts-point {
            cursor: pointer;
        }
        
        .highcharts-data-label text {
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- SidebaEtoimo sidebar -->
        <div class="col-lg-2 px-0 sidebar">
            <div class="d-flex flex-column h-100 p-3">
                <div class="profile-card">
                    <div class="profile-img">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h6 class="mb-1"><?= htmlspecialchars($_SESSION['user_name']) ?></h6>
                    <span class="small opacity-75">Πολιτική Προστασία</span>
                </div>
                
                <nav class="nav flex-column mt-3">
                    <a class="nav-link active" href="#">
                        <i class="bi bi-speedometer2"></i> Πίνακας Ελέγχου
                    </a>
                    <a class="nav-link" href="#quarantines">
                        <i class="bi bi-house-exclamation"></i> Καραντίνες
                    </a>
                    <a class="nav-link" href="#reports">
                        <i class="bi bi-clipboard2-pulse"></i> Αναφορές
                    </a>
                    <a class="nav-link" href="#notifications">
                        <i class="bi bi-megaphone"></i> Ειδοποιήσεις
                    </a>
                    <a class="nav-link" href="#analytics">
                        <i class="bi bi-graph-up"></i> Αναλυτικά
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- menu-->
        <div class="col-lg-10 p-4">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card quarantine-stat">
                        <i class="bi bi-house-exclamation"></i>
                        <div class="number"><?= $quarantines->num_rows ?></div>
                        <div class="label">Ενεργές Καραντίνες</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card report-stat">
                        <i class="bi bi-clipboard2-pulse"></i>
                        <div class="number"><?= $reports->num_rows ?></div>
                        <div class="label">Νέες Αναφορές</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card school-stat">
                        <i class="bi bi-building"></i>
                        <div class="number"><?= $schools->num_rows ?></div>
                        <div class="label">Σχολεία</div>
                    </div>
                </div>
            </div>
            
            <!-- xarths k chart -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-map me-2"></i>Χάρτης Καραντινών</h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="map-container"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-pie-chart me-2"></i>Κατανομή Καραντινών</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="quarantineDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- karantines -->
            <div class="card mb-4" id="quarantines">
                <div class="card-header">
                    <h5><i class="bi bi-house-exclamation me-2"></i>Ενεργές Καραντίνες</h5>
                </div>
                <div class="card-body p-0">
                    <?php if($quarantines->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Τοποθεσία</th>
                                    <th>Διάρκεια</th>
                                    <th>ID Πολίτη</th>
                                    <th>Ημερομηνία Έναρξης</th>
                                    <th class="pe-4">Κατάσταση</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($q = $quarantines->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <i class="bi bi-geo-alt me-2 text-primary"></i>
                                        <span class="fw-medium"><?= htmlspecialchars($q['τοποθεσία']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($q['διάρκεια']) ?> ημέρες
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($q['id_πολιτη']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($q['ημερομηνία_έναρξης'])) ?></td>
                                    <td class="pe-4">
                                        <span class="badge bg-<?= $q['διάρκεια'] > 7 ? 'danger' : 'warning' ?>">
                                            <?= $q['διάρκεια'] > 7 ? 'Εκτεταμένη' : 'Ενεργή' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <h5>Δεν υπάρχουν ενεργές καραντίνες</h5>
                        <p class="text-muted">Όλα τα δεδομένα είναι ενημερωμένα</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
           
            <div class="card mb-4" id="reports">
                <div class="card-header">
                    <h5><i class="bi bi-clipboard2-pulse me-2"></i>Πρόσφατες Αναφορές Επιδημιολόγων</h5>
                </div>
                <div class="card-body p-0">
                    <?php if($reports->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Πολίτης</th>
                                    <th>Επιδημιολόγος</th>
                                    <th>Κατηγορία</th>
                                    <th>Σοβαρότητα</th>
                                    <th class="pe-4">Ημερομηνία</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($report = $reports->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-medium"><?= htmlspecialchars($report['πολίτης_όνομα'] . ' ' . $report['πολίτης_επώνυμο']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($report['αμκα']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($report['όνομα'] . ' ' . $report['επώνυμο']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $report['κατηγορία'] ?>">
                                            <?= htmlspecialchars($categoryLabels[$report['κατηγορία']] ?? $report['κατηγορία']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $report['βαθμός_σοβαρότητας'] == 'high' ? 'danger' : 
                                            ($report['βαθμός_σοβαρότητας'] == 'medium' ? 'warning' : 'success') ?>">
                                            <?= htmlspecialchars($severityLabels[$report['βαθμός_σοβαρότητας']] ?? $report['βαθμός_σοβαρότητας']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($q['ημερομηνία_έναρξης'] ?? 'now')) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-clipboard-x"></i>
                        <h5>Δεν υπάρχουν αναφορές</h5>
                        <p class="text-muted">Καμία νέα αναφορά δεν έχει υποβληθεί</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            


            
            <div class="card" id="notifications">
                <div class="card-header">
                    <h5><i class="bi bi-megaphone me-2"></i>Νέα Σχολική Ειδοποίηση</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="send-school-notification.php" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Σχολείο <span class="text-danger">*</span></label>
                                <select class="form-select" name="school_id" required>
                                    <option value="" selected disabled>-- Επιλέξτε Σχολείο --</option>
                                    <?php 
                                    $schools->data_seek(0); // Reset pointer
                                    while($school = $schools->fetch_assoc()): ?>
                                        <option value="<?= $school['id_σχολειου'] ?>">
                                            <?= htmlspecialchars($school['όνομα']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Τύπος Οδηγιών <span class="text-danger">*</span></label>
                                <select class="form-select" name="notification_type" required>
                                    <option value="hygiene">Υγειονομικές Οδηγίες</option>
                                    <option value="safety">Πρωτόκολλα Ασφαλείας</option>
                                    <option value="closure">Εκκρεμείς Κλεισίματα</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Μήνυμα <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="message" rows="4" required placeholder="Συμπληρώστε τις οδηγίες..."></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Βίντεο Οδηγιών (προαιρετικό)</label>
                                <input type="file" class="form-control" name="video" accept="video/mp4,video/webm,video/ogg">
                                <small class="text-muted">Επιτρέπονται μόνο αρχεία MP4, WebM ή OGG (μέγιστο μέγεθος 20MB)</small>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <div class="d-flex justify-content-end gap-3">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-2"></i>Καθαρισμός
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i>Αποστολή
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
// script gia xarth
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for charts
    const locationData = {
        labels: [<?php 
            $locations = [];
            $quarantine_locations->data_seek(0);
            while($loc = $quarantine_locations->fetch_assoc()) {
                $locations[] = "'" . htmlspecialchars($loc['τοποθεσία']) . "'";
            }
            echo implode(',', $locations);
        ?>],
        datasets: [{
            label: 'Αριθμός Καραντινών',
            data: [<?php 
                $quarantine_locations->data_seek(0);
                $counts = [];
                while($loc = $quarantine_locations->fetch_assoc()) {
                    $counts[] = $loc['count'];
                }
                echo implode(',', $counts);
            ?>],
            backgroundColor: [
                '#4361ee', '#3f37c9', '#4cc9f0', '#4895ef', 
                '#f72585', '#b5179e', '#7209b7', '#560bad',
                '#480ca8', '#3a0ca3'
            ],
            borderWidth: 0
        }]
    };

    
    const distributionCtx = document.getElementById('quarantineDistributionChart').getContext('2d');
    new Chart(distributionCtx, {
        type: 'pie',
        data: locationData,
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
    
    // fortwnw ton carth ths elladas
    (async () => {
        // dieuthinsi link h parakatw online diathesimi
        const topology = await fetch(
            'https://code.highcharts.com/mapdata/countries/gr/gr-all.topo.json'
        ).then(response => response.json());
        
        
        const mapPoints = <?= json_encode($map_points) ?>;
        
        
        Highcharts.mapChart('map-container', {
            chart: {
                map: topology,
                backgroundColor: 'transparent'
            },
            
            title: {
                text: 'Καραντίνες ανά Περιοχή',
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
                pointFormat: '<b>Καραντίνες:</b> {point.count}<br/>' +
                            '<b>Μέση Διάρκεια:</b> {point.avg_duration} ημέρες<br/>' +
                            '<b>Μέγιστη Διάρκεια:</b> {point.max_duration} ημέρες'
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
                // Quarantine locations
                type: 'mappoint',
                name: 'Καραντίνες',
                color: Highcharts.getOptions().colors[1],
                data: mapPoints.map(point => ({
                    name: point.name,
                    lat: point.lat,
                    lon: point.lon,
                    count: point.count,
                    avg_duration: point.avg_duration,
                    max_duration: point.max_duration
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
                    radius: 5,
                    lineColor: '#FFFFFF',
                    lineWidth: 2,
                    fillColor: '#f72585',
                    states: {
                        hover: {
                            radius: 7,
                            fillColor: '#b5179e'
                        }
                    }
                }
            }],
            
            credits: {
                enabled: false
            }
        });
    })();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>