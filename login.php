<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISP বিলিং সিস্টেম - লগইন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .login-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }
        
        .form-group input {
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            padding: 12px 15px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .alert {
            margin-bottom: 20px;
            border-radius: 5px;
            border: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 5px;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            color: white;
            transform: translateY(-2px);
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
        }
        
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-text {
            background: transparent;
            border: none;
            border-bottom: 2px solid #e0e0e0;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-wifi"></i>
                </div>
                <h1>ISP বিলিং সিস্টেম</h1>
                <p>নিরাপদ লগইন পোর্টাল</p>
            </div>
            
            <div class="login-body">
                <!-- ট্যাব বাটন -->
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchTab('admin')">
                        <i class="fas fa-lock"></i> অ্যাডমিন
                    </button>
                    <button class="tab-btn" onclick="switchTab('customer')">
                        <i class="fas fa-user"></i> কাস্টমার
                    </button>
                </div>
                
                <!-- অ্যাডমিন লগইন -->
                <div id="admin-tab" class="tab-content active">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="admin/login_process.php">
                        <div class="form-group">
                            <label for="admin-username">ব্যবহারকারী নাম</label>
                            <input type="text" class="form-control" id="admin-username" name="username" 
                                   placeholder="আপনার ব্যবহারকারী নাম" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin-password">পাসওয়ার্ড</label>
                            <input type="password" class="form-control" id="admin-password" name="password" 
                                   placeholder="আপনার পাসওয়ার্ড" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="remember-admin" name="remember">
                            <label class="form-check-label" for="remember-admin">
                                আমাকে মনে রাখুন
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt"></i> লগইন করুন
                        </button>
                    </form>
                </div>
                
                <!-- কাস্টমার লগইন -->
                <div id="customer-tab" class="tab-content">
                    <?php if (isset($customer_error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($customer_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="customer/login_process.php">
                        <div class="form-group">
                            <label for="customer-id">কাস্টমার ID</label>
                            <input type="text" class="form-control" id="customer-id" name="customer_id" 
                                   placeholder="যেমন: CUST-001" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer-password">পাসওয়ার্ড</label>
                            <input type="password" class="form-control" id="customer-password" name="password" 
                                   placeholder="আপনার পাসওয়ার্ড" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="remember-customer" name="remember">
                            <label class="form-check-label" for="remember-customer">
                                আমাকে মনে রাখুন
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt"></i> লগইন করুন
                        </button>
                        
                        <hr class="my-3">
                        
                        <p class="text-center" style="font-size: 12px; color: #666;">
                            পাসওয়ার্ড ভুলে গেছেন? 
                            <a href="#" style="color: #667eea; text-decoration: none;">এখানে ক্লিক করুন</a>
                        </p>
                    </form>
                </div>
            </div>
            
            <div class="login-footer">
                <p>© 2026 ISP বিলিং সিস্টেম | সকল অধিকার সংরক্ষিত</p>
                <p style="margin-top: 10px;">সংস্করণ: 1.0.0</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchTab(tab) {
            // সমস্ত ট্যাব লুকান
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('active');
            });
            
            // নির্বাচিত ট্যাব দেখান
            document.getElementById(tab + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        // এন্টার কী চাপলে লগইন করুন
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });
        });
    </script>
</body>
</html>
