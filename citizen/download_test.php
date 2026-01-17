<?php
require __DIR__ . '/../includes/auth.php';
checkRole('citizen');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_data'])) {
    $test = json_decode($_POST['test_data'], true);
    
    if ($test && isset($test['αμκα'])) {
        // antistoixo test me polith
        $stmt = $conn->prepare("SELECT αμκα FROM πολιτης WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $citizen = $result->fetch_assoc();
        
        if ($citizen && $citizen['αμκα'] === $test['αμκα']) {
            // apotelesma txt arxeioy
            $content = "Αποτελέσματα Τεστ\n";
            $content .= "========================\n";
            $content .= "ΑΜΚΑ: " . $test['αμκα'] . "\n";
            $content .= "Ημερομηνία: " . $test['ημερομηνία'] . "\n";
            $content .= "Τύπος: ZeroX-Virus-1 Test\n";
            $content .= "Αποτέλεσμα: " . $test['τύπος'] . "\n";
            $content .= "Σχόλια: " . $test['σχόλια'] . "\n";
            $content .= "Ημερομηνία λήψης: " . date('Y-m-d H:i:s') . "\n";
            
            
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="test_result_' . $test['id_τεστ'] . '.txt"');
            header('Content-Length: ' . strlen($content));
            
            
            echo $content;
            exit();
        }
    }
}

// error
header('Location: dashboard.php');
exit();
?>