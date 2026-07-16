<?php
// Shared helpers: auth, lead queries, misc.

require_once __DIR__ . '/config.php';

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// ---------- Auth ----------

function attempt_login(string $email, string $password): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE lower(email) = lower(?)');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return null;
}

function issue_api_token(int $userId): string
{
    // Reuse the existing token so logging in from one device doesn't
    // invalidate sessions on other devices.
    $stmt = db()->prepare('SELECT api_token FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $token = $stmt->fetchColumn();
    if (!$token) {
        $token = bin2hex(random_bytes(32));
        db()->prepare('UPDATE users SET api_token = ? WHERE id = ?')->execute([$token, $userId]);
    }
    return $token;
}

function user_by_token(?string $token): ?array
{
    if (!$token) return null;
    $stmt = db()->prepare('SELECT * FROM users WHERE api_token = ?');
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function current_web_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// ---------- Leads ----------

function lead_filters_from(array $src): array
{
    return [
        'search'        => trim($src['search'] ?? ''),
        'status'        => $src['status'] ?? '',
        'priority'      => $src['priority'] ?? '',
        'behaviour'     => $src['behaviour'] ?? '',
        'followup_date' => $src['followup_date'] ?? '',
    ];
}

function query_leads(array $f): array
{
    $sql = "SELECT l.*,
                   (SELECT followup_date FROM lead_followups fu
                     WHERE fu.lead_id = l.id AND fu.status = 'pending'
                     ORDER BY fu.followup_date ASC LIMIT 1) AS next_followup
            FROM leads l WHERE 1=1";
    $params = [];

    if ($f['search'] !== '') {
        $sql .= " AND (l.name LIKE :q OR l.phone LIKE :q OR l.email LIKE :q OR l.company_name LIKE :q)";
        $params[':q'] = '%' . $f['search'] . '%';
    }
    foreach (['status', 'priority', 'behaviour'] as $col) {
        if (!empty($f[$col])) {
            $sql .= " AND l.$col = :$col";
            $params[":$col"] = $f[$col];
        }
    }
    if (!empty($f['followup_date'])) {
        $sql .= " AND l.id IN (SELECT lead_id FROM lead_followups
                               WHERE status = 'pending' AND date(followup_date) = date(:fdate))";
        $params[':fdate'] = $f['followup_date'];
    }
    $sql .= " ORDER BY CASE l.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END,
              l.updated_at DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_lead(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM leads WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function get_followups(int $leadId): array
{
    $stmt = db()->prepare("SELECT fu.*, u.name AS created_by_name
                           FROM lead_followups fu LEFT JOIN users u ON u.id = fu.created_by
                           WHERE fu.lead_id = ? ORDER BY fu.followup_date DESC, fu.id DESC");
    $stmt->execute([$leadId]);
    return $stmt->fetchAll();
}

function validate_lead(array $in): array
{
    $errors = [];
    if (trim($in['name'] ?? '') === '')  $errors[] = 'Name is required.';
    if (trim($in['phone'] ?? '') === '') $errors[] = 'Phone number is required.';
    if (($in['email'] ?? '') !== '' && !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is invalid.';
    if (!in_array($in['status'] ?? 'new', LEAD_STATUSES, true))          $errors[] = 'Invalid status.';
    if (!in_array($in['priority'] ?? 'normal', LEAD_PRIORITIES, true))   $errors[] = 'Invalid priority.';
    if (!in_array($in['behaviour'] ?? 'normal', LEAD_BEHAVIOURS, true))  $errors[] = 'Invalid behaviour.';
    return $errors;
}

function save_lead(array $in, ?int $id, ?int $userId): int
{
    $fields = [
        trim($in['name']), trim($in['phone']), trim($in['email'] ?? ''), trim($in['company_name'] ?? ''),
        $in['status'] ?? 'new', $in['behaviour'] ?? 'normal', $in['priority'] ?? 'normal', trim($in['notes'] ?? ''),
    ];
    if ($id) {
        $fields[] = $id;
        db()->prepare("UPDATE leads SET name=?, phone=?, email=?, company_name=?, status=?, behaviour=?,
                       priority=?, notes=?, updated_at=datetime('now') WHERE id=?")->execute($fields);
        return $id;
    }
    $fields[] = $userId;
    db()->prepare("INSERT INTO leads (name, phone, email, company_name, status, behaviour, priority, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?,?)")->execute($fields);
    return (int) db()->lastInsertId();
}

function add_followup(int $leadId, array $in, ?int $userId): array
{
    $errors = [];
    $date = trim($in['followup_date'] ?? '');
    if ($date === '' || !strtotime($date)) $errors[] = 'A valid follow-up date is required.';
    $type = in_array($in['type'] ?? 'call', FOLLOWUP_TYPES, true) ? ($in['type'] ?? 'call') : 'call';
    if ($errors) return [null, $errors];

    db()->prepare("INSERT INTO lead_followups (lead_id, followup_date, message, type, created_by)
                   VALUES (?,?,?,?,?)")
        ->execute([$leadId, $date, trim($in['message'] ?? ''), $type, $userId]);
    db()->prepare("UPDATE leads SET status = CASE WHEN status IN ('new','contacted') THEN 'follow_up' ELSE status END,
                   updated_at = datetime('now') WHERE id = ?")->execute([$leadId]);
    return [(int) db()->lastInsertId(), []];
}

// ---------- Clients ----------

function query_clients(array $f): array
{
    $sql = 'SELECT * FROM clients WHERE 1=1';
    $params = [];
    if (!empty($f['search'])) {
        $sql .= ' AND (name LIKE :q OR phone LIKE :q OR email LIKE :q OR company_name LIKE :q OR city LIKE :q)';
        $params[':q'] = '%' . trim($f['search']) . '%';
    }
    if (!empty($f['status'])) {
        $sql .= ' AND status = :status';
        $params[':status'] = $f['status'];
    }
    $sql .= ' ORDER BY updated_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_client(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function validate_client(array $in): array
{
    $errors = [];
    if (trim($in['name'] ?? '') === '')  $errors[] = 'Name is required.';
    if (trim($in['phone'] ?? '') === '') $errors[] = 'Phone number is required.';
    if (($in['email'] ?? '') !== '' && !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is invalid.';
    if (!in_array($in['status'] ?? 'active', CLIENT_STATUSES, true)) $errors[] = 'Invalid status.';
    return $errors;
}

function save_client(array $in, ?int $id, ?int $userId): int
{
    $fields = [
        trim($in['name']), trim($in['phone']), trim($in['email'] ?? ''), trim($in['company_name'] ?? ''),
        trim($in['address'] ?? ''), trim($in['city'] ?? ''), $in['status'] ?? 'active', trim($in['notes'] ?? ''),
    ];
    if ($id) {
        $fields[] = $id;
        db()->prepare("UPDATE clients SET name=?, phone=?, email=?, company_name=?, address=?, city=?,
                       status=?, notes=?, updated_at=datetime('now') WHERE id=?")->execute($fields);
        return $id;
    }
    $fields[] = $userId;
    db()->prepare("INSERT INTO clients (name, phone, email, company_name, address, city, status, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?,?)")->execute($fields);
    return (int) db()->lastInsertId();
}

// ---------- Projects ----------

function query_projects(array $f): array
{
    $sql = "SELECT p.*, c.name AS client_name, c.phone AS client_phone,
                   (SELECT COUNT(*) FROM project_tasks t WHERE t.project_id = p.id) AS task_total,
                   (SELECT COUNT(*) FROM project_tasks t WHERE t.project_id = p.id AND t.status = 'done') AS task_done
            FROM projects p LEFT JOIN clients c ON c.id = p.client_id WHERE 1=1";
    $params = [];
    if (!empty($f['search'])) {
        $sql .= ' AND (p.name LIKE :q OR p.description LIKE :q OR c.name LIKE :q OR c.company_name LIKE :q)';
        $params[':q'] = '%' . trim($f['search']) . '%';
    }
    if (!empty($f['status'])) {
        $sql .= ' AND p.status = :status';
        $params[':status'] = $f['status'];
    }
    if (!empty($f['client_id'])) {
        $sql .= ' AND p.client_id = :cid';
        $params[':cid'] = (int) $f['client_id'];
    }
    $sql .= ' ORDER BY p.updated_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_project(int $id): ?array
{
    $stmt = db()->prepare("SELECT p.*, c.name AS client_name, c.phone AS client_phone, c.email AS client_email
                           FROM projects p LEFT JOIN clients c ON c.id = p.client_id WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function validate_project(array $in): array
{
    $errors = [];
    if (trim($in['name'] ?? '') === '') $errors[] = 'Project name is required.';
    if (!in_array($in['status'] ?? 'planning', PROJECT_STATUSES, true)) $errors[] = 'Invalid status.';
    foreach (['start_date', 'end_date'] as $d) {
        if (($in[$d] ?? '') !== '' && !strtotime($in[$d])) $errors[] = "Invalid $d.";
    }
    if (($in['budget'] ?? '') !== '' && !is_numeric($in['budget'])) $errors[] = 'Budget must be a number.';
    if (!empty($in['client_id']) && !get_client((int) $in['client_id'])) $errors[] = 'Client not found.';
    return $errors;
}

function save_project(array $in, ?int $id, ?int $userId): int
{
    $fields = [
        trim($in['name']),
        !empty($in['client_id']) ? (int) $in['client_id'] : null,
        trim($in['description'] ?? ''),
        $in['status'] ?? 'planning',
        trim($in['start_date'] ?? '') ?: null,
        trim($in['end_date'] ?? '') ?: null,
        ($in['budget'] ?? '') !== '' ? (float) $in['budget'] : null,
        trim($in['notes'] ?? ''),
    ];
    if ($id) {
        $fields[] = $id;
        db()->prepare("UPDATE projects SET name=?, client_id=?, description=?, status=?, start_date=?,
                       end_date=?, budget=?, notes=?, updated_at=datetime('now') WHERE id=?")->execute($fields);
        return $id;
    }
    $fields[] = $userId;
    db()->prepare("INSERT INTO projects (name, client_id, description, status, start_date, end_date, budget, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?,?)")->execute($fields);
    return (int) db()->lastInsertId();
}

// ---------- Employees ----------

function query_employees(array $f): array
{
    $sql = 'SELECT * FROM employees WHERE 1=1';
    $params = [];
    if (!empty($f['search'])) {
        $sql .= ' AND (name LIKE :q OR phone LIKE :q OR email LIKE :q OR designation LIKE :q OR department LIKE :q)';
        $params[':q'] = '%' . trim($f['search']) . '%';
    }
    if (!empty($f['status'])) {
        $sql .= ' AND status = :status';
        $params[':status'] = $f['status'];
    }
    $sql .= ' ORDER BY name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_employee(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM employees WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function validate_employee(array $in): array
{
    $errors = [];
    if (trim($in['name'] ?? '') === '')  $errors[] = 'Name is required.';
    if (trim($in['phone'] ?? '') === '') $errors[] = 'Phone number is required.';
    if (($in['email'] ?? '') !== '' && !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is invalid.';
    if (!in_array($in['status'] ?? 'active', EMPLOYEE_STATUSES, true)) $errors[] = 'Invalid status.';
    if (($in['joining_date'] ?? '') !== '' && !strtotime($in['joining_date'])) $errors[] = 'Invalid joining date.';
    if (($in['salary'] ?? '') !== '' && !is_numeric($in['salary'])) $errors[] = 'Salary must be a number.';
    return $errors;
}

function save_employee(array $in, ?int $id, ?int $userId): int
{
    $fields = [
        trim($in['name']), trim($in['phone']), trim($in['email'] ?? ''),
        trim($in['designation'] ?? ''), trim($in['department'] ?? ''),
        trim($in['joining_date'] ?? '') ?: null,
        ($in['salary'] ?? '') !== '' ? (float) $in['salary'] : null,
        trim($in['address'] ?? ''), $in['status'] ?? 'active', trim($in['notes'] ?? ''),
    ];
    if ($id) {
        $fields[] = $id;
        db()->prepare("UPDATE employees SET name=?, phone=?, email=?, designation=?, department=?,
                       joining_date=?, salary=?, address=?, status=?, notes=?, updated_at=datetime('now')
                       WHERE id=?")->execute($fields);
        return $id;
    }
    $fields[] = $userId;
    db()->prepare("INSERT INTO employees (name, phone, email, designation, department, joining_date, salary, address, status, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?)")->execute($fields);
    return (int) db()->lastInsertId();
}

// ---------- Project tasks ----------

function get_project_tasks(int $projectId): array
{
    $stmt = db()->prepare("SELECT t.*, e.name AS assignee_name
                           FROM project_tasks t LEFT JOIN employees e ON e.id = t.assigned_to
                           WHERE t.project_id = ?
                           ORDER BY CASE t.status WHEN 'done' THEN 1 ELSE 0 END,
                                    CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END,
                                    t.due_date IS NULL, t.due_date ASC, t.id ASC");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function query_all_tasks(array $f = []): array
{
    $sql = "SELECT t.*, e.name AS assignee_name, p.name AS project_name
            FROM project_tasks t
            LEFT JOIN employees e ON e.id = t.assigned_to
            JOIN projects p ON p.id = t.project_id WHERE 1=1";
    $params = [];
    if (!empty($f['status'])) {
        $sql .= ' AND t.status = :status';
        $params[':status'] = $f['status'];
    }
    if (!empty($f['project_id'])) {
        $sql .= ' AND t.project_id = :pid';
        $params[':pid'] = (int) $f['project_id'];
    }
    if (!empty($f['assigned_to'])) {
        $sql .= ' AND t.assigned_to = :aid';
        $params[':aid'] = (int) $f['assigned_to'];
    }
    if (!empty($f['search'])) {
        $sql .= ' AND (t.title LIKE :q OR t.description LIKE :q OR p.name LIKE :q)';
        $params[':q'] = '%' . trim($f['search']) . '%';
    }
    $sql .= " ORDER BY CASE t.status WHEN 'done' THEN 1 ELSE 0 END,
              CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END,
              t.due_date IS NULL, t.due_date ASC, t.id ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_task(int $id): ?array
{
    $stmt = db()->prepare("SELECT t.*, e.name AS assignee_name
                           FROM project_tasks t LEFT JOIN employees e ON e.id = t.assigned_to
                           WHERE t.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function validate_task(array $in): array
{
    $errors = [];
    if (trim($in['title'] ?? '') === '') $errors[] = 'Task title is required.';
    if (!in_array($in['status'] ?? 'todo', TASK_STATUSES, true)) $errors[] = 'Invalid task status.';
    if (!in_array($in['priority'] ?? 'normal', LEAD_PRIORITIES, true)) $errors[] = 'Invalid priority.';
    if (($in['due_date'] ?? '') !== '' && !strtotime($in['due_date'])) $errors[] = 'Invalid due date.';
    if (!empty($in['assigned_to']) && !get_employee((int) $in['assigned_to'])) $errors[] = 'Employee not found.';
    return $errors;
}

function save_task(array $in, ?int $id, ?int $userId, ?int $projectId = null): int
{
    $fields = [
        trim($in['title']),
        trim($in['description'] ?? ''),
        !empty($in['assigned_to']) ? (int) $in['assigned_to'] : null,
        $in['status'] ?? 'todo',
        $in['priority'] ?? 'normal',
        trim($in['due_date'] ?? '') ?: null,
    ];
    if ($id) {
        $fields[] = $id;
        db()->prepare("UPDATE project_tasks SET title=?, description=?, assigned_to=?, status=?,
                       priority=?, due_date=?, updated_at=datetime('now') WHERE id=?")->execute($fields);
        return $id;
    }
    array_unshift($fields, $projectId);
    $fields[] = $userId;
    db()->prepare("INSERT INTO project_tasks (project_id, title, description, assigned_to, status, priority, due_date, created_by)
                   VALUES (?,?,?,?,?,?,?,?)")->execute($fields);
    return (int) db()->lastInsertId();
}

// ---------- Project services (hosting / domains / renewals) ----------

function query_services(array $f = []): array
{
    $sql = "SELECT s.*, p.name AS project_name,
                   CAST(julianday(s.expiry_date) - julianday('now','localtime') AS INTEGER) AS days_left
            FROM project_services s JOIN projects p ON p.id = s.project_id WHERE 1=1";
    $params = [];
    if (!empty($f['project_id'])) {
        $sql .= ' AND s.project_id = :pid';
        $params[':pid'] = (int) $f['project_id'];
    }
    if (!empty($f['type'])) {
        $sql .= ' AND s.type = :type';
        $params[':type'] = $f['type'];
    }
    if (!empty($f['search'])) {
        $sql .= ' AND (s.name LIKE :q OR s.provider LIKE :q OR p.name LIKE :q)';
        $params[':q'] = '%' . trim($f['search']) . '%';
    }
    if (!empty($f['expiring_days'])) {
        $sql .= " AND julianday(s.expiry_date) - julianday('now','localtime') <= CAST(:days AS INTEGER)";
        $params[':days'] = (int) $f['expiring_days'];
    }
    $sql .= ' ORDER BY s.expiry_date ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_service(int $id): ?array
{
    $stmt = db()->prepare("SELECT s.*, p.name AS project_name,
                           CAST(julianday(s.expiry_date) - julianday('now','localtime') AS INTEGER) AS days_left
                           FROM project_services s JOIN projects p ON p.id = s.project_id WHERE s.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function validate_service(array $in): array
{
    $errors = [];
    if (trim($in['name'] ?? '') === '') $errors[] = 'Name (domain / plan) is required.';
    if (!in_array($in['type'] ?? 'domain', SERVICE_TYPES, true)) $errors[] = 'Invalid service type.';
    if (trim($in['expiry_date'] ?? '') === '' || !strtotime($in['expiry_date'])) $errors[] = 'A valid expiry date is required.';
    if (($in['purchase_date'] ?? '') !== '' && !strtotime($in['purchase_date'])) $errors[] = 'Invalid purchase date.';
    if (($in['years'] ?? '') !== '' && (!is_numeric($in['years']) || (int) $in['years'] < 1)) $errors[] = 'Term (years) must be a positive number.';
    foreach (['our_cost', 'client_charge'] as $money) {
        if (($in[$money] ?? '') !== '' && !is_numeric($in[$money])) $errors[] = str_replace('_', ' ', ucfirst($money)) . ' must be a number.';
    }
    return $errors;
}

function save_service(array $in, ?int $id, ?int $userId, ?int $projectId = null): int
{
    $fields = [
        $in['type'] ?? 'domain',
        trim($in['provider'] ?? ''),
        trim($in['name']),
        trim($in['purchase_date'] ?? '') ?: null,
        trim($in['expiry_date']),
        ($in['years'] ?? '') !== '' ? (int) $in['years'] : 1,
        ($in['our_cost'] ?? '') !== '' ? (float) $in['our_cost'] : null,
        ($in['client_charge'] ?? '') !== '' ? (float) $in['client_charge'] : null,
        !empty($in['auto_renew']) ? 1 : 0,
        trim($in['notes'] ?? ''),
    ];
    if ($id) {
        $fields[] = $id;
        db()->prepare("UPDATE project_services SET type=?, provider=?, name=?, purchase_date=?, expiry_date=?,
                       years=?, our_cost=?, client_charge=?, auto_renew=?, notes=?, updated_at=datetime('now')
                       WHERE id=?")->execute($fields);
        return $id;
    }
    array_unshift($fields, $projectId);
    $fields[] = $userId;
    db()->prepare("INSERT INTO project_services (project_id, type, provider, name, purchase_date, expiry_date, years, our_cost, client_charge, auto_renew, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute($fields);
    return (int) db()->lastInsertId();
}

// ---------- Project credentials ----------

function get_project_credentials(int $projectId): array
{
    $stmt = db()->prepare('SELECT * FROM project_credentials WHERE project_id = ? ORDER BY type, id');
    $stmt->execute([$projectId]);
    return array_map('decrypt_credential_row', $stmt->fetchAll());
}

function get_credential(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM project_credentials WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? decrypt_credential_row($row) : null;
}

// Passwords are stored AES-256-CBC encrypted (see app_key()); decrypt for display.
function decrypt_credential_row(array $row): array
{
    $row['password'] = decrypt_secret($row['password'] ?? '');
    return $row;
}

function validate_credential(array $in): array
{
    $errors = [];
    if (!in_array($in['type'] ?? 'website', CREDENTIAL_TYPES, true)) $errors[] = 'Invalid credential type.';
    if (trim($in['label'] ?? '') === '' && trim($in['url'] ?? '') === '' && trim($in['username'] ?? '') === '') {
        $errors[] = 'Give at least a label, URL, or username.';
    }
    return $errors;
}

function save_credential(array $in, ?int $id, ?int $userId, ?int $projectId = null): int
{
    $fields = [
        $in['type'] ?? 'website',
        trim($in['label'] ?? ''),
        trim($in['url'] ?? ''),
        trim($in['username'] ?? ''),
        encrypt_secret($in['password'] ?? ''),
        trim($in['notes'] ?? ''),
    ];
    if ($id) {
        $fields[] = $id;
        db()->prepare("UPDATE project_credentials SET type=?, label=?, url=?, username=?, password=?, notes=?,
                       updated_at=datetime('now') WHERE id=?")->execute($fields);
        return $id;
    }
    array_unshift($fields, $projectId);
    $fields[] = $userId;
    db()->prepare("INSERT INTO project_credentials (project_id, type, label, url, username, password, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?)")->execute($fields);
    return (int) db()->lastInsertId();
}

// ---------- Settings & payment accounts ----------

function settings_all(): array
{
    return db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
}

function settings_save(array $kv): void
{
    $stmt = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?)
                           ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    foreach ($kv as $k => $v) {
        $stmt->execute([$k, (string) $v]);
    }
}

function query_accounts(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM payment_accounts' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY id';
    return db()->query($sql)->fetchAll();
}

function save_account(array $in, ?int $id): int
{
    $fields = [trim($in['name']), $in['type'] ?? 'upi', trim($in['details'] ?? ''),
               trim($in['account_no'] ?? ''), trim($in['ifsc'] ?? ''), !empty($in['active']) ? 1 : 0];
    if ($id) {
        $fields[] = $id;
        db()->prepare('UPDATE payment_accounts SET name=?, type=?, details=?, account_no=?, ifsc=?, active=? WHERE id=?')->execute($fields);
        return $id;
    }
    db()->prepare('INSERT INTO payment_accounts (name, type, details, account_no, ifsc, active) VALUES (?,?,?,?,?,?)')->execute($fields);
    return (int) db()->lastInsertId();
}

// ---------- Invoices ----------

function next_invoice_no(): string
{
    $prefix = settings_all()['invoice_prefix'] ?? 'INV';
    $year = date('Y');
    $count = (int) db()->query("SELECT COUNT(*) FROM invoices WHERE invoice_no LIKE '$prefix-$year-%'")->fetchColumn();
    return sprintf('%s-%s-%03d', $prefix, $year, $count + 1);
}

function invoice_totals(array $inv, array $items, float $paid): array
{
    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += (float) $it['qty'] * (float) $it['rate'];
    }
    $afterDiscount = max(0, $subtotal - (float) $inv['discount']);
    $gst = $inv['type'] === 'gst' ? round($afterDiscount * (float) $inv['gst_percent'] / 100, 2) : 0.0;
    $total = round($afterDiscount + $gst, 2);
    return [
        'subtotal' => round($subtotal, 2),
        'gst_amount' => $gst,
        'total' => $total,
        'amount_paid' => round($paid, 2),
        'balance' => round($total - $paid, 2),
    ];
}

function get_invoice(int|string $id, bool $byToken = false): ?array
{
    $col = $byToken ? 'i.public_token = ?' : 'i.id = ?';
    $stmt = db()->prepare("SELECT i.*, p.name AS project_name, c.name AS client_name,
                                  c.company_name AS client_company, c.phone AS client_phone,
                                  c.email AS client_email, c.address AS client_address, c.city AS client_city
                           FROM invoices i
                           LEFT JOIN projects p ON p.id = i.project_id
                           LEFT JOIN clients c ON c.id = p.client_id
                           WHERE $col");
    $stmt->execute([$byToken ? (string) $id : $id]);
    $inv = $stmt->fetch();
    if (!$inv) return null;

    $stmt = db()->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id');
    $stmt->execute([(int) $inv['id']]);
    $inv['items'] = $stmt->fetchAll();

    $stmt = db()->prepare("SELECT ip.*, a.name AS account_name FROM invoice_payments ip
                           LEFT JOIN payment_accounts a ON a.id = ip.account_id
                           WHERE ip.invoice_id = ? ORDER BY ip.paid_date, ip.id");
    $stmt->execute([(int) $inv['id']]);
    $inv['payments'] = $stmt->fetchAll();

    $paid = array_sum(array_map(fn($p) => (float) $p['amount'], $inv['payments']));
    $inv = array_merge($inv, invoice_totals($inv, $inv['items'], $paid));
    $inv['locked'] = !empty($inv['password_hash']);
    return $inv;
}

function get_invoice_by_token(string $token): ?array
{
    return $token !== '' ? get_invoice($token, true) : null;
}

function query_invoices(array $f = []): array
{
    $sql = "SELECT i.id FROM invoices i
            LEFT JOIN projects p ON p.id = i.project_id
            LEFT JOIN clients c ON c.id = p.client_id WHERE 1=1";
    $params = [];
    if (!empty($f['status'])) {
        $sql .= ' AND i.status = :status';
        $params[':status'] = $f['status'];
    }
    if (!empty($f['project_id'])) {
        $sql .= ' AND i.project_id = :pid';
        $params[':pid'] = (int) $f['project_id'];
    }
    if (!empty($f['type'])) {
        $sql .= ' AND i.type = :type';
        $params[':type'] = $f['type'];
    }
    if (!empty($f['date_from'])) {
        $sql .= ' AND i.issue_date >= :dfrom';
        $params[':dfrom'] = $f['date_from'];
    }
    if (!empty($f['date_to'])) {
        $sql .= ' AND i.issue_date <= :dto';
        $params[':dto'] = $f['date_to'];
    }
    if (!empty($f['search'])) {
        $sql .= ' AND (i.invoice_no LIKE :q OR p.name LIKE :q OR c.name LIKE :q OR c.company_name LIKE :q)';
        $params[':q'] = '%' . trim($f['search']) . '%';
    }
    $sql .= ' ORDER BY i.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return array_map(fn($row) => get_invoice((int) $row['id']), $stmt->fetchAll());
}

function validate_invoice(array $in, array $items): array
{
    $errors = [];
    if (empty($in['project_id']) || !get_project((int) $in['project_id'])) $errors[] = 'A valid project is required.';
    if (!in_array($in['type'] ?? 'non_gst', ['gst', 'non_gst'], true)) $errors[] = 'Invalid invoice type.';
    if (($in['gst_percent'] ?? '') !== '' && !is_numeric($in['gst_percent'])) $errors[] = 'GST % must be a number.';
    if (($in['discount'] ?? '') !== '' && !is_numeric($in['discount'])) $errors[] = 'Discount must be a number.';
    if (trim($in['issue_date'] ?? '') === '' || !strtotime($in['issue_date'])) $errors[] = 'A valid issue date is required.';
    if (($in['due_date'] ?? '') !== '' && !strtotime($in['due_date'])) $errors[] = 'Invalid due date.';
    $valid = 0;
    foreach ($items as $it) {
        if (trim($it['description'] ?? '') === '') continue;
        if (!is_numeric($it['qty'] ?? 1) || !is_numeric($it['rate'] ?? 0)) { $errors[] = 'Item qty and rate must be numbers.'; break; }
        if (!in_array($it['category'] ?? 'other', ITEM_CATEGORIES, true)) { $errors[] = 'Invalid item category.'; break; }
        $valid++;
    }
    if (!$valid) $errors[] = 'Add at least one line item.';
    return $errors;
}

function save_invoice(array $in, array $items, ?int $id, ?int $userId): int
{
    $pdo = db();
    if ($id) {
        $fields = [
            (int) $in['project_id'], $in['type'] ?? 'non_gst',
            ($in['gst_percent'] ?? '') !== '' ? (float) $in['gst_percent'] : 18,
            trim($in['issue_date']), trim($in['due_date'] ?? '') ?: null,
            ($in['discount'] ?? '') !== '' ? (float) $in['discount'] : 0,
            trim($in['terms'] ?? ''), trim($in['notes'] ?? ''), $id,
        ];
        $pdo->prepare("UPDATE invoices SET project_id=?, type=?, gst_percent=?, issue_date=?, due_date=?,
                       discount=?, terms=?, notes=?, updated_at=datetime('now') WHERE id=?")->execute($fields);
        $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = ?')->execute([$id]);
    } else {
        $pdo->prepare("INSERT INTO invoices (invoice_no, project_id, type, gst_percent, issue_date, due_date,
                       discount, terms, notes, public_token, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                next_invoice_no(), (int) $in['project_id'], $in['type'] ?? 'non_gst',
                ($in['gst_percent'] ?? '') !== '' ? (float) $in['gst_percent'] : 18,
                trim($in['issue_date']), trim($in['due_date'] ?? '') ?: null,
                ($in['discount'] ?? '') !== '' ? (float) $in['discount'] : 0,
                trim($in['terms'] ?? ''), trim($in['notes'] ?? ''),
                bin2hex(random_bytes(20)), $userId,
            ]);
        $id = (int) $pdo->lastInsertId();
    }
    // Which payment accounts to show on the invoice (array of ids or comma string)
    if (array_key_exists('payment_account_ids', $in)) {
        $ids = is_array($in['payment_account_ids'])
            ? $in['payment_account_ids']
            : explode(',', (string) $in['payment_account_ids']);
        $ids = implode(',', array_filter(array_map('intval', $ids)));
        $pdo->prepare('UPDATE invoices SET payment_account_ids = ? WHERE id = ?')->execute([$ids, $id]);
    }

    // (Re)set password lock if provided: non-empty sets it, literal "remove" clears it
    if (array_key_exists('password', $in)) {
        if ($in['password'] === 'remove') {
            $pdo->prepare('UPDATE invoices SET password_hash = NULL WHERE id = ?')->execute([$id]);
        } elseif ((string) $in['password'] !== '') {
            $pdo->prepare('UPDATE invoices SET password_hash = ? WHERE id = ?')
                ->execute([password_hash((string) $in['password'], PASSWORD_DEFAULT), $id]);
        }
    }
    $stmt = $pdo->prepare('INSERT INTO invoice_items (invoice_id, category, description, qty, rate) VALUES (?,?,?,?,?)');
    foreach ($items as $it) {
        if (trim($it['description'] ?? '') === '') continue;
        $stmt->execute([$id, $it['category'] ?? 'other', trim($it['description']),
                        (float) ($it['qty'] ?? 1), (float) ($it['rate'] ?? 0)]);
    }
    refresh_invoice_status($id);
    return $id;
}

function refresh_invoice_status(int $id): void
{
    $inv = get_invoice($id);
    if (!$inv || $inv['status'] === 'cancelled') return;
    $status = $inv['amount_paid'] <= 0 ? 'pending'
        : ($inv['amount_paid'] + 0.01 >= $inv['total'] ? 'paid' : 'partial');
    db()->prepare("UPDATE invoices SET status = ?, updated_at = datetime('now') WHERE id = ?")->execute([$status, $id]);
}

function add_invoice_payment(int $invoiceId, array $in, ?int $userId): array
{
    $errors = [];
    if (!is_numeric($in['amount'] ?? '') || (float) $in['amount'] <= 0) $errors[] = 'Amount must be a positive number.';
    if (trim($in['paid_date'] ?? '') === '' || !strtotime($in['paid_date'])) $errors[] = 'A valid payment date is required.';
    if ($errors) return $errors;
    db()->prepare('INSERT INTO invoice_payments (invoice_id, account_id, amount, paid_date, reference, notes, created_by)
                   VALUES (?,?,?,?,?,?,?)')
        ->execute([$invoiceId, !empty($in['account_id']) ? (int) $in['account_id'] : null,
                   (float) $in['amount'], trim($in['paid_date']), trim($in['reference'] ?? ''),
                   trim($in['notes'] ?? ''), $userId]);
    refresh_invoice_status($invoiceId);
    return [];
}

// ---------- Expenses ----------

function query_expenses(array $f = []): array
{
    $sql = "SELECT ex.*, a.name AS account_name, p.name AS project_name
            FROM expenses ex
            LEFT JOIN payment_accounts a ON a.id = ex.account_id
            LEFT JOIN projects p ON p.id = ex.project_id WHERE 1=1";
    $params = [];
    if (!empty($f['month'])) { // YYYY-MM
        $sql .= " AND strftime('%Y-%m', ex.expense_date) = :month";
        $params[':month'] = $f['month'];
    }
    if (!empty($f['category'])) {
        $sql .= ' AND ex.category = :category';
        $params[':category'] = $f['category'];
    }
    if (!empty($f['project_id'])) {
        $sql .= ' AND ex.project_id = :pid';
        $params[':pid'] = (int) $f['project_id'];
    }
    if (!empty($f['account_id'])) {
        $sql .= ' AND ex.account_id = :aid';
        $params[':aid'] = (int) $f['account_id'];
    }
    if (!empty($f['search'])) {
        $sql .= ' AND (ex.description LIKE :q OR ex.paid_to LIKE :q OR p.name LIKE :q)';
        $params[':q'] = '%' . trim($f['search']) . '%';
    }
    $sql .= ' ORDER BY ex.expense_date DESC, ex.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function expenses_summary(array $rows): array
{
    $total = 0.0;
    $byCategory = [];
    foreach ($rows as $r) {
        $total += (float) $r['amount'];
        $byCategory[$r['category']] = ($byCategory[$r['category']] ?? 0) + (float) $r['amount'];
    }
    arsort($byCategory);
    return ['total' => round($total, 2), 'by_category' => array_map(fn($v) => round($v, 2), $byCategory)];
}

function validate_expense(array $in): array
{
    $errors = [];
    if (trim($in['description'] ?? '') === '') $errors[] = 'Description is required.';
    if (!is_numeric($in['amount'] ?? '') || (float) $in['amount'] <= 0) $errors[] = 'Amount must be a positive number.';
    if (trim($in['expense_date'] ?? '') === '' || !strtotime($in['expense_date'])) $errors[] = 'A valid date is required.';
    if (!in_array($in['category'] ?? 'other', EXPENSE_CATEGORIES, true)) $errors[] = 'Invalid category.';
    if (!empty($in['project_id']) && !get_project((int) $in['project_id'])) $errors[] = 'Project not found.';
    return $errors;
}

function save_expense(array $in, ?int $id, ?int $userId): int
{
    $fields = [
        trim($in['expense_date']), $in['category'] ?? 'other', trim($in['description']),
        (float) $in['amount'], trim($in['paid_to'] ?? ''),
        !empty($in['account_id']) ? (int) $in['account_id'] : null,
        !empty($in['project_id']) ? (int) $in['project_id'] : null,
        trim($in['notes'] ?? ''),
    ];
    if ($id) {
        $fields[] = $id;
        db()->prepare("UPDATE expenses SET expense_date=?, category=?, description=?, amount=?, paid_to=?,
                       account_id=?, project_id=?, notes=? WHERE id=?")->execute($fields);
        return $id;
    }
    $fields[] = $userId;
    db()->prepare("INSERT INTO expenses (expense_date, category, description, amount, paid_to, account_id, project_id, notes, created_by)
                   VALUES (?,?,?,?,?,?,?,?,?)")->execute($fields);
    return (int) db()->lastInsertId();
}

// ---------- Quotations ----------

function next_quote_no(): string
{
    $prefix = settings_all()['invoice_prefix'] ?? 'INV';
    $year = date('Y');
    $count = (int) db()->query("SELECT COUNT(*) FROM quotations WHERE quote_no LIKE '$prefix-Q-$year-%'")->fetchColumn();
    return sprintf('%s-Q-%s-%03d', $prefix, $year, $count + 1);
}

function get_quotation(int|string $id, bool $byToken = false): ?array
{
    $col = $byToken ? 'q.public_token = ?' : 'q.id = ?';
    $stmt = db()->prepare("SELECT q.*, c.name AS c_name, c.company_name AS c_company,
                                  c.phone AS c_phone, c.email AS c_email
                           FROM quotations q LEFT JOIN clients c ON c.id = q.client_id
                           WHERE $col");
    $stmt->execute([$id]);
    $q = $stmt->fetch();
    if (!$q) return null;
    $stmt = db()->prepare('SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id');
    $stmt->execute([(int) $q['id']]);
    $q['items'] = $stmt->fetchAll();
    // Display name: linked client wins over free-text prospect
    $q['display_name']    = $q['c_name'] ?: $q['prospect_name'];
    $q['display_company'] = $q['c_company'] ?: $q['prospect_company'];
    $q['display_phone']   = $q['c_phone'] ?: $q['prospect_phone'];
    $q['display_email']   = $q['c_email'] ?: $q['prospect_email'];
    $q = array_merge($q, invoice_totals($q, $q['items'], 0));
    $q['expired'] = $q['status'] === 'pending' && $q['valid_until']
        && strtotime($q['valid_until']) < strtotime(date('Y-m-d'));
    return $q;
}

function get_quotation_by_token(string $token): ?array
{
    return $token !== '' ? get_quotation($token, true) : null;
}

function query_quotations(array $f = []): array
{
    $sql = "SELECT q.id FROM quotations q LEFT JOIN clients c ON c.id = q.client_id WHERE 1=1";
    $params = [];
    if (!empty($f['status'])) {
        $sql .= ' AND q.status = :status';
        $params[':status'] = $f['status'];
    }
    if (!empty($f['search'])) {
        $sql .= ' AND (q.quote_no LIKE :q OR q.title LIKE :q OR q.prospect_name LIKE :q OR c.name LIKE :q OR c.company_name LIKE :q)';
        $params[':q'] = '%' . trim($f['search']) . '%';
    }
    $sql .= ' ORDER BY q.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return array_map(fn($row) => get_quotation((int) $row['id']), $stmt->fetchAll());
}

function validate_quotation(array $in, array $items): array
{
    $errors = [];
    if (trim($in['title'] ?? '') === '') $errors[] = 'Project title is required.';
    if (empty($in['client_id']) && trim($in['prospect_name'] ?? '') === '') $errors[] = 'Pick a client or enter a prospect name.';
    if (!empty($in['client_id']) && !get_client((int) $in['client_id'])) $errors[] = 'Client not found.';
    if (!in_array($in['type'] ?? 'non_gst', ['gst', 'non_gst'], true)) $errors[] = 'Invalid type.';
    if (($in['gst_percent'] ?? '') !== '' && !is_numeric($in['gst_percent'])) $errors[] = 'GST % must be a number.';
    if (($in['discount'] ?? '') !== '' && !is_numeric($in['discount'])) $errors[] = 'Discount must be a number.';
    if (trim($in['issue_date'] ?? '') === '' || !strtotime($in['issue_date'])) $errors[] = 'A valid issue date is required.';
    if (($in['valid_until'] ?? '') !== '' && !strtotime($in['valid_until'])) $errors[] = 'Invalid validity date.';
    $valid = 0;
    foreach ($items as $it) {
        if (trim($it['description'] ?? '') === '') continue;
        if (!is_numeric($it['qty'] ?? 1) || !is_numeric($it['rate'] ?? 0)) { $errors[] = 'Item qty and rate must be numbers.'; break; }
        $valid++;
    }
    if (!$valid) $errors[] = 'Add at least one line item.';
    return $errors;
}

function save_quotation(array $in, array $items, ?int $id, ?int $userId): int
{
    $pdo = db();
    $fields = [
        trim($in['title']), trim($in['scope'] ?? ''),
        !empty($in['client_id']) ? (int) $in['client_id'] : null,
        trim($in['prospect_name'] ?? ''), trim($in['prospect_company'] ?? ''),
        trim($in['prospect_phone'] ?? ''), trim($in['prospect_email'] ?? ''),
        $in['type'] ?? 'non_gst',
        ($in['gst_percent'] ?? '') !== '' ? (float) $in['gst_percent'] : 18,
        ($in['discount'] ?? '') !== '' ? (float) $in['discount'] : 0,
        trim($in['issue_date']), trim($in['valid_until'] ?? '') ?: null,
        trim($in['terms'] ?? ''), trim($in['notes'] ?? ''),
    ];
    if ($id) {
        $fields[] = $id;
        $pdo->prepare("UPDATE quotations SET title=?, scope=?, client_id=?, prospect_name=?, prospect_company=?,
                       prospect_phone=?, prospect_email=?, type=?, gst_percent=?, discount=?, issue_date=?,
                       valid_until=?, terms=?, notes=?, updated_at=datetime('now') WHERE id=?")->execute($fields);
        $pdo->prepare('DELETE FROM quotation_items WHERE quotation_id = ?')->execute([$id]);
    } else {
        array_unshift($fields, next_quote_no());
        $fields[] = bin2hex(random_bytes(20));
        $fields[] = $userId;
        $pdo->prepare("INSERT INTO quotations (quote_no, title, scope, client_id, prospect_name, prospect_company,
                       prospect_phone, prospect_email, type, gst_percent, discount, issue_date, valid_until,
                       terms, notes, public_token, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute($fields);
        $id = (int) $pdo->lastInsertId();
    }
    $stmt = $pdo->prepare('INSERT INTO quotation_items (quotation_id, category, description, qty, rate) VALUES (?,?,?,?,?)');
    foreach ($items as $it) {
        if (trim($it['description'] ?? '') === '') continue;
        $stmt->execute([$id, $it['category'] ?? 'other', trim($it['description']),
                        (float) ($it['qty'] ?? 1), (float) ($it['rate'] ?? 0)]);
    }
    return $id;
}

function convert_quotation_to_project(int $id, ?int $userId): array
{
    $q = get_quotation($id);
    if (!$q) return [null, ['Quotation not found.']];
    if ($q['project_id']) return [(int) $q['project_id'], []];

    $clientId = $q['client_id'] ? (int) $q['client_id'] : null;
    if (!$clientId && $q['prospect_name']) {
        // The prospect becomes a real client
        $clientId = save_client([
            'name' => $q['prospect_name'],
            'phone' => $q['prospect_phone'] ?: '-',
            'email' => $q['prospect_email'],
            'company_name' => $q['prospect_company'],
        ], null, $userId);
    }
    $projectId = save_project([
        'name' => $q['title'],
        'client_id' => $clientId,
        'description' => $q['scope'],
        'status' => 'planning',
        'start_date' => date('Y-m-d'),
        'budget' => $q['total'],
    ], null, $userId);
    db()->prepare("UPDATE quotations SET status='accepted', project_id=?, updated_at=datetime('now') WHERE id=?")
        ->execute([$projectId, $id]);
    return [$projectId, []];
}

// ---------- Amount in words (Indian numbering: lakh / crore) ----------

function inr_words(float $amount): string
{
    $rupees = (int) floor($amount);
    $paise = (int) round(($amount - $rupees) * 100);

    $two = function (int $n) {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven',
                 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        if ($n < 20) return $ones[$n];
        return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
    };

    $words = function (int $n) use ($two, &$words) {
        if ($n === 0) return 'Zero';
        $parts = [];
        foreach ([['Crore', 10000000], ['Lakh', 100000], ['Thousand', 1000], ['Hundred', 100]] as [$label, $div]) {
            if ($n >= $div) {
                $chunk = intdiv($n, $div);
                $n %= $div;
                $parts[] = ($div === 10000000 && $chunk > 99 ? $words($chunk) : ($chunk > 99 ? $two(intdiv($chunk, 100)) . ' Hundred ' . $two($chunk % 100) : $two($chunk))) . ' ' . $label;
            }
        }
        if ($n > 0) $parts[] = $two($n);
        return trim(implode(' ', $parts));
    };

    $out = 'Rupees ' . $words($rupees);
    if ($paise > 0) $out .= ' and ' . $two($paise) . ' Paise';
    return $out . ' Only';
}

function dashboard_stats(): array
{
    $byStatus = db()->query("SELECT status, COUNT(*) c FROM leads GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $today = db()->query("SELECT fu.*, l.name AS lead_name, l.phone AS lead_phone, l.company_name
                          FROM lead_followups fu JOIN leads l ON l.id = fu.lead_id
                          WHERE fu.status = 'pending' AND date(fu.followup_date) <= date('now','localtime')
                          ORDER BY fu.followup_date ASC")->fetchAll();
    $dueTasks = db()->query("SELECT t.*, p.name AS project_name, e.name AS assignee_name
                             FROM project_tasks t JOIN projects p ON p.id = t.project_id
                             LEFT JOIN employees e ON e.id = t.assigned_to
                             WHERE t.status != 'done' AND t.due_date IS NOT NULL
                                   AND date(t.due_date) <= date('now','localtime')
                             ORDER BY t.due_date ASC")->fetchAll();
    $openInvoices = array_values(array_filter(query_invoices(),
        fn($i) => in_array($i['status'], ['pending', 'partial'], true)));
    $overdueInvoices = array_values(array_filter($openInvoices,
        fn($i) => $i['due_date'] && strtotime($i['due_date']) < strtotime(date('Y-m-d'))));
    $urgentRenewals = query_services(['expiring_days' => 7]);

    return [
        'total'           => (int) db()->query('SELECT COUNT(*) FROM leads')->fetchColumn(),
        'total_clients'   => (int) db()->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
        'total_projects'  => (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
        'active_projects' => (int) db()->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn(),
        'total_employees' => (int) db()->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
        'expiring_soon'   => query_services(['expiring_days' => 30]),
        'urgent_renewals' => $urgentRenewals,
        'due_tasks'       => $dueTasks,
        'overdue_invoices' => $overdueInvoices,
        'month_expenses'  => (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM expenses
                              WHERE strftime('%Y-%m', expense_date) = strftime('%Y-%m','now','localtime')")->fetchColumn(),
        'pending_quotes'  => (int) db()->query("SELECT COUNT(*) FROM quotations WHERE status = 'pending'")->fetchColumn(),
        'open_invoices'   => $openInvoices,
        'invoice_outstanding' => round(array_sum(array_column($openInvoices, 'balance')), 2),
        'by_status'       => $byStatus,
        'today_followups' => $today,
        'attention_count' => count($today) + count($dueTasks) + count($overdueInvoices) + count($urgentRenewals),
    ];
}

// ---------- Global search across leads / clients / projects / invoices / quotations ----------

function global_search(string $q): array
{
    $q = trim($q);
    if ($q === '') return ['leads' => [], 'clients' => [], 'projects' => [], 'invoices' => [], 'quotations' => []];
    $like = '%' . $q . '%';

    $leads = db()->prepare("SELECT id, name, phone, company_name FROM leads
                            WHERE name LIKE ? OR phone LIKE ? OR company_name LIKE ? OR email LIKE ? LIMIT 8");
    $leads->execute([$like, $like, $like, $like]);

    $clients = db()->prepare("SELECT id, name, phone, company_name FROM clients
                              WHERE name LIKE ? OR phone LIKE ? OR company_name LIKE ? OR email LIKE ? LIMIT 8");
    $clients->execute([$like, $like, $like, $like]);

    $projects = db()->prepare("SELECT p.id, p.name, c.name AS client_name FROM projects p
                               LEFT JOIN clients c ON c.id = p.client_id
                               WHERE p.name LIKE ? OR c.name LIKE ? LIMIT 8");
    $projects->execute([$like, $like]);

    $invoices = db()->prepare("SELECT i.id, i.invoice_no, c.name AS client_name FROM invoices i
                               LEFT JOIN projects p ON p.id = i.project_id
                               LEFT JOIN clients c ON c.id = p.client_id
                               WHERE i.invoice_no LIKE ? OR c.name LIKE ? LIMIT 8");
    $invoices->execute([$like, $like]);

    $quotations = db()->prepare("SELECT id, quote_no, title, prospect_name FROM quotations
                                 WHERE quote_no LIKE ? OR title LIKE ? OR prospect_name LIKE ? LIMIT 8");
    $quotations->execute([$like, $like, $like]);

    return [
        'leads'      => $leads->fetchAll(),
        'clients'    => $clients->fetchAll(),
        'projects'   => $projects->fetchAll(),
        'invoices'   => $invoices->fetchAll(),
        'quotations' => $quotations->fetchAll(),
    ];
}
