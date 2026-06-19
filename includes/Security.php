<?php
/**
 * ISP Billing System - Security Class
 * নিরাপত্তা বৈশিষ্ট্য: CSRF, ইনপুট ভ্যালিডেশন, এনক্রিপশন
 */

class Security {
    
    /**
     * CSRF টোকেন জেনারেট করুন
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * CSRF টোকেন যাচাই করুন
     */
    public static function verifyCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }

    /**
     * CSRF টোকেন HTML ফিল্ড হিসেবে রিটার্ন করুন
     */
    public static function getCSRFField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * ইনপুট ডেটা স্যানিটাইজ করুন
     */
    public static function sanitize($input, $type = 'string') {
        if ($type == 'email') {
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        } elseif ($type == 'url') {
            return filter_var($input, FILTER_SANITIZE_URL);
        } elseif ($type == 'int') {
            return intval($input);
        } elseif ($type == 'float') {
            return floatval($input);
        } else {
            // সাধারণ স্ট্রিং
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * ইমেইল ভ্যালিডেট করুন
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * ফোন নম্বর ভ্যালিডেট করুন
     */
    public static function isValidPhone($phone) {
        // বাংলাদেশী ফোন নম্বর ফরম্যাট: 01XXXXXXXXX
        $pattern = '/^01[3-9]\d{8}$/';
        return preg_match($pattern, $phone);
    }

    /**
     * মোবাইল নম্বর ক্লিন করুন
     */
    public static function cleanPhoneNumber($phone) {
        // শুধুমাত্র সংখ্যা রাখুন
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // যদি +880 থেকে শুরু হয় তাহলে 0 দিয়ে শুরু করুন
        if (substr($phone, 0, 3) == '880') {
            $phone = '0' . substr($phone, 3);
        }
        
        return $phone;
    }

    /**
     * পাসওয়ার্ড হ্যাশ করুন
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * পাসওয়ার্ড যাচাই করুন
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * পাসওয়ার্ড শক্তিশালী কিনা চেক করুন
     */
    public static function isStrongPassword($password) {
        $length = strlen($password) >= 8;
        $uppercase = preg_match('/[A-Z]/', $password);
        $lowercase = preg_match('/[a-z]/', $password);
        $number = preg_match('/[0-9]/', $password);
        $special = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);
        
        return $length && $uppercase && $lowercase && $number && $special;
    }

    /**
     * ডেটা এনক্রিপ্ট করুন
     */
    public static function encrypt($data) {
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * ডেটা ডিক্রিপ্ট করুন
     */
    public static function decrypt($data) {
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * ক্লায়েন্ট IP সংগ্রহ করুন
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ?: 'UNKNOWN';
    }

    /**
     * এক্সেস লগ করুন
     */
    public static function logActivity($user_id, $action, $details = '', $customer_id = null) {
        global $db;
        
        try {
            $ip = self::getClientIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $db->prepare("INSERT INTO activity_logs (user_id, customer_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
            $db->bind($user_id, 'i');
            $db->bind($customer_id, 'i');
            $db->bind($action, 's');
            $db->bind($details, 's');
            $db->bind($ip, 's');
            $db->bind($user_agent, 's');
            $db->execute();

            return true;
        } catch (Exception $e) {
            // লগিং ব্যর্থ হলেও সিস্টেম চলবে
            return false;
        }
    }

    /**
     * ফাইল আপলোড ভ্যালিডেট করুন
     */
    public static function validateFileUpload($file, $allowed_extensions = null) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['status' => false, 'message' => 'আপলোড ব্যর্থ'];
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['status' => false, 'message' => 'ফাইল সাইজ অনেক বড়'];
        }

        if (!$allowed_extensions) {
            $allowed_extensions = ALLOWED_EXTENSIONS;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            return ['status' => false, 'message' => 'ফাইল টাইপ অনুমোদিত নয়'];
        }

        return ['status' => true];
    }

    /**
     * নিরাপদ ফাইল নাম জেনারেট করুন
     */
    public static function generateSafeFileName($original_name) {
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $name = pathinfo($original_name, PATHINFO_FILENAME);
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        return $name . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    }

    /**
     * SQL ইনজেকশন প্রতিরোধ
     */
    public static function escapeSQLString($string) {
        global $db;
        if (method_exists($db, 'connection')) {
            return $db->connection->real_escape_string($string);
        }
        return addslashes($string);
    }

    /**
     * XSS প্রতিরোধ
     */
    public static function escapeHTML($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * JSON এ এনকোড করার সময় নিরাপদ করুন
     */
    public static function safeJsonEncode($data) {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * রেট লিমিটিং চেক করুন
     */
    public static function checkRateLimit($key, $limit = 10, $window = 60) {
        $cache_key = 'rate_limit_' . $key;
        
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = ['count' => 0, 'reset_time' => time() + $window];
        }

        if (time() > $_SESSION[$cache_key]['reset_time']) {
            $_SESSION[$cache_key] = ['count' => 0, 'reset_time' => time() + $window];
        }

        $_SESSION[$cache_key]['count']++;

        if ($_SESSION[$cache_key]['count'] > $limit) {
            return false;
        }

        return true;
    }

    /**
     * সিকিউর হেডার সেট করুন
     */
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Content-Security-Policy: default-src \'self\'');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * HTTPS রিডাইরেক্ট
     */
    public static function forceHTTPS() {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $url, true, 301);
            exit;
        }
    }
}

// নিরাপত্তা হেডার সেট করুন
Security::setSecurityHeaders();

?>
