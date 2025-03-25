<?php
// Simulan ang session
session_start();

// I-enable ang rate limiting at DDoS protection
include 'ddos_protection.php'; // Create this file with the protection measures

// Isama ang database connection
include '../connect.php';

// I-enable ang error reporting para sa debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Suriin kung naka-login ang user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Generate ng CSRF token para sa seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting para sa form submissions
if (!isset($_SESSION['last_request'])) {
    $_SESSION['last_request'] = time();
} else {
    $current_time = time();
    if (($current_time - $_SESSION['last_request']) < 2) { // 2 seconds between requests
        die("Too many requests. Please wait a moment.");
    }
    $_SESSION['last_request'] = $current_time;
}

$updateMessage = '';

// Kunin ang student ID mula sa URL kung mayroon
if (isset($_GET['stid'])) {
    $id = intval($_GET['stid']);
}

// Kung may form submission para mag-update ng balance
if (isset($_POST['submit'])) {
    // I-validate ang CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // CAPTCHA verification
    if (!verifyCaptcha()) {
        die("CAPTCHA verification failed.");
    }

    // Kunin at i-sanitize ang mga input
    $id = intval($_POST['stid']);
    $nbalance = floatval($_POST['newBalance']);

    // I-update ang balance ng estudyante gamit ang prepared statements
    $updateQuery = "UPDATE students SET balance = ? WHERE stid = ?";
    $stmt = mysqli_prepare($con, $updateQuery);
    mysqli_stmt_bind_param($stmt, 'di', $nbalance, $id);
    $res = mysqli_stmt_execute($stmt);

    if ($res) {
        $updateMessage = 'success';
    } else {
        $updateMessage = 'error: ' . mysqli_error($con);
    }

    // I-insert ang history ng pagbabago
    $sels = htmlspecialchars($_POST['sel'], ENT_QUOTES, 'UTF-8');
    $new = floatval($_POST['cBalance']);
    $date = htmlspecialchars($_POST['date'], ENT_QUOTES, 'UTF-8');

    // Suriin kung umiiral ang estudyante
    $checkStudentQuery = "SELECT stid FROM students WHERE stid = ?";
    $stmt = mysqli_prepare($con, $checkStudentQuery);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) == 0) {
        die("Error: Student ID does not exist.");
    }

    // I-insert ang data sa history table
    $insertQuery = "INSERT INTO history (sel, cbalance, date, studentId) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $insertQuery);
    mysqli_stmt_bind_param($stmt, 'sdsi', $sels, $new, $date, $id);
    $insert = mysqli_stmt_execute($stmt);

    if (!$insert) {
        die("Error inserting into history: " . mysqli_error($con));
    }
}

// Kung may action na isinumite (approve o reject)
if (isset($_POST['action'])) {
    // Input validation at sanitization
    $rname = htmlspecialchars($_POST['rname'], ENT_QUOTES, 'UTF-8');
    $rstid = htmlspecialchars($_POST['rstid'], ENT_QUOTES, 'UTF-8');
    $rparticular = htmlspecialchars($_POST['rparticular'], ENT_QUOTES, 'UTF-8');
    $ramount = floatval($_POST['ramount']);
    $rdate = htmlspecialchars($_POST['rdate'], ENT_QUOTES, 'UTF-8');
    $rtype = "Hma/Aub";

    // Prepared statement para sa record insertion
    $stmt1 = $con->prepare("INSERT INTO record (name, stid, particular, amount, date, type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt1->bind_param("sssdss", $rname, $rstid, $rparticular, $ramount, $rdate, $rtype);

    if ($stmt1->execute()) {
        $insertMessage = "success";
    } else {
        $insertMessage = "error: " . $stmt1->error;
    }

    $stmt1->close();

    $stid = intval($_POST['stid']);
    $status = ($_POST['action'] == 'approve') ? 'approved' : 'rejected';

    // I-update ang status ng approval
    $updateStatusQuery = "UPDATE approval SET status = ? WHERE stid = ?";
    $stmt = mysqli_prepare($con, $updateStatusQuery);
    mysqli_stmt_bind_param($stmt, 'si', $status, $stid);
    $res = mysqli_stmt_execute($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approval</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="shortcut icon" href="../uploads/blogo.png" type="x-icon">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/styles.css">
  <!-- SweetAlert at reCAPTCHA -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<!-- Header at Sidebar code remains the same -->

<div class="main-content">
  <div class="parent">
    <h1 style="text-align: center;">Approval Payment Online</h1>
    <?php
    // Kunin ang mga pending approvals
    $kunin = "SELECT * FROM approval WHERE status = 'pending' LIMIT 50"; // Limit results
    $hasa = mysqli_query($con, $kunin);
    while ($linya = mysqli_fetch_assoc($hasa)) {
        echo '<div class="child" id="child-' . htmlspecialchars($linya['stid']) . '">
            <br>';
        echo '<form method="post" action="" class="approval-form" onsubmit="return verifyAction(this);">';
        echo '<div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY"></div>';
        // Rest of the form
    }
    ?>
  </div>

  <!-- Rest of the HTML remains the same -->
</div>

<script>
// Enhanced security functions
function verifyAction(form) {
    if (grecaptcha.getResponse().length === 0) {
        alert("Please complete the CAPTCHA");
        return false;
    }
    
    return confirm("Are you sure you want to proceed?");
}

// Rate limiting for client-side
let lastClick = 0;
document.querySelectorAll('button[type="submit"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const now = Date.now();
        if (now - lastClick < 2000) { // 2 seconds between clicks
            e.preventDefault();
            alert('Please wait before submitting again');
            return false;
        }
        lastClick = now;
    });
});

// Rest of the JavaScript remains the same
</script>
</body>
</html>