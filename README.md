# ISP Billing System - Enterprise Edition

একটি সম্পূর্ণ ISP (ইন্টারনেট সেবা প্রদানকারী) ম্যানেজমেন্ট এবং বিলিং সিস্টেম

## সিস্টেম বৈশিষ্ট্য

### 👥 কাস্টমার ম্যানেজমেন্ট
- নতুন কাস্টমার যোগ করা
- কাস্টমার ID ও পাসওয়ার্ড
- মোবাইল নম্বর, ঠিকানা, NID
- Active/Inactive/Suspended স্ট্যাটাস

### 🌐 প্যাকেজ ম্যানেজমেন্ট
- 5 Mbps, 10 Mbps, 20 Mbps প্যাকেজ তৈরি
- প্যাকেজের দাম নির্ধারণ
- Upgrade/Downgrade সুবিধা

### 💰 বিলিং সিস্টেম
- মাসিক বিল জেনারেশন
- বিলের তারিখ সেট করা
- Due ও Paid স্ট্যাটাস
- Auto Carry Forward Due
- Advance Payment, Discount ও Waiver

### 💳 অনলাইন পেমেন্ট
- bKash, Nagad, Rocket Integration
- SSLCommerz Payment Gateway
- QR Payment Support
- Payment History

### 📲 নোটিফিকেশন সিস্টেম
- SMS নোটিফিকেশন
- WhatsApp মেসেজ
- Cron Job অটোমেশন

### 📡 MikroTik ইন্টিগ্রেশন
- PPPoE User Management
- Hotspot User Management
- Queue Management
- RADIUS সাপোর্ট

### 🌍 কাস্টমার পোর্টাল
- বিল ডাউনলোড
- অনলাইন পেমেন্ট
- প্রোফাইল ম্যানেজমেন্ট

## প্রযুক্তি স্ট্যাক

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, jQuery
- **APIs**: MikroTik API, bKash, Nagad, Rocket, SSLCommerz
- **Server**: Apache/Nginx

## ফোল্ডার স্ট্রাকচার

```
isp-billing-system/
├── config/              # কনফিগারেশন ফাইল
├── includes/            # রিইউজেবল PHP ফাইল
├── assets/              # CSS, JS, ইমেজ
├── customers/           # কাস্টমার ম্যানেজমেন্ট
├── packages/            # প্যাকেজ ম্যানেজমেন্ট
├── billing/             # বিলিং মডিউল
├── payments/            # পেমেন্ট প্রসেসিং
├── mikrotik/            # MikroTik ইন্টিগ্রেশন
├── reports/             # রিপোর্ট জেনারেশন
├── settings/            # সিস্টেম সেটিংস
├── cron/                # অটোমেটেড টাস্ক
├── api/                 # REST API
├── database/            # SQL স্কিপ্ট
└── index.php            # হোম পেজ
```

## ইনস্টলেশন

1. Repository clone করুন
2. `database/schema.sql` চালান
3. `config/config.php` এডিট করুন
4. ওয়েব সার্ভার রেডি করুন

## লাইসেন্স

এন্টারপ্রাইজ

## সাপোর্ট

যোগাযোগ করুন: support@ispbilling.local

---

**Version**: 1.0.0  
**Last Updated**: 2026-06-19
