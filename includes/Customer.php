<?php
/**
 * ISP Billing System - Customer Model
 * কাস্টমার সংক্রান্ত সমস্ত ফাংশন
 */

class Customer {
    private $db;
    private $error;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /**
     * নতুন কাস্টমার যোগ করুন
     */
    public function create($data) {
        // ভ্যালিডেশন
        if (empty($data['full_name']) || empty($data['phone']) || empty($data['area_id'])) {
            $this->error = "বাধ্যতামূলক ক্ষেত্র পূরণ করুন";
            return false;
        }

        try {
            // ইউনিক কাস্টমার ID জেনারেট করুন
            $customer_id = $this->generateCustomerID();
            $password = password_hash($data['password'] ?? 'ISP@2026', PASSWORD_BCRYPT);

            $this->db->prepare("INSERT INTO customers (customer_id, password, full_name, phone, email, nid, address, area_id, package_id, connection_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $status = $data['status'] ?? 'active';
            $this->db->bind($customer_id, 's');
            $this->db->bind($password, 's');
            $this->db->bind($data['full_name'], 's');
            $this->db->bind($data['phone'], 's');
            $this->db->bind($data['email'] ?? '', 's');
            $this->db->bind($data['nid'] ?? '', 's');
            $this->db->bind($data['address'], 's');
            $this->db->bind($data['area_id'], 'i');
            $this->db->bind($data['package_id'] ?? 1, 'i');
            $this->db->bind($data['connection_date'] ?? date('Y-m-d'), 's');
            $this->db->bind($status, 's');
            
            if ($this->db->execute()) {
                return ['status' => true, 'customer_id' => $customer_id, 'message' => 'কাস্টমার সফলভাবে যোগ করা হয়েছে'];
            }
            
            $this->error = "কাস্টমার যোগ করতে ব্যর্থ";
            return false;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * কাস্টমার আপডেট করুন
     */
    public function update($customer_id, $data) {
        try {
            $this->db->prepare("UPDATE customers SET full_name=?, phone=?, email=?, nid=?, address=?, area_id=?, package_id=?, status=? WHERE customer_id=?");
            
            $this->db->bind($data['full_name'], 's');
            $this->db->bind($data['phone'], 's');
            $this->db->bind($data['email'] ?? '', 's');
            $this->db->bind($data['nid'] ?? '', 's');
            $this->db->bind($data['address'], 's');
            $this->db->bind($data['area_id'], 'i');
            $this->db->bind($data['package_id'], 'i');
            $this->db->bind($data['status'], 's');
            $this->db->bind($customer_id, 's');
            
            return $this->db->execute();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * কাস্টমার তালিকা পান
     */
    public function getAll($limit = 50, $offset = 0, $search = '') {
        try {
            $query = "SELECT * FROM customers";
            
            if (!empty($search)) {
                $query .= " WHERE customer_id LIKE ? OR full_name LIKE ? OR phone LIKE ?";
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ?, ?";
            
            $this->db->prepare($query);
            
            if (!empty($search)) {
                $search_term = "%{$search}%";
                $this->db->bind($search_term, 's');
                $this->db->bind($search_term, 's');
                $this->db->bind($search_term, 's');
            }
            
            $this->db->bind($offset, 'i');
            $this->db->bind($limit, 'i');
            $this->db->execute();
            
            return $this->db->fetchAll();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return [];
        }
    }

    /**
     * একক কাস্টমার পান
     */
    public function getByID($customer_id) {
        try {
            $this->db->prepare("SELECT c.*, a.name as area_name, p.name as package_name FROM customers c LEFT JOIN areas a ON c.area_id = a.id LEFT JOIN packages p ON c.package_id = p.id WHERE c.customer_id = ?");
            $this->db->bind($customer_id, 's');
            $this->db->execute();
            
            return $this->db->fetchRow();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }

    /**
     * কাস্টমার স্ট্যাটাস পরিবর্তন করুন
     */
    public function changeStatus($customer_id, $status) {
        try {
            $this->db->prepare("UPDATE customers SET status = ? WHERE customer_id = ?");
            $this->db->bind($status, 's');
            $this->db->bind($customer_id, 's');
            
            return $this->db->execute();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ইউনিক কাস্টমার ID জেনারেট করুন
     */
    private function generateCustomerID() {
        $prefix = 'CUST';
        $timestamp = substr(str_pad(time(), 10, '0', STR_PAD_LEFT), -6);
        $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . '-' . date('Y') . '-' . $timestamp . $random;
    }

    /**
     * কাস্টমার ব্যালেন্স আপডেট করুন
     */
    public function updateBalance($customer_id, $amount, $type = 'deduct') {
        try {
            if ($type == 'add') {
                $this->db->prepare("UPDATE customers SET balance = balance + ? WHERE customer_id = ?");
            } else {
                $this->db->prepare("UPDATE customers SET balance = balance - ? WHERE customer_id = ?");
            }
            
            $this->db->bind($amount, 'd');
            $this->db->bind($customer_id, 's');
            
            return $this->db->execute();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
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

?>
