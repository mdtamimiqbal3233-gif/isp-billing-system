<?php
/**
 * ISP Billing System - Configuration File
 * ডাটাবেস এবং সিস্টেম কনফিগারেশন
 */

// ডাটাবেস কনফিগারেশন
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'isp_billing_db');
define('DB_PORT', 3306);

// সিস্টেম কনফিগারেশন
define('SITE_NAME', 'ISP Billing System');
define('SITE_URL', 'http://localhost/isp-billing-system/');
define('SITE_VERSION', '1.0.0');

// নিরাপত্তা কনফিগারেশন
define('SESSION_TIMEOUT', 3600); // 1 ঘন্টা
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 মিনিট

// এনক্রিপশন কী
define('ENCRYPTION_KEY', 'your-secret-key-here-change-in-production');
define('CIPHER', 'AES-256-CBC');

// MikroTik কনফিগারেশন
define('MIKROTIK_HOST', '192.168.88.1');
define('MIKROTIK_USER', 'admin');
define('MIKROTIK_PASS', 'admin');
define('MIKROTIK_PORT', 8728);

// পেমেন্ট গেটওয়ে কনফিগারেশন
define('BKASH_API_KEY', 'your-bkash-api-key');
define('BKASH_SECRET_KEY', 'your-bkash-secret-key');
define('NAGAD_API_KEY', 'your-nagad-api-key');
define('ROCKET_API_KEY', 'your-rocket-api-key');
define('SSLCOMMERZ_STORE_ID', 'your-store-id');
define('SSLCOMMERZ_STORE_PASSWORD', 'your-store-password');

// SMS কনফিগারেশন
define('SMS_API_URL', 'https://smsapi.example.com');
define('SMS_API_KEY', 'your-sms-api-key');
define('SMS_FROM', 'ISP-Billing');

// WhatsApp কনফিগারেশন
define('WHATSAPP_API_URL', 'https://api.whatsapp.com');
define('WHATSAPP_API_TOKEN', 'your-whatsapp-token');
define('WHATSAPP_PHONE_ID', 'your-phone-id');

// ইমেইল কনফিগারেশন
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'your-email@gmail.com');
define('MAIL_PASS', 'your-app-password');
define('MAIL_FROM', 'billing@ispbilling.local');

// ফাইল আপলোড কনফিগারেশন
define('UPLOAD_DIR', '/uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// লগিং কনফিগারেশন
define('LOG_DIR', '/logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// ডেভেলপমেন্ট মোড
define('DEBUG_MODE', true); // প্রোডাকশনে false করুন
define('ERROR_REPORTING', E_ALL);
define('DISPLAY_ERRORS', true); // প্রোডাকশনে false করুন

// টাইম জোন
date_default_timezone_set('Asia/Dhaka');

// লোকালাইজেশন
define('DEFAULT_LANGUAGE', 'bn'); // বাংলা
define('DEFAULT_CURRENCY', 'BDT');
define('CURRENCY_SYMBOL', '৳');

?>
