<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>ZeroX-Virus-1 System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Rubik', sans-serif;
            background-color: #f4f7fa;
            color: #2c3e50;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(120deg, #1f1c2c, #928DAB);
        }

        .card {
            background-color: white;
            border-radius: 16px;
            padding: 3rem 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 480px;
            text-align: center;
        }

        h1 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        p {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 2rem;
        }

        .role-button {
            display: block;
            width: 100%;
            margin-bottom: 1rem;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            color: white;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .role-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .btn-citizen { background-color: #3498db; }
        .btn-doctor { background-color: #27ae60; }
        .btn-protection { background-color: #e74c3c; }
        .btn-epidemiologist { background-color: #f39c12; }
        .btn-school { background-color: #16a085; }

        .footer {
            text-align: center;
            padding: 1rem;
            background-color: #1f1c2c;
            color: #ccc;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="hero">
        <div class="card">
            <h1>ZeroX-Virus-1</h1>
            <p>Παρακαλώ επιλέξτε τον ρόλο σας:</p>
            <form method="get" action="">
                <button type="submit" name="role" value="citizen" class="role-button btn-citizen">Πολίτης</button>
                <button type="submit" name="role" value="doctor" class="role-button btn-doctor">Γιατρός</button>
                <button type="submit" name="role" value="civil-protection" class="role-button btn-protection">Πολιτική Προστασία</button>
                <button type="submit" name="role" value="epidemiologist" class="role-button btn-epidemiologist">Επιδημιολόγος</button>
                <button type="submit" name="role" value="school" class="role-button btn-school">Σχολείο</button>
            </form>
        </div>
    </div>

    <div class="footer">
        © 2025 ZeroX-Virus-1 System | Όλα τα δικαιώματα διατηρούνται
    </div>

    
    <?php
    if (isset($_GET['role'])) {
        $role = $_GET['role'];
        $allowed_roles = ['citizen', 'doctor', 'civil-protection', 'epidemiologist', 'school'];
        if (in_array($role, $allowed_roles)) {
            header("Location: $role/login.php");
            exit;
        } else {
            echo "<script>alert('Μη έγκυρος ρόλος.');</script>";
        }
    }
    ?>
</body>
</html>
