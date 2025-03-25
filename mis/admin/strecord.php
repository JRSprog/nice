<?php
// Start session
session_start();

// Include database connection
include '../connect.php'; // Ensure this file initializes $con securely

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Information</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link rel="shortcut icon" href="../uploads/blogo.png" type="x-icon">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<header>
    <div class="menu-container">
      <button class="burger-button" onclick="toggleSidebar()">☰</button>
    </div>
    <div class="dropdown">
      <button class="dropdown-button"><i class="fa-solid fa-user"></i></button>
      <div class="dropdown-content">
         <a href="#"><i class="fa-solid fa-user"></i>&nbsp; Profile</a>
         <a href="#"><i class="fa-solid fa-gear"></i>&nbsp; Settings</a>
         <a href="../logout.php?logout=true"><i class="fa-solid fa-right-from-bracket"></i>&nbsp; Logout</a>
      </div>
    </div>
  </header>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="close">
        <span class="close-sidebar" onclick="toggleSidebar()"><i class="fa-solid fa-arrow-left"></i></span>
        <img src="../uploads/blogo.png" alt="Image" class="sidebar-image">
        <p class="sidebar-text">Your text goes here.</p>
    </div>
    
    <div class="sidebar-content">
      <a href="dashboard.php" class="sidebar-item"><i class="fa-solid fa-house"></i>&nbsp; Dashboard</a>
      <a href="user.php" class="sidebar-item"><i class="fa-solid fa-user"></i>&nbsp; User</a>
      <a href="approval.php" class="sidebar-item"><i class="fa-solid fa-credit-card"></i>&nbsp; Online Approval</a>
      <a href="strecord.php" class="sidebar-item"><i class="fa-solid fa-clipboard-list"></i>&nbsp; Student Information</a>
      <a href="payrecord.php" class="sidebar-item"><i class="fa-solid fa-clipboard-list"></i>&nbsp; Payment Record</a>
      <a href="onfees.php" class="sidebar-item"><i class="fa-solid fa-clipboard-list"></i>&nbsp; Ongoing Fees</a>
    </div>
  </div>

  <div class="main-content">
    <div class="strecord">
      <h1>Student Information</h1>
      <div class="search-container1">
        <i class="fa-solid fa-magnifying-glass"></i><br><br>
        <input type="search" id="searchInput" placeholder="Search ID/student here...">
      </div><br><br>  
      
      <table id="dataTable">
        <thead>
          <tr>
            <th>Student ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Middle Name</th>
            <th>Birthday</th>
            <th>Age</th>
            <th>Email</th>
            <th>Address</th>
            <th>Program</th>
            <th>Year level</th>
          </tr>
        </thead>
        <tbody>
        <?php
        // Fetch students data using prepared statements
        $sql = "SELECT * FROM students";
        $stmt = mysqli_prepare($con, $sql);
        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                die("Database query failed: " . mysqli_error($con));
            }

            $index = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $stid = htmlspecialchars($row['stid']);
                echo '<tr id="student-row-'.$index.'" onclick="togglePayment('.$index.')">';
                echo '<td>'.'s'. $stid .'</td>';
                echo '<td>'. htmlspecialchars($row['lname']) .'</td>';
                echo '<td>'. htmlspecialchars($row['fname']) .'</td>';
                echo '<td>'. htmlspecialchars($row['mname']) .'</td>';
                echo '<td>'. htmlspecialchars(date('F j, Y', strtotime($row['bday']))) .'</td>';
                echo '<td>'. htmlspecialchars($row['age']) .'</td>';
                echo '<td>'. htmlspecialchars($row['email']) .'</td>';
                echo '<td>'. htmlspecialchars($row['address']) .'</td>';
                echo '<td>'. htmlspecialchars($row['program']) .'</td>';
                echo '<td>'. htmlspecialchars($row['level']) .'</td>';
                echo '</tr>';

                // Fetch payments for each student using prepared statements
                $payment_sql = "SELECT balance FROM students WHERE stid = ?";
                $stmt2 = mysqli_prepare($con, $payment_sql);
                if ($stmt2) {
                    mysqli_stmt_bind_param($stmt2, "i", $stid);
                    mysqli_stmt_execute($stmt2);
                    $payment_result = mysqli_stmt_get_result($stmt2);
                    $payments = mysqli_fetch_all($payment_result, MYSQLI_ASSOC);
                    mysqli_stmt_close($stmt2);
                } else {
                    die("Error preparing statement: " . mysqli_error($con));
                }

                // Fetch stfees data using prepared statements
                $stfees_sql = "SELECT payname, amount FROM stfees WHERE program = ?";
                $stmt3 = mysqli_prepare($con, $stfees_sql);
                if ($stmt3) {
                    mysqli_stmt_bind_param($stmt3, "s", $row['program']);
                    mysqli_stmt_execute($stmt3);
                    $stfees_result = mysqli_stmt_get_result($stmt3);
                    $stfees = mysqli_fetch_all($stfees_result, MYSQLI_ASSOC);
                    mysqli_stmt_close($stmt3);
                } else {
                    die("Error preparing statement: " . mysqli_error($con));
                }

                // Fetch fees data for the specific program
                $fees_sql = "SELECT paname, amount FROM fees WHERE selct = ?";
                $stmt4 = mysqli_prepare($con, $fees_sql);
                if ($stmt4) {
                    mysqli_stmt_bind_param($stmt4, "s", $row['program']);
                    mysqli_stmt_execute($stmt4);
                    $fees_result = mysqli_stmt_get_result($stmt4);
                    $fees = mysqli_fetch_all($fees_result, MYSQLI_ASSOC);
                    mysqli_stmt_close($stmt4);
                } else {
                    die("Error preparing statement: " . mysqli_error($con));
                }

                $total_amount = 0;
                $stfees_total_amount = 0;
                $fees_total_amount = 0;

                echo '<tr id="payment-'.$index.'" class="payment-details" style="display:none;">
                        <td colspan="10">
                          <form action="" method="POST">
                            <input type="hidden" name="stid" value="'.$stid.'">
                            <input type="hidden" name="csrf_token" value="'.$_SESSION['csrf_token'].'">';

                if ($payments || $stfees || $fees) {
                    echo '<table class="list">
                            <thead>
                              <tr>
                                <th>Payment Name</th>
                                <th>Amount</th>
                              </tr>
                            </thead>
                            <tbody>';

                    // Display payments from payments table (only if amount > 0)
                    foreach ($payments as $payment) {
                        $amount = $payment['balance'];
                        if ($amount > 0) { // Only display if amount is greater than zero
                            $total_amount += $amount;
                            echo '<tr>';
                            echo '<td>Miscellaneous</td>';
                            echo '<td>'. number_format($amount, 0) .'</td>';
                            echo '</tr>';
                        }
                    }

                    // Display stfees data (only if amount > 0)
                    foreach ($stfees as $stfee) {
                        $amount1 = $stfee['amount'];
                        if ($amount1 > 0) { // Only display if amount is greater than zero
                            $stfees_total_amount += $amount1;
                            echo '<tr>';
                            echo '<td>'. htmlspecialchars($stfee['payname']) .'</td>';
                            echo '<td>'. number_format($amount1) .'</td>';
                            echo '</tr>';
                        }
                    }

                    // Display fees data (only if amount > 0)
                    foreach ($fees as $fee) {
                        $amount2 = $fee['amount'];
                        if ($amount2 > 0) { // Only display if amount is greater than zero
                            $fees_total_amount += $amount2;
                            echo '<tr>';
                            echo '<td>'. htmlspecialchars($fee['paname']) .'</td>';
                            echo '<td>'. number_format($amount2) .'</td>';
                            echo '</tr>';
                        }
                    }

                    // Display total amount only if there are valid payments
                    if ($total_amount + $stfees_total_amount + $fees_total_amount > 0) {
                        echo '<tr style="font-weight: bold; background-color: #f2f2f2;">
                                  <td>Total Amount</td>
                                  <td>'. number_format($total_amount + $stfees_total_amount + $fees_total_amount, 0) .'</td>
                              </tr>';
                    }

                    echo '</tbody>
                          </table>';
                }

                echo '</form>
                      </td>
                    </tr>';
                $index++;
            }
            mysqli_stmt_close($stmt);
        } else {
            die("Error preparing statement: " . mysqli_error($con));
        }
        ?>
        </tbody>
      </table>
      <div id="noRecordMessage" style="display: none;">No record found</div>
    </div>
  </div>

  <script>
    function togglePayment(index) {
      var paymentRow = document.getElementById("payment-" + index);
      var currentDisplay = paymentRow.style.display;
      paymentRow.style.display = (currentDisplay === 'none' || currentDisplay === '') ? 'table-row' : 'none';
    }

    document.getElementById('searchInput').addEventListener('keyup', function() {
      var input = this.value.toLowerCase();
      var rows = document.querySelectorAll('#dataTable tbody tr');
      var noRecordMessage = document.getElementById('noRecordMessage');
      var found = false;

      rows.forEach(function(row, index) {
        if (row.id.startsWith("student-row")) {
          var text = row.innerText.toLowerCase();
          var paymentRow = document.getElementById("payment-" + index);
          row.style.display = text.includes(input) ? '' : 'none';
          paymentRow.style.display = 'none';
          if (text.includes(input)) found = true;
        }
      });

      noRecordMessage.style.display = found ? 'none' : 'block';
    }); 
  </script>
  <script src="../js/script.js"></script>
</body>
</html>