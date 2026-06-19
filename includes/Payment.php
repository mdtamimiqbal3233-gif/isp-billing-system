<?php
/**
 * ISP Billing System - Payment Model
 * পেমেন্ট সংক্রান্ত সমস্ত ফাংশন
 */

class Payment {
    private $db;
    private $error;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /**
     * নতুন পেমেন্ট রেকর্ড করুন
     */
    public function create($data) {
        try {
            $payment_id = $this->generatePaymentID();
            
            $this->db->prepare("INSERT INTO payments (payment_id, customer_id, bill_id, amount, payment_method, transaction_id, status, reference_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $this->db->bind($payment_id, 's');
            $this->db->bind($data['customer_id'], 'i');
            $this->db->bind($data['bill_id'] ?? null, 'i');
            $this->db->bind($data['amount'], 'd');
            $this->db->bind($data['payment_method'], 's');
            $this->db->bind($data['transaction_id'] ?? '', 's');
            $this->db->bind($data['status'] ?? 'pending', 's');
            $this->db->bind($data['reference_number'] ?? '', 's');
            $this->db->bind($data['notes'] ?? '', 's');
            
            if ($this->db->execute()) {
                return ['status' => true, 'payment_id' => $payment_id];
            }
            
            $this->error = "পেমেন্ট রেকর্ড করতে ব্যর্থ";
            return false;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * পেমেন্ট নিশ্চিত করুন
     */
    public function confirm($payment_id) {
        try {
            // পেমেন্ট তথ্য পান
            $payment = $this->getByID($payment_id);
            if (!$payment) {
                $this->error = "পেমেন্ট পাওয়া যায়নি";
                return false;
            }

            // স্ট্যাটাস আপডেট করুন
            $this->db->prepare("UPDATE payments SET status = 'success', payment_date = NOW() WHERE payment_id = ?");
            $this->db->bind($payment_id, 's');
            
            if ($this->db->execute()) {
                // কাস্টমার ব্যালেন্স আপডেট করুন
                $this->updateCustomerBalance($payment['customer_id'], $payment['amount']);
                
                // বিল আপডেট করুন
                if ($payment['bill_id']) {
                    $this->updateBillPayment($payment['bill_id'], $payment['amount']);
                }
                
                return true;
            }
            
            $this->error = "পেমেন্ট নিশ্চিত করতে ব্যর্থ";
            return false;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * পেমেন্ট বাতিল করুন
     */
    public function cancel($payment_id, $reason = '') {
        try {
            $this->db->prepare("UPDATE payments SET status = 'cancelled', notes = ? WHERE payment_id = ?");
            $this->db->bind($reason, 's');
            $this->db->bind($payment_id, 's');
            
            return $this->db->execute();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * পেমেন্ট তালিকা পান
     */
    public function getAll($filters = [], $limit = 50, $offset = 0) {
        try {
            $query = "SELECT p.*, c.full_name, c.phone FROM payments p JOIN customers c ON p.customer_id = c.id WHERE 1=1";

            if (!empty($filters['status'])) {
                $query .= " AND p.status = '{$filters['status']}'";
            }

            if (!empty($filters['customer_id'])) {
                $query .= " AND c.customer_id = '{$filters['customer_id']}'";
            }

            if (!empty($filters['method'])) {
                $query .= " AND p.payment_method = '{$filters['method']}'";
            }

            if (!empty($filters['from_date'])) {
                $query .= " AND DATE(p.payment_date) >= '{$filters['from_date']}'";
            }

            if (!empty($filters['to_date'])) {
                $query .= " AND DATE(p.payment_date) <= '{$filters['to_date']}'";
            }

            $query .= " ORDER BY p.created_at DESC LIMIT ?, ?";

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
     * একক পেমেন্ট পান
     */
    public function getByID($payment_id) {
        try {
            $this->db->prepare("SELECT p.*, c.full_name FROM payments p JOIN customers c ON p.customer_id = c.id WHERE p.payment_id = ?");
            $this->db->bind($payment_id, 's');
            $this->db->execute();

            return $this->db->fetchRow();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }

    /**
     * কাস্টমারের পেমেন্ট হিস ট্রি পান
     */
    public function getCustomerPayments($customer_id, $limit = 20) {
        try {
            $this->db->prepare("SELECT * FROM payments WHERE customer_id = ? ORDER BY created_at DESC LIMIT ?");
            $this->db->bind($customer_id, 'i');
            $this->db->bind($limit, 'i');
            $this->db->execute();

            return $this->db->fetchAll();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return [];
        }
    }

    /**
     * কাস্টমার ব্যালেন্স আপডেট করুন
     */
    private function updateCustomerBalance($customer_id, $amount) {
        try {
            $this->db->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?");
            $this->db->bind($amount, 'd');
            $this->db->bind($customer_id, 'i');
            
            return $this->db->execute();

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * বিল পেমেন্ট আপডেট করুন
     */
    private function updateBillPayment($bill_id, $amount) {
        try {
            // পেইড অ্যামাউন্ট যোগ করুন
            $this->db->prepare("UPDATE bills SET paid_amount = paid_amount + ? WHERE id = ?");
            $this->db->bind($amount, 'd');
            $this->db->bind($bill_id, 'i');
            $this->db->execute();

            // বিল স্ট্যাটাস আপডেট করুন
            $this->db->prepare("SELECT total_due, paid_amount FROM bills WHERE id = ?");
            $this->db->bind($bill_id, 'i');
            $this->db->execute();
            $bill = $this->db->fetchRow();

            if ($bill['paid_amount'] >= $bill['total_due']) {
                // সম্পূর্ণ পেইড
                $this->db->prepare("UPDATE bills SET status = 'paid' WHERE id = ?");
                $this->db->bind($bill_id, 'i');
            } else {
                // আংশিক পেইড
                $this->db->prepare("UPDATE bills SET status = 'partial' WHERE id = ?");
                $this->db->bind($bill_id, 'i');
            }

            return $this->db->execute();

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * মাসিক পেমেন্ট রিপোর্ট পান
     */
    public function getMonthlyReport($month = null) {
        try {
            if (!$month) {
                $month = date('Y-m');
            }

            $this->db->prepare("SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                payment_method,
                status
            FROM payments 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ? 
            GROUP BY payment_method, status");
            
            $this->db->bind($month, 's');
            $this->db->execute();

            return $this->db->fetchAll();

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return [];
        }
    }

    /**
     * ইউনিক পেমেন্ট ID জেনারেট করুন
     */
    private function generatePaymentID() {
        $prefix = 'PAY';
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
