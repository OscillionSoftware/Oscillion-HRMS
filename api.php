<?php
// REST API for the mobile app. Routed from index.php for any /api/* path.
// Auth: POST /api/login returns a bearer token; all other endpoints require
// an "Authorization: Bearer <token>" header.

require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
    exit;
}

$path  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path  = preg_replace('#^/api#', '', $path) ?: '/';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

function respond(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function fail(string $message, int $code = 400): never
{
    respond(['success' => false, 'message' => $message], $code);
}

// ---- Public: login ----
if ($path === '/login' && $method === 'POST') {
    $user = attempt_login($input['email'] ?? '', $input['password'] ?? '');
    if (!$user) fail('Invalid email or password.', 401);
    respond([
        'success' => true,
        'token'   => issue_api_token((int) $user['id']),
        'user'    => ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
    ]);
}

// ---- Everything below requires a token ----
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = preg_match('/Bearer\s+(\S+)/', $authHeader, $m) ? $m[1] : null;
$user = user_by_token($token);
if (!$user) fail('Unauthenticated. Provide a valid Bearer token.', 401);
$userId = (int) $user['id'];

// GET /meta — dropdown values for filters/forms
if ($path === '/meta' && $method === 'GET') {
    respond([
        'success'    => true,
        'statuses'   => LEAD_STATUSES,
        'priorities' => LEAD_PRIORITIES,
        'behaviours' => LEAD_BEHAVIOURS,
        'followup_types' => FOLLOWUP_TYPES,
        'client_statuses' => CLIENT_STATUSES,
        'project_statuses' => PROJECT_STATUSES,
        'employee_statuses' => EMPLOYEE_STATUSES,
    ]);
}

// GET /dashboard
if ($path === '/dashboard' && $method === 'GET') {
    respond(['success' => true] + dashboard_stats());
}

// GET /projects (with filters) | POST /projects
if ($path === '/projects') {
    if ($method === 'GET') {
        respond(['success' => true, 'projects' => query_projects([
            'search'    => $_GET['search'] ?? '',
            'status'    => $_GET['status'] ?? '',
            'client_id' => $_GET['client_id'] ?? '',
        ])]);
    }
    if ($method === 'POST') {
        $errors = validate_project($input);
        if ($errors) fail(implode(' ', $errors), 422);
        $id = save_project($input, null, $userId);
        respond(['success' => true, 'project' => get_project($id)], 201);
    }
}

// GET /quotations | POST /quotations
if ($path === '/quotations') {
    if ($method === 'GET') {
        respond(['success' => true, 'quotations' => query_quotations([
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ])]);
    }
    if ($method === 'POST') {
        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $errors = validate_quotation($input, $items);
        if ($errors) fail(implode(' ', $errors), 422);
        $id = save_quotation($input, $items, null, $userId);
        respond(['success' => true, 'quotation' => get_quotation($id)], 201);
    }
}

// GET|PUT /quotations/{id}
if (preg_match('#^/quotations/(\d+)$#', $path, $m)) {
    $quotation = get_quotation((int) $m[1]);
    if (!$quotation) fail('Quotation not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'quotation' => $quotation]);
    }
    if ($method === 'PUT') {
        if (isset($input['status']) && count($input) === 1) {
            if (!in_array($input['status'], QUOTE_STATUSES, true)) fail('Invalid status.', 422);
            db()->prepare("UPDATE quotations SET status=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$input['status'], (int) $quotation['id']]);
            respond(['success' => true, 'quotation' => get_quotation((int) $quotation['id'])]);
        }
        $items = is_array($input['items'] ?? null) ? $input['items'] : $quotation['items'];
        $merged = array_merge($quotation, $input);
        $errors = validate_quotation($merged, $items);
        if ($errors) fail(implode(' ', $errors), 422);
        save_quotation($merged, $items, (int) $quotation['id'], $userId);
        respond(['success' => true, 'quotation' => get_quotation((int) $quotation['id'])]);
    }
}

// POST /quotations/{id}/convert — accepted quote becomes a project (+client)
if (preg_match('#^/quotations/(\d+)/convert$#', $path, $m) && $method === 'POST') {
    [$projectId, $errors] = convert_quotation_to_project((int) $m[1], $userId);
    if ($errors) fail(implode(' ', $errors), 422);
    respond(['success' => true, 'project_id' => $projectId,
             'quotation' => get_quotation((int) $m[1])], 201);
}

// GET /expenses | POST /expenses
if ($path === '/expenses') {
    if ($method === 'GET') {
        $rows = query_expenses([
            'month'      => $_GET['month'] ?? '',
            'category'   => $_GET['category'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'search'     => $_GET['search'] ?? '',
        ]);
        respond(['success' => true, 'expenses' => $rows, 'summary' => expenses_summary($rows)]);
    }
    if ($method === 'POST') {
        $errors = validate_expense($input);
        if ($errors) fail(implode(' ', $errors), 422);
        $id = save_expense($input, null, $userId);
        respond(['success' => true, 'id' => $id], 201);
    }
}

// PUT|DELETE /expenses/{id}
if (preg_match('#^/expenses/(\d+)$#', $path, $m)) {
    $stmt = db()->prepare('SELECT * FROM expenses WHERE id = ?');
    $stmt->execute([(int) $m[1]]);
    $expense = $stmt->fetch();
    if (!$expense) fail('Expense not found.', 404);
    if ($method === 'PUT') {
        $merged = array_merge($expense, $input);
        $errors = validate_expense($merged);
        if ($errors) fail(implode(' ', $errors), 422);
        save_expense($merged, (int) $expense['id'], $userId);
        respond(['success' => true]);
    }
    if ($method === 'DELETE') {
        db()->prepare('DELETE FROM expenses WHERE id = ?')->execute([(int) $expense['id']]);
        respond(['success' => true]);
    }
}

// GET /accounts — payment accounts | POST to add
if ($path === '/accounts') {
    if ($method === 'GET') {
        respond(['success' => true, 'accounts' => query_accounts()]);
    }
    if ($method === 'POST') {
        if (trim($input['name'] ?? '') === '') fail('Account name is required.', 422);
        $id = save_account($input + ['active' => $input['active'] ?? 1], null);
        respond(['success' => true, 'accounts' => query_accounts()], 201);
    }
}

// DELETE /accounts/{id}
if (preg_match('#^/accounts/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $aid = (int) $m[1];
    db()->prepare('UPDATE invoice_payments SET account_id = NULL WHERE account_id = ?')->execute([$aid]);
    db()->prepare('UPDATE expenses SET account_id = NULL WHERE account_id = ?')->execute([$aid]);
    $stmt = db()->prepare('DELETE FROM payment_accounts WHERE id = ?');
    $stmt->execute([$aid]);
    if (!$stmt->rowCount()) fail('Account not found.', 404);
    respond(['success' => true]);
}

// GET /settings (company profile for invoices)
if ($path === '/settings' && $method === 'GET') {
    respond(['success' => true, 'settings' => settings_all()]);
}

// GET /invoices | POST /invoices
if ($path === '/invoices') {
    if ($method === 'GET') {
        respond(['success' => true, 'invoices' => query_invoices([
            'status'     => $_GET['status'] ?? '',
            'search'     => $_GET['search'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
        ])]);
    }
    if ($method === 'POST') {
        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $errors = validate_invoice($input, $items);
        if ($errors) fail(implode(' ', $errors), 422);
        $id = save_invoice($input, $items, null, $userId);
        respond(['success' => true, 'invoice' => get_invoice($id)], 201);
    }
}

// GET|PUT /invoices/{id}
if (preg_match('#^/invoices/(\d+)$#', $path, $m)) {
    $invoice = get_invoice((int) $m[1]);
    if (!$invoice) fail('Invoice not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'invoice' => $invoice]);
    }
    if ($method === 'PUT') {
        // Cancel / reopen shortcut
        if (isset($input['status']) && count($input) === 1) {
            if (!in_array($input['status'], ['cancelled', 'pending'], true)) fail('Only cancel/reopen allowed here.', 422);
            db()->prepare("UPDATE invoices SET status=?, updated_at=datetime('now') WHERE id=?")
                ->execute([$input['status'], (int) $invoice['id']]);
            refresh_invoice_status((int) $invoice['id']);
            respond(['success' => true, 'invoice' => get_invoice((int) $invoice['id'])]);
        }
        $items = is_array($input['items'] ?? null) ? $input['items'] : $invoice['items'];
        $merged = array_merge($invoice, $input);
        $errors = validate_invoice($merged, $items);
        if ($errors) fail(implode(' ', $errors), 422);
        save_invoice($merged, $items, (int) $invoice['id'], $userId);
        respond(['success' => true, 'invoice' => get_invoice((int) $invoice['id'])]);
    }
}

// DELETE /invoices/{id} — removes invoice with its items and payments
if (preg_match('#^/invoices/(\d+)$#', $path, $m) && $method === 'DELETE') {
    $invoice = get_invoice((int) $m[1]);
    if (!$invoice) fail('Invoice not found.', 404);
    db()->prepare('DELETE FROM invoices WHERE id = ?')->execute([(int) $invoice['id']]);
    respond(['success' => true]);
}

// POST /invoices/{id}/payments
if (preg_match('#^/invoices/(\d+)/payments$#', $path, $m) && $method === 'POST') {
    $invoice = get_invoice((int) $m[1]);
    if (!$invoice) fail('Invoice not found.', 404);
    $errors = add_invoice_payment((int) $invoice['id'], $input, $userId);
    if ($errors) fail(implode(' ', $errors), 422);
    respond(['success' => true, 'invoice' => get_invoice((int) $invoice['id'])], 201);
}

// GET /services — all renewals across projects (?expiring_days=60&type=&search=)
if ($path === '/services' && $method === 'GET') {
    respond(['success' => true, 'services' => query_services([
        'search'        => $_GET['search'] ?? '',
        'type'          => $_GET['type'] ?? '',
        'expiring_days' => $_GET['expiring_days'] ?? '',
    ])]);
}

// GET|POST /projects/{id}/services
if (preg_match('#^/projects/(\d+)/services$#', $path, $m)) {
    if (!get_project((int) $m[1])) fail('Project not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'services' => query_services(['project_id' => (int) $m[1]])]);
    }
    if ($method === 'POST') {
        $errors = validate_service($input);
        if ($errors) fail(implode(' ', $errors), 422);
        save_service($input, null, $userId, (int) $m[1]);
        respond(['success' => true, 'services' => query_services(['project_id' => (int) $m[1]])], 201);
    }
}

// PUT /services/{id} — e.g. renew: push expiry_date forward
if (preg_match('#^/services/(\d+)$#', $path, $m) && $method === 'PUT') {
    $service = get_service((int) $m[1]);
    if (!$service) fail('Service not found.', 404);
    $merged = array_merge($service, $input);
    $errors = validate_service($merged);
    if ($errors) fail(implode(' ', $errors), 422);
    save_service($merged, (int) $service['id'], $userId);
    respond(['success' => true, 'service' => get_service((int) $service['id'])]);
}

// GET|POST /projects/{id}/credentials
if (preg_match('#^/projects/(\d+)/credentials$#', $path, $m)) {
    if (!get_project((int) $m[1])) fail('Project not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'credentials' => get_project_credentials((int) $m[1])]);
    }
    if ($method === 'POST') {
        $errors = validate_credential($input);
        if ($errors) fail(implode(' ', $errors), 422);
        save_credential($input, null, $userId, (int) $m[1]);
        respond(['success' => true, 'credentials' => get_project_credentials((int) $m[1])], 201);
    }
}

// PUT|DELETE /credentials/{id}
if (preg_match('#^/credentials/(\d+)$#', $path, $m)) {
    $cred = get_credential((int) $m[1]);
    if (!$cred) fail('Credential not found.', 404);
    if ($method === 'PUT') {
        $merged = array_merge($cred, $input);
        $errors = validate_credential($merged);
        if ($errors) fail(implode(' ', $errors), 422);
        save_credential($merged, (int) $cred['id'], $userId);
        respond(['success' => true, 'credential' => get_credential((int) $cred['id'])]);
    }
    if ($method === 'DELETE') {
        db()->prepare('DELETE FROM project_credentials WHERE id = ?')->execute([(int) $cred['id']]);
        respond(['success' => true]);
    }
}

// GET|POST /projects/{id}/tasks
if (preg_match('#^/projects/(\d+)/tasks$#', $path, $m)) {
    if (!get_project((int) $m[1])) fail('Project not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'tasks' => get_project_tasks((int) $m[1])]);
    }
    if ($method === 'POST') {
        $errors = validate_task($input);
        if ($errors) fail(implode(' ', $errors), 422);
        save_task($input, null, $userId, (int) $m[1]);
        respond(['success' => true, 'tasks' => get_project_tasks((int) $m[1])], 201);
    }
}

// PUT /tasks/{id} — update any fields (commonly just status)
if (preg_match('#^/tasks/(\d+)$#', $path, $m) && $method === 'PUT') {
    $task = get_task((int) $m[1]);
    if (!$task) fail('Task not found.', 404);
    $merged = array_merge($task, $input);
    $errors = validate_task($merged);
    if ($errors) fail(implode(' ', $errors), 422);
    save_task($merged, (int) $task['id'], $userId);
    respond(['success' => true, 'task' => get_task((int) $task['id'])]);
}

// GET|PUT /projects/{id}
if (preg_match('#^/projects/(\d+)$#', $path, $m)) {
    $project = get_project((int) $m[1]);
    if (!$project) fail('Project not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'project' => $project,
                 'tasks' => get_project_tasks((int) $project['id'])]);
    }
    if ($method === 'PUT') {
        $merged = array_merge($project, $input);
        $errors = validate_project($merged);
        if ($errors) fail(implode(' ', $errors), 422);
        save_project($merged, (int) $project['id'], $userId);
        respond(['success' => true, 'project' => get_project((int) $project['id'])]);
    }
}

// GET /employees (with filters) | POST /employees
if ($path === '/employees') {
    if ($method === 'GET') {
        respond(['success' => true, 'employees' => query_employees([
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
        ])]);
    }
    if ($method === 'POST') {
        $errors = validate_employee($input);
        if ($errors) fail(implode(' ', $errors), 422);
        $id = save_employee($input, null, $userId);
        respond(['success' => true, 'employee' => get_employee($id)], 201);
    }
}

// GET|PUT /employees/{id}
if (preg_match('#^/employees/(\d+)$#', $path, $m)) {
    $employee = get_employee((int) $m[1]);
    if (!$employee) fail('Employee not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'employee' => $employee]);
    }
    if ($method === 'PUT') {
        $merged = array_merge($employee, $input);
        $errors = validate_employee($merged);
        if ($errors) fail(implode(' ', $errors), 422);
        save_employee($merged, (int) $employee['id'], $userId);
        respond(['success' => true, 'employee' => get_employee((int) $employee['id'])]);
    }
}

// GET /clients (with filters) | POST /clients
if ($path === '/clients') {
    if ($method === 'GET') {
        respond(['success' => true, 'clients' => query_clients([
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
        ])]);
    }
    if ($method === 'POST') {
        $errors = validate_client($input);
        if ($errors) fail(implode(' ', $errors), 422);
        $id = save_client($input, null, $userId);
        respond(['success' => true, 'client' => get_client($id)], 201);
    }
}

// GET|PUT /clients/{id}
if (preg_match('#^/clients/(\d+)$#', $path, $m)) {
    $client = get_client((int) $m[1]);
    if (!$client) fail('Client not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'client' => $client]);
    }
    if ($method === 'PUT') {
        $merged = array_merge($client, $input);
        $errors = validate_client($merged);
        if ($errors) fail(implode(' ', $errors), 422);
        save_client($merged, (int) $client['id'], $userId);
        respond(['success' => true, 'client' => get_client((int) $client['id'])]);
    }
}

// GET /leads (with filters) | POST /leads
if ($path === '/leads') {
    if ($method === 'GET') {
        respond(['success' => true, 'leads' => query_leads(lead_filters_from($_GET))]);
    }
    if ($method === 'POST') {
        $errors = validate_lead($input);
        if ($errors) fail(implode(' ', $errors), 422);
        $id = save_lead($input, null, $userId);
        respond(['success' => true, 'lead' => get_lead($id)], 201);
    }
}

// GET|PUT /leads/{id}
if (preg_match('#^/leads/(\d+)$#', $path, $m)) {
    $lead = get_lead((int) $m[1]);
    if (!$lead) fail('Lead not found.', 404);
    if ($method === 'GET') {
        respond(['success' => true, 'lead' => $lead, 'followups' => get_followups((int) $lead['id'])]);
    }
    if ($method === 'PUT') {
        $merged = array_merge($lead, $input);
        $errors = validate_lead($merged);
        if ($errors) fail(implode(' ', $errors), 422);
        save_lead($merged, (int) $lead['id'], $userId);
        respond(['success' => true, 'lead' => get_lead((int) $lead['id'])]);
    }
}

// POST /leads/{id}/followups
if (preg_match('#^/leads/(\d+)/followups$#', $path, $m) && $method === 'POST') {
    if (!get_lead((int) $m[1])) fail('Lead not found.', 404);
    [$id, $errors] = add_followup((int) $m[1], $input, $userId);
    if ($errors) fail(implode(' ', $errors), 422);
    respond(['success' => true, 'followups' => get_followups((int) $m[1])], 201);
}

// PUT /followups/{id} — mark done/pending
if (preg_match('#^/followups/(\d+)$#', $path, $m) && $method === 'PUT') {
    $status = ($input['status'] ?? '') === 'done' ? 'done' : 'pending';
    $stmt = db()->prepare('UPDATE lead_followups SET status = ? WHERE id = ?');
    $stmt->execute([$status, (int) $m[1]]);
    if (!$stmt->rowCount()) fail('Follow-up not found.', 404);
    respond(['success' => true]);
}

fail('Endpoint not found.', 404);
