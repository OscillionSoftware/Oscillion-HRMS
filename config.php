<?php
// Database bootstrap: opens SQLite, creates schema, seeds the super admin.

const DB_PATH = __DIR__ . '/data/hrms.sqlite';

const LEAD_STATUSES   = ['new', 'contacted', 'follow_up', 'interested', 'not_interested', 'converted', 'closed'];
const CLIENT_STATUSES  = ['active', 'inactive'];
const PROJECT_STATUSES  = ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'];
const EMPLOYEE_STATUSES = ['active', 'on_leave', 'resigned'];
const TASK_STATUSES     = ['todo', 'in_progress', 'done'];
const SERVICE_TYPES     = ['domain', 'hosting', 'ssl', 'email', 'other'];
const CREDENTIAL_TYPES  = ['website', 'cpanel', 'gmail', 'aws', 'firebase', 'mongodb', 'github', 'database', 'other'];
const INVOICE_STATUSES  = ['pending', 'partial', 'paid', 'cancelled'];
const ITEM_CATEGORIES   = ['project_charges', 'service_charges', 'domain_renewal', 'hosting', 'maintenance', 'feature_addon', 'bug_fixes', 'other'];
const ACCOUNT_TYPES     = ['upi', 'bank', 'cash', 'other'];
const EXPENSE_CATEGORIES = ['rent', 'salary', 'tools_subscriptions', 'server_hosting', 'domain_purchase', 'marketing', 'travel', 'office_supplies', 'food', 'other'];
const QUOTE_STATUSES     = ['pending', 'accepted', 'rejected'];
const LEAD_PRIORITIES = ['low', 'normal', 'high', 'urgent'];
const LEAD_BEHAVIOURS = ['normal', 'polite', 'interested', 'busy', 'rude', 'not_reachable'];
const FOLLOWUP_TYPES  = ['call', 'whatsapp', 'email', 'meeting', 'other'];

// Per-deployment secret used to encrypt sensitive fields at rest (credential
// vault passwords). Generated once on first run and stored outside the web
// root's version control; losing this file makes stored passwords unrecoverable.
function app_key(): string
{
    static $key = null;
    if ($key !== null) return $key;
    $path = __DIR__ . '/data/.appkey';
    if (!is_file($path)) {
        file_put_contents($path, bin2hex(random_bytes(32)));
        chmod($path, 0600);
    }
    $key = hex2bin(trim(file_get_contents($path)));
    return $key;
}

function encrypt_secret(string $plaintext): string
{
    if ($plaintext === '') return '';
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plaintext, 'aes-256-cbc', app_key(), OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}

function decrypt_secret(?string $stored): string
{
    if (!$stored) return '';
    $raw = base64_decode($stored, true);
    if ($raw === false || strlen($raw) < 17) return ''; // not encrypted / malformed
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'aes-256-cbc', app_key(), OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        init_schema($pdo);
    }
    return $pdo;
}

function init_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'staff',
        api_token TEXT,
        api_token_created_at TEXT,
        must_change_password INTEGER NOT NULL DEFAULT 0,
        failed_login_attempts INTEGER NOT NULL DEFAULT 0,
        locked_until TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        phone TEXT NOT NULL,
        email TEXT,
        company_name TEXT,
        status TEXT NOT NULL DEFAULT 'new',
        behaviour TEXT NOT NULL DEFAULT 'normal',
        priority TEXT NOT NULL DEFAULT 'normal',
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        phone TEXT NOT NULL,
        email TEXT,
        company_name TEXT,
        address TEXT,
        city TEXT,
        status TEXT NOT NULL DEFAULT 'active',
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        client_id INTEGER REFERENCES clients(id),
        description TEXT,
        status TEXT NOT NULL DEFAULT 'planning',
        start_date TEXT,
        end_date TEXT,
        budget REAL,
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS employees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        phone TEXT NOT NULL,
        email TEXT,
        designation TEXT,
        department TEXT,
        joining_date TEXT,
        salary REAL,
        address TEXT,
        status TEXT NOT NULL DEFAULT 'active',
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
        title TEXT NOT NULL,
        description TEXT,
        assigned_to INTEGER REFERENCES employees(id),
        status TEXT NOT NULL DEFAULT 'todo',
        priority TEXT NOT NULL DEFAULT 'normal',
        due_date TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
        type TEXT NOT NULL DEFAULT 'domain',
        provider TEXT,
        name TEXT NOT NULL,
        purchase_date TEXT,
        expiry_date TEXT NOT NULL,
        years INTEGER NOT NULL DEFAULT 1,
        our_cost REAL,
        client_charge REAL,
        auto_renew INTEGER NOT NULL DEFAULT 0,
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_credentials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
        type TEXT NOT NULL DEFAULT 'website',
        label TEXT,
        url TEXT,
        username TEXT,
        password TEXT,
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_followups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
        followup_date TEXT NOT NULL,
        message TEXT,
        type TEXT NOT NULL DEFAULT 'call',
        status TEXT NOT NULL DEFAULT 'pending',
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL DEFAULT ''
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_accounts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        type TEXT NOT NULL DEFAULT 'upi',
        details TEXT,
        active INTEGER NOT NULL DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_no TEXT NOT NULL UNIQUE,
        project_id INTEGER REFERENCES projects(id),
        type TEXT NOT NULL DEFAULT 'non_gst',
        gst_percent REAL NOT NULL DEFAULT 18,
        status TEXT NOT NULL DEFAULT 'pending',
        issue_date TEXT NOT NULL,
        due_date TEXT,
        discount REAL NOT NULL DEFAULT 0,
        terms TEXT,
        notes TEXT,
        public_token TEXT NOT NULL UNIQUE,
        password_hash TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        category TEXT NOT NULL DEFAULT 'project_charges',
        description TEXT NOT NULL,
        qty REAL NOT NULL DEFAULT 1,
        rate REAL NOT NULL DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        account_id INTEGER REFERENCES payment_accounts(id),
        amount REAL NOT NULL,
        paid_date TEXT NOT NULL,
        reference TEXT,
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        expense_date TEXT NOT NULL,
        category TEXT NOT NULL DEFAULT 'other',
        description TEXT NOT NULL,
        amount REAL NOT NULL,
        paid_to TEXT,
        account_id INTEGER REFERENCES payment_accounts(id),
        project_id INTEGER REFERENCES projects(id),
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quotations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        quote_no TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL,
        scope TEXT,
        client_id INTEGER REFERENCES clients(id),
        prospect_name TEXT,
        prospect_company TEXT,
        prospect_phone TEXT,
        prospect_email TEXT,
        type TEXT NOT NULL DEFAULT 'non_gst',
        gst_percent REAL NOT NULL DEFAULT 18,
        discount REAL NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'pending',
        issue_date TEXT NOT NULL,
        valid_until TEXT,
        terms TEXT,
        notes TEXT,
        public_token TEXT NOT NULL UNIQUE,
        project_id INTEGER REFERENCES projects(id),
        created_by INTEGER REFERENCES users(id),
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quotation_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        quotation_id INTEGER NOT NULL REFERENCES quotations(id) ON DELETE CASCADE,
        category TEXT NOT NULL DEFAULT 'project_charges',
        description TEXT NOT NULL,
        qty REAL NOT NULL DEFAULT 1,
        rate REAL NOT NULL DEFAULT 0
    )");

    // Migration: API token expiry + forced password change support
    $userCols = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
    if (!in_array('api_token_created_at', $userCols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN api_token_created_at TEXT');
    }
    if (!in_array('must_change_password', $userCols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN must_change_password INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('failed_login_attempts', $userCols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN failed_login_attempts INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('locked_until', $userCols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN locked_until TEXT');
    }

    // Migration: which payment accounts to show on each invoice (comma-separated ids)
    $cols = array_column($pdo->query('PRAGMA table_info(invoices)')->fetchAll(), 'name');
    if (!in_array('payment_account_ids', $cols, true)) {
        $pdo->exec('ALTER TABLE invoices ADD COLUMN payment_account_ids TEXT');
    }

    // Migration: separate bank fields on payment accounts
    $paCols = array_column($pdo->query('PRAGMA table_info(payment_accounts)')->fetchAll(), 'name');
    if (!in_array('account_no', $paCols, true)) {
        $pdo->exec('ALTER TABLE payment_accounts ADD COLUMN account_no TEXT');
        $pdo->exec('ALTER TABLE payment_accounts ADD COLUMN ifsc TEXT');
    }

    // Seed payment accounts + default settings
    if (!$pdo->query('SELECT COUNT(*) FROM payment_accounts')->fetchColumn()) {
        $pdo->exec("INSERT INTO payment_accounts (name, type, details) VALUES
            ('PhonePe', 'upi', 'phonepe-number-here'),
            ('Google Pay / UPI', 'upi', 'your-upi-id@bank'),
            ('Bank Current Account', 'bank', 'A/c No: __________  IFSC: __________'),
            ('Cash', 'cash', '')");
    }
    if (!$pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn()) {
        $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
        foreach ([
            'company_name'    => 'Oscillion Software',
            'company_tagline' => 'Build Beyond Boundaries',
            'company_address' => 'Your office address here',
            'company_phone'   => '+91 00000 00000',
            'company_email'   => 'oscillionsoftware@gmail.com',
            'company_gstin'   => 'YOUR-GSTIN-HERE',
            'invoice_prefix'  => 'OSC',
            'default_terms'   => "1. Payment is due within the due date mentioned on the invoice.\n2. 50% advance is required to start the work; remaining on delivery.\n3. Domain, hosting and third-party charges are billed at renewal and are non-refundable.\n4. Support covers bug fixes for 30 days after delivery; new features are billed separately.\n5. Delayed payments may pause ongoing services and renewals.",
        ] as $k => $v) {
            $stmt->execute([$k, $v]);
        }
    }
    // Seed super admin with a random one-time password (not a fixed default),
    // written next to the encryption key so only the server operator can read
    // it; the account is forced to change it on first login.
    $exists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn();
    if (!$exists) {
        $password = bin2hex(random_bytes(9));
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, must_change_password)
                               VALUES (?, ?, ?, 'super_admin', 1)");
        $stmt->execute(['Oscillion Software', 'oscillionsoftware@gmail.com', password_hash($password, PASSWORD_DEFAULT)]);

        $path = __DIR__ . '/data/INITIAL_ADMIN_PASSWORD.txt';
        file_put_contents($path,
            "Initial super admin login for Oscillion HRMS\n" .
            "Email:    oscillionsoftware@gmail.com\n" .
            "Password: $password\n\n" .
            "You will be required to change this password on first login.\n" .
            "Delete this file once you've logged in.\n");
        chmod($path, 0600);
    }
}
