<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/db.php';

// timezone
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Athens');
}

// Initialize settings array if not exists
if (!isset($_SESSION['settings'])) {
    $_SESSION['settings'] = [
        'dark_mode' => false, // light mode
    ];
}

// Handle settings update
if (isset($_POST['update_settings'])) {
    $_SESSION['settings']['dark_mode'] = isset($_POST['dark_mode']);
    // dark mode
    
    // Return JSON response for AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
    
    
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="el" data-bs-theme="<?= $_SESSION['settings']['dark_mode'] ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ZX1 Health Platform</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                <i class="bi bi-heart-pulse fs-3 me-2"></i>
                <span class="h4 mb-0">ZX1 Health Platform</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" 
                    aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <div class="navbar-nav ms-auto align-items-lg-center">
                    <?php if(isset($_SESSION['role'])): ?>
                        <div class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
       data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-circle me-1"></i>
        <?= htmlspecialchars($_SESSION['user_name'] ?? 'Λογαριασμός', ENT_QUOTES, 'UTF-8') ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
        <li>
            <div class="px-3 py-2">
                <h6 class="dropdown-header">Ρυθμίσεις</h6>
                <form id="settingsForm" method="post">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="darkModeSwitch" 
                               name="dark_mode" <?= $_SESSION['settings']['dark_mode'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="darkModeSwitch">
                            <i class="bi bi-moon me-2"></i>Σκούρο Θέμα
                        </label>
                    </div>
                    <input type="hidden" name="update_settings" value="1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 mt-2">
                        <i class="bi bi-save me-1"></i>Αποθήκευση
                    </button>
                </form>
            </div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="/Ergasia3_php/includes/logout.php">
            <i class="bi bi-box-arrow-left me-2"></i>Αποσύνδεση
        </a></li>
    </ul>
</div>
                    <?php else: ?>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-light" href="/citizen/login.php">
                                <i class="bi bi-person me-1"></i>Πολίτης
                            </a>
                            <a class="btn btn-outline-light" href="/doctor/login.php">
                                <i class="bi bi-heart-pulse me-1"></i>Γιατρός
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    
    <main class="flex-grow-1">
        <div class="container py-4">




<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Handle settings form submission
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            type: 'POST',
            url: '',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update theme immediately
                    const darkMode = $('#darkModeSwitch').is(':checked');
                    $('html').attr('data-bs-theme', darkMode ? 'dark' : 'light');
                    
                    // Store in localStorage as fallback
                    localStorage.setItem('darkMode', darkMode);
                    
                    // Show success message
                    alert('Οι ρυθμίσεις αποθηκεύτηκαν επιτυχώς!');
                }
            },
            error: function() {
                alert('Προέκυψε σφάλμα κατά την αποθήκευση των ρυθμίσεων.');
            }
        });
    });
    
    
    const storedDarkMode = localStorage.getItem('darkMode');
    if (storedDarkMode !== null) {
        const darkMode = storedDarkMode === 'true';
        $('html').attr('data-bs-theme', darkMode ? 'dark' : 'light');
        $('#darkModeSwitch').prop('checked', darkMode);
    }
});
</script>