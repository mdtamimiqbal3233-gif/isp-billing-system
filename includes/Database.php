<?php
/**
 * ISP Billing System - Database Connection Class
 * ডাটাবেস সংযোগ এবং ক্যোয়ারি পরিচালনা
 */

require_once 'config.php';

class Database {
    private $connection;
    private $stmt;
    private $error;
    private $result;

    /**
     * ডাটাবেস সংযোগ প্রতিষ্ঠা
     */
    public function connect() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                DB_PORT
            );

            if ($this->connection->connect_error) {
                $this->error = "সংযোগ ব্যর্থ: " . $this->connection->connect_error;
                if (DEBUG_MODE) {
                    die($this->error);
                }
                return false;
            }

            // UTF-8 সেট করুন
            $this->connection->set_charset("utf8mb4");
            return true;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            if (DEBUG_MODE) {
                die("ডাটাবেস সংযোগ ত্রুটি: " . $this->error);
            }
            return false;
        }
    }

    /**
     * Prepared Statement প্রস্তুত করুন
     * @param string $query SQL ক্যোয়ারি
     */
    public function prepare($query) {
        try {
            $this->stmt = $this->connection->prepare($query);
            if (!$this->stmt) {
                $this->error = "প্রস্তুতি ব্যর্থ: " . $this->connection->error;
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Bind প্যারামিটার
     * @param string $param প্যারামিটার নাম
     * @param mixed $value মূল্য
     * @param string $type ডেটা টাইপ
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = MYSQLI_TYPE_LONG;
                    break;
                case is_float($value):
                    $type = MYSQLI_TYPE_DOUBLE;
                    break;
                case is_string($value):
                    $type = MYSQLI_TYPE_STRING;
                    break;
                default:
                    $type = MYSQLI_TYPE_STRING;
            }
        }

        return $this->stmt->bind_param($type, $value);
    }

    /**
     * ক্যোয়ারি এক্সিকিউট করুন
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ফলাফল পান
     */
    public function getResult() {
        $this->result = $this->stmt->get_result();
        return $this->result;
    }

    /**
     * একটি সারি ফেরত দিন
     */
    public function fetchRow() {
        if ($this->result) {
            return $this->result->fetch_array(MYSQLI_ASSOC);
        }
        return null;
    }

    /**
     * সমস্ত সারি ফেরত দিন
     */
    public function fetchAll() {
        $rows = [];
        if ($this->result) {
            while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * প্রভাবিত সারি সংখ্যা
     */
    public function getAffectedRows() {
        return $this->connection->affected_rows;
    }

    /**
     * শেষ Insert ID
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * ত্রুটি বার্তা পান
     */
    public function getError() {
        return $this->error;
    }

    /**
     * সংযোগ বন্ধ করুন
     */
    public function close() {
        if ($this->stmt) {
            $this->stmt->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * সংযোগ পরীক্ষা
     */
    public function testConnection() {
        if ($this->connect()) {
            $this->close();
            return true;
        }
        return false;
    }

    /**
     * টেবিল বিদ্যমান কিনা চেক করুন
     */
    public function tableExists($tableName) {
        $this->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
        $this->bind(DB_NAME, 's');
        $this->bind($tableName, 's');
        $this->execute();
        $this->getResult();
        return $this->result->num_rows > 0;
    }

    /**
     * ডাটাবেস ব্যাকআপ
     */
    public function backup($outputFile = null) {
        if (!$outputFile) {
            $outputFile = LOG_DIR . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }

        try {
            $tables = [];
            $this->prepare("SHOW TABLES FROM " . DB_NAME);
            $this->execute();
            $result = $this->getResult();

            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }

            $output = "-- ISP Billing System Backup\n";
            $output .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Database: " . DB_NAME . "\n\n";

            foreach ($tables as $table) {
                $this->prepare("SHOW CREATE TABLE " . $table);
                $this->execute();
                $result = $this->getResult();
                $row = $result->fetch_row();
                $output .= $row[1] . ";\n\n";
            }

            file_put_contents($outputFile, $output);
            return $outputFile;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}

// ডাটাবেস ইনস্ট্যান্স তৈরি করুন
$db = new Database();
$db->connect();

?>
