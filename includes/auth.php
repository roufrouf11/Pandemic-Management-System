<?php

include 'db.php';

session_start();

function checkRole($requiredRole) {
    if (!isset($_SESSION['role'])) {
        header("Location: ../$requiredRole/login.php");
        exit();
    }
    
    // make the role
    $table = match($_SESSION['role']) {
        'citizen' => 'πολιτης',
        'doctor' => 'γιατρος',
        'epidemiologist' => 'επιδημιολογος',
        'civil_protection' => 'πολιτικη_προστασια',
        'school' => 'σχολειο'
    };
    
    //matching the ids
    $idField = match($_SESSION['role']) {
        'citizen' => 'id',
        'doctor' => 'id_γιατρου',
        'epidemiologist' => 'id_επιδημιολογου',
        'civil_protection' => 'id_πολιτικης',
        'school' => 'id_σχολειου'
    };

    $stmt = $GLOBALS['conn']->prepare("SELECT $idField FROM $table WHERE $idField = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    //pernaw to id sto login page
    if (!$stmt->get_result()->fetch_assoc()) {
        session_destroy();
        header("Location: ../$requiredRole/login.php");
        exit();
    }
}
?>