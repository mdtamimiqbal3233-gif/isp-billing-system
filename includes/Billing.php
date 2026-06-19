<?php
/**
 * ISP Billing System - Billing Model
 * বিলিং সংক্রান্ত সমস্ত ফাংশন
 */

class Billing {
    private $db;
    private $error;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /**
     * নতুন বিল তৈরি করুন
     */
    public function createBill($customer_id, $package_id, $amount, $due_date) {
        try {
            $bill_id = $this->generateBillID();
            $billing_month = date('Y-m-01');

            $this->db->prepare("INSERT INTO bills (bill_id, customer_id, package_id, amount, total_due, billing_month, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $this->db->bind($bill_id, 's');
            $this->db->bind($customer_id, 'i');
            $this->db->bind($package_id, 'i');
            $this->db->bind($amount, 'd');
            $this->db->bind($amount, 'd');
            $this->db->bind($billing_month, 's');
            $this->db->bind($due_date, 's');
            $this->db->bind('pending', 's');
            
            if ($this->db->execute()) {
                return ['status' => true, 'bill_id' => $bill_id];
            }
            
            $this->error = "বিল তৈরি করতে ব্যর্থ";
            return false;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * মাসিক বিল জেনারেট করুন (সকল কাস্টমারের জন্য)
     */
    public function generateMonthlyBills() {
        try {
            // সমস্ত সক্রিয় কাস্টমার পান
            $this->db->prepare("SELECT c.id, c.customer_id, c.package_id, p.price FROM customers c JOIN packages p ON c.package_id = p.id WHERE c.status = 'active'");
            $this->db->execute();
            $customers = $this->db->fetchAll();

            $created_count = 0;
            $due_date = date('Y-m-d', strtotime('+10 days'));

            foreach ($customers as $customer) {
                // এই মাসের জন্য ইতিমধ্যে বিল আছে কিনা চেক করুন
                $this->db->prepare("SELECT id FROM bills WHERE customer_id = ? AND billing_month = DATE_FORMAT(NOW(), '%Y-%m-01')");
                $this->db->bind($customer['id'], 'i');
                $this->db->execute();
                $existing = $this->db->getResult();

                if ($existing->num_rows == 0) {
                    // নতুন বিল তৈরি করুন
                    $bill = $this->createBill($customer['id'], $customer['package_id'], $customer['price'], $due_date);
                    if ($bill['status']) {
                        $created_count++;
                    }
                }
            }

            return ['status' => true, 'created' => $created_count];

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * বিল তালিকা পান
     */
    public function getBills($filters = [], $limit = 50, $offset = 0) {
        try {
            $query = "SELECT b.*, c.full_name, c.phone, p.name as package_name FROM bills b JOIN customers c ON b.customer_id = c.id JOIN packages p ON b.package_id = p.id WHERE 1=1";

            if (!empty($filters['status'])) {
                $query .= " AND b.status = '{$filters['status']}'";
            }

            if (!empty($filters['customer_id'])) {
                $query .= " AND c.customer_id = '{$filters['customer_id']}'";
            }

            if (!empty($filters['month'])) {
                $query .= " AND b.billing_month LIKE '{$filters['month']}%'";
            }

            $query .= " ORDER BY b.due_date DESC LIMIT ?, ?";

            $this->db->prepare($query);
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
     * একক বিল পান
     */
    public function getBillByID($bill_id) {
        try {
            $this->db->prepare("SELECT b.*, c.full_name, c.phone, c.email, p.name as package_name FROM bills b JOIN customers c ON b.customer_id = c.id JOIN packages p ON b.package_id = p.id WHERE b.bill_id = ?");
            $this->db->bind($bill_id, 's');
            $this->db->execute();

            return $this->db->fetchRow();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }

    /**
     * বিল আপডেট করুন
     */
    public function updateBill($bill_id, $data) {
        try {
            $updates = [];
            $params = [];
            $types = '';

            if (isset($data['amount'])) {
                $updates[] = "amount = ?";
                $params[] = $data['amount'];
                $types .= 'd';
            }

            if (isset($data['discount'])) {
                $updates[] = "discount = ?";
                $params[] = $data['discount'];
                $types .= 'd';
            }

            if (isset($data['waiver'])) {
                $updates[] = "waiver = ?";
                $params[] = $data['waiver'];
                $types .= 'd';
            }

            if (isset($data['status'])) {
                $updates[] = "status = ?";
                $params[] = $data['status'];
                $types .= 's';
            }

            if (empty($updates)) {
                return true;
            }

            $query = "UPDATE bills SET " . implode(', ', $updates) . " WHERE bill_id = ?";
            $params[] = $bill_id;
            $types .= 's';

            $this->db->prepare($query);

            for ($i = 0; $i < count($params); $i++) {
                $this->db->bind($params[$i], $types[$i]);
            }

            return $this->db->execute();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * অতিদেয় বিল চেক করুন এবং স্ট্যাটাস আপডেট করুন
     */
    public function checkOverdueBills() {
        try {
            $this->db->prepare("UPDATE bills SET status = 'overdue' WHERE status IN ('pending', 'partial') AND due_date < DATE(NOW())");
            return $this->db->execute();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * কাস্টমারের মোট বকেয়া পান
     */
    public function getTotalDue($customer_id) {
        try {
            $this->db->prepare("SELECT SUM(total_due - paid_amount) as total_due FROM bills WHERE customer_id = ? AND status IN ('pending', 'overdue', 'partial')");
            $this->db->bind($customer_id, 'i');
            $this->db->execute();

            $result = $this->db->fetchRow();
            return $result['total_due'] ?? 0;

        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * ইউনিক বিল ID জেনারেট করুন
     */
    private function generateBillID() {
        $prefix = 'BILL';
        $timestamp = substr(str_pad(time(), 10, '0', STR_PAD_LEFT), -6);
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . '-' . date('Y') . '-' . $timestamp . $random;
    }

    /**
     * ত্রুটি বার্তা পান
     */
    public function getError() {
        return $this->error;
    }
}

?>
