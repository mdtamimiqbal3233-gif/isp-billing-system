<?php
/**
 * ISP Billing System - Admin Dashboard
 * অ্যাডমিন ড্যাশবোর্ড মূল পৃষ্ঠা
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../config/config.php';

// অথেন্টিকেশন চেক করুন
$auth = new Auth();
if (!$auth->isLoggedIn() || !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// ব্যবহারকারী তথ্য পান
$user = $auth->getUser();

// পারমিশন চেক করুন
if (!$auth->hasPermission('view_dashboard')) {
    header('Location: unauthorized.php');
    exit;
}

// ড্যাশবোর্ড ডেটা সংগ্রহ করুন
try {
    global $db;
    
    // মোট কাস্টমার
    $db->prepare("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $db->execute();
    $result = $db->getResult();
    $active_customers = $result->fetch_assoc()['total'];
    
    // মোট রাজস্ব (এই মাসে)
    $db->prepare("SELECT SUM(amount) as total FROM payments WHERE payment_date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND status = 'success'");
    $db->execute();
    $result = $db->getResult();
    $monthly_revenue = $result->fetch_assoc()['total'] ?? 0;
    
    // বকেয়া বিল
    $db->prepare("SELECT SUM(total_due - paid_amount) as total FROM bills WHERE status IN ('pending', 'overdue', 'partial')");
    $db->execute();
    $result = $db->getResult();
    $pending_bills = $result->fetch_assoc()['total'] ?? 0;
    
    // সক্রিয় রাউটার
    $db->prepare("SELECT COUNT(*) as total FROM routers WHERE status = 'online'");
    $db->execute();
    $result = $db->getResult();
    $online_routers = $result->fetch_assoc()['total'];
    
} catch (Exception $e) {
    $active_customers = 0;
    $monthly_revenue = 0;
    $pending_bills = 0;
    $online_routers = 0;
}

?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ড্যাশবোর্ড - ISP বিলিং সিস্টেম</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
            color: white;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
            overflow-y: auto;
        }
        
        .sidebar .logo {
            text-align: center;
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar .logo h3 {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }
        
        .sidebar .nav-item {
            margin: 5px 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .topbar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .topbar h1 {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-menu img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .user-menu .dropdown-menu {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 15px;
        }
        
        .stat-icon.blue {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .stat-icon.green {
            background: #e8f5e9;
            color: #4caf50;
        }
        
        .stat-icon.orange {
            background: #fff3e0;
            color: #ff9800;
        }
        
        .stat-icon.red {
            background: #ffebee;
            color: #f44336;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #999;
        }
        
        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .chart-card h5 {
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .recent-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .recent-table table {
            margin-bottom: 0;
        }
        
        .recent-table thead {
            background: #f5f7fa;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .recent-table th {
            font-weight: 600;
            color: #333;
            padding: 15px;
            text-align: left;
        }
        
        .recent-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .badge-custom {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .topbar {
                flex-direction: column;
                gap: 15px;
            }
            
            .topbar h1 {
                font-size: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- সাইডবার -->
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-wifi" style="font-size: 30px; margin-bottom: 10px;"></i>
            <h3>ISP System</h3>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-chart-line"></i> ড্যাশবোর্ড
            </a>
            <a class="nav-link" href="customers.php">
                <i class="fas fa-users"></i> কাস্টমার
            </a>
            <a class="nav-link" href="packages.php">
                <i class="fas fa-box"></i> প্যাকেজ
            </a>
            <a class="nav-link" href="billing.php">
                <i class="fas fa-file-invoice"></i> বিলিং
            </a>
            <a class="nav-link" href="payments.php">
                <i class="fas fa-credit-card"></i> পেমেন্ট
            </a>
            <a class="nav-link" href="routers.php">
                <i class="fas fa-router"></i> রাউটার
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar"></i> রিপোর্ট
            </a>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cog"></i> সেটিংস
            </a>
            
            <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 20px 0;">
            
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> লগআউট
            </a>
        </nav>
    </div>
    
    <!-- মূল কন্টেন্ট -->
    <div class="main-content">
        <!-- টপবার -->
        <div class="topbar">
            <h1><i class="fas fa-chart-line"></i> ড্যাশবোর্ড</h1>
            <div class="topbar-right">
                <div class="user-menu">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['full_name']); ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">প্রোফাইল</a></li>
                            <li><a class="dropdown-item" href="change_password.php">পাসওয়ার্ড পরিবর্তন</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">লগআউট</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- স্ট্যাট কার্ড -->
        <div class="row">
            <!-- সক্রিয় কাস্টমার -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $active_customers; ?></div>
                    <div class="stat-label">সক্রিয় কাস্টমার</div>
                </div>
            </div>
            
            <!-- মাসিক রাজস্ব -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-value">৳<?php echo number_format($monthly_revenue, 2); ?></div>
                    <div class="stat-label">এই মাসের রাজস্ব</div>
                </div>
            </div>
            
            <!-- বকেয়া বিল -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-value">৳<?php echo number_format($pending_bills, 2); ?></div>
                    <div class="stat-label">বকেয়া বিল</div>
                </div>
            </div>
            
            <!-- সক্রিয় রাউটার -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-router"></i>
                    </div>
                    <div class="stat-value"><?php echo $online_routers; ?></div>
                    <div class="stat-label">সক্রিয় রাউটার</div>
                </div>
            </div>
        </div>
        
        <!-- চার্ট এবং টেবিল -->
        <div class="row">
            <!-- রাজস্ব চার্ট -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5>গত ৬ মাসের রাজস্ব</h5>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <!-- কাস্টমার স্ট্যাটাস -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5>কাস্টমার স্ট্যাটাস</h5>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- সাম্প্রতিক লেনদেন -->
        <div class="recent-table">
            <h5 style="padding: 20px 20px 10px; margin: 0; color: #333;">সাম্প্রতিক পেমেন্ট</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>কাস্টমার</th>
                        <th>পরিমাণ</th>
                        <th>পদ্ধতি</th>
                        <th>তারিখ</th>
                        <th>অবস্থা</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>রহিম সাহেব</td>
                        <td>৳999</td>
                        <td>bKash</td>
                        <td>19-06-2026</td>
                        <td><span class="badge badge-custom badge-success">সফল</span></td>
                    </tr>
                    <tr>
                        <td>করিম সাহেব</td>
                        <td>৳1,499</td>
                        <td>Nagad</td>
                        <td>18-06-2026</td>
                        <td><span class="badge badge-custom badge-success">সফল</span></td>
                    </tr>
                    <tr>
                        <td>নাসির আহমেদ</td>
                        <td>৳500</td>
                        <td>নগদ</td>
                        <td>17-06-2026</td>
                        <td><span class="badge badge-custom badge-warning">অপেক্ষমাণ</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    <script>
        // রাজস্ব চার্ট
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন'],
                datasets: [{
                    label: 'রাজস্ব (৳)',
                    data: [50000, 60000, 55000, 70000, 65000, 75000],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // কাস্টমার স্ট্যাটাস চার্ট
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['সক্রিয়', 'নিষ্ক্রিয়', 'স্থগিত'],
                datasets: [{
                    data: [85, 10, 5],
                    backgroundColor: ['#4caf50', '#ff9800', '#f44336']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
