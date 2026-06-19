<?php
/**
 * ISP Billing System - Authentication Class
 * নিরাপদ অথেন্টিকেশন এবং সেশন ম্যানেজমেন্ট
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Auth {
    private $db;
    private $security;
    private $error;

    public function __construct() {
        global $db;
        $this->db = $db;
        $this->security = new Security();
    }

    /**
     * ব্যবহারকারী লগইন করুন
     * @param string $username ব্যবহারকারী নাম
     * @param string $password পাসওয়ার্ড
     */
    public function login($username, $password) {
        // ইনপুট ভ্যালিডেশন
        if (empty($username) || empty($password)) {
            $this->error = "ব্যবহারকারী নাম এবং পাসওয়ার্ড প্রয়োজন";
            return false;
        }

        // ব্রুট ফোর্স সুরক্ষা
        if (!$this->checkLoginAttempts($username)) {
            $this->error = "অনেক ব্যর্থ প্রচেষ্টার পরে অ্যাকাউন্ট লক করা হয়েছে। ১৫ মিনিট পরে চেষ্টা করুন।";
            return false;
        }

        try {
            // ডাটাবেস থেকে ব্যবহারকারী খুঁজুন
            $this->db->prepare("SELECT id, password, role_id, status FROM users WHERE username = ?");
            $this->db->bind($username, 's');
            $this->db->execute();
            $result = $this->db->getResult();

            if ($result->num_rows == 0) {
                $this->recordLoginAttempt($username, false);
                $this->error = "ব্যবহারকারী নাম বা পাসওয়ার্ড ভুল";
                return false;
            }

            $user = $result->fetch_assoc();

            // স্ট্যাটাস চেক করুন
            if ($user['status'] != 'active') {
                $this->error = "আপনার অ্যাকাউন্ট নিষ্ক্রিয় বা স্থগিত করা হয়েছে";
                return false;
            }

            // পাসওয়ার্ড যাচাই করুন
            if (!password_verify($password, $user['password'])) {
                $this->recordLoginAttempt($username, false);
                $this->error = "ব্যবহারকারী নাম বা পাসওয়ার্ড ভুল";
                return false;
            }

            // সফল লগইন
            $this->recordLoginAttempt($username, true);
            $this->createSession($user['id'], $user['role_id']);
            return true;

        } catch (Exception $e) {
            $this->error = "লগইন প্রক্রিয়ায় ত্রুটি: " . $e->getMessage();
            return false;
        }
    }

    /**
     * কাস্টমার লগইন
     * @param string $customer_id কাস্টমার ID
     * @param string $password পাসওয়ার্ড
     */
    public function customerLogin($customer_id, $password) {
        if (empty($customer_id) || empty($password)) {
            $this->error = "কাস্টমার ID এবং পাসওয়ার্ড প্রয়োজন";
            return false;
        }

        try {
            $this->db->prepare("SELECT id, password, status FROM customers WHERE customer_id = ?");
            $this->db->bind($customer_id, 's');
            $this->db->execute();
            $result = $this->db->getResult();

            if ($result->num_rows == 0) {
                $this->error = "কাস্টমার ID বা পাসওয়ার্ড ভুল";
                return false;
            }

            $customer = $result->fetch_assoc();

            if ($customer['status'] != 'active') {
                $this->error = "আপনার সেবা নিষ্ক্রিয় বা স্থগিত করা হয়েছে";
                return false;
            }

            if (!password_verify($password, $customer['password'])) {
                $this->error = "কাস্টমার ID বা পাসওয়ার্ড ভুল";
                return false;
            }

            $this->createCustomerSession($customer['id']);
            return true;

        } catch (Exception $e) {
            $this->error = "লগইন ত্রুটি: " . $e->getMessage();
            return false;
        }
    }

    /**
     * সেশন তৈরি করুন
     */
    private function createSession($user_id, $role_id) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role_id'] = $role_id;
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $this->security->getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // ডাটাবেসে আপডেট করুন
        $this->db->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
        $this->db->bind($user_id, 'i');
        $this->db->execute();
    }

    /**
     * কাস্টমার সেশন তৈরি করুন
     */
    private function createCustomerSession($customer_id) {
        $_SESSION['customer_id'] = $customer_id;
        $_SESSION['login_type'] = 'customer';
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $this->security->getClientIP();
    }

    /**
     * লগইন অ্যাটেম্প্ট রেকর্ড করুন
     */
    private function recordLoginAttempt($username, $success = false) {
        try {
            if ($success) {
                $this->db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE username = ?");
            } else {
                $this->db->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE username = ?");
                
                // ৫টির বেশি চেষ্টা হলে লক করুন
                $this->db->prepare("SELECT login_attempts FROM users WHERE username = ?");
                $this->db->bind($username, 's');
                $this->db->execute();
                $result = $this->db->getResult();
                $user = $result->fetch_assoc();

                if ($user['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                    $this->db->prepare("UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE username = ?");
                }
            }
            $this->db->bind($username, 's');
            $this->db->execute();
        } catch (Exception $e) {
            // লগিং ব্যর্থ হলেও লগইন চালিয়ে যান
        }
    }

    /**
     * লগইন অ্যাটেম্প্ট চেক করুন
     */
    private function checkLoginAttempts($username) {
        try {
            $this->db->prepare("SELECT login_attempts, locked_until FROM users WHERE username = ?");
            $this->db->bind($username, 's');
            $this->db->execute();
            $result = $this->db->getResult();

            if ($result->num_rows == 0) {
                return true;
            }

            $user = $result->fetch_assoc();

            // যদি লক থাকে
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return false;
            }

            return true;

        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * সেশন যাচাই করুন
     */
    public function isLoggedIn() {
        if (isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) {
            // সেশন এক্সপায়ার চেক করুন
            if (isset($_SESSION['login_time'])) {
                if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
                    $this->logout();
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * ব্যবহারকারী তথ্য পান
     */
    public function getUser() {
        if (!$this->isLoggedIn() || !isset($_SESSION['user_id'])) {
            return null;
        }

        try {
            $this->db->prepare("SELECT id, username, email, full_name, role_id FROM users WHERE id = ?");
            $this->db->bind($_SESSION['user_id'], 'i');
            $this->db->execute();
            $result = $this->db->getResult();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * কাস্টমার তথ্য পান
     */
    public function getCustomer() {
        if (!$this->isLoggedIn() || !isset($_SESSION['customer_id'])) {
            return null;
        }

        try {
            $this->db->prepare("SELECT id, customer_id, full_name, email, phone, status, balance FROM customers WHERE id = ?");
            $this->db->bind($_SESSION['customer_id'], 'i');
            $this->db->execute();
            $result = $this->db->getResult();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * পারমিশন চেক করুন
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn() || !isset($_SESSION['user_id'])) {
            return false;
        }

        try {
            $this->db->prepare("SELECT permissions FROM roles WHERE id = ?");
            $this->db->bind($_SESSION['role_id'], 'i');
            $this->db->execute();
            $result = $this->db->getResult();
            $role = $result->fetch_assoc();

            if (!$role) {
                return false;
            }

            $permissions = json_decode($role['permissions'], true);
            
            if (in_array('all', $permissions)) {
                return true;
            }

            return in_array($permission, $permissions);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * লগআউট করুন
     */
    public function logout() {
        session_destroy();
        $_SESSION = [];
        setcookie('PHPSESSID', '', time() - 3600, '/');
    }

    /**
     * পাসওয়ার্ড পরিবর্তন করুন
     */
    public function changePassword($user_id, $old_password, $new_password) {
        // পাসওয়ার্ড দৈর্ঘ্য চেক
        if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $this->error = "পাসওয়ার্ড কমপক্ষে " . PASSWORD_MIN_LENGTH . " অক্ষর হতে হবে";
            return false;
        }

        try {
            $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $this->db->bind($user_id, 'i');
            $this->db->execute();
            $result = $this->db->getResult();
            $user = $result->fetch_assoc();

            if (!password_verify($old_password, $user['password'])) {
                $this->error = "পুরানো পাসওয়ার্ড সঠিক নয়";
                return false;
            }

            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $this->db->bind($hashed_password, 's');
            $this->db->bind($user_id, 'i');
            $this->db->execute();

            return true;

        } catch (Exception $e) {
            $this->error = "পাসওয়ার্ড পরিবর্তন ব্যর্থ: " . $e->getMessage();
            return false;
        }
    }

    /**
     * ত্রুটি বার্তা পান
     */
    public function getError() {
        return $this->error;
    }
}

// সেশন শুরু করুন
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
