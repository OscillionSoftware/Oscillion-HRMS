<?php
// Entry point + router. Run with:  php -S 0.0.0.0:8000 index.php  (from portal/)
// Routes /api/* to the JSON API, everything else to web pages (session auth).

// Suppress error output to the browser in production; still logged server-side.
// Toggle by setting the APP_DEBUG environment variable (e.g. in your host's
// PHP config) — off by default so stack traces never leak to visitors.
ini_set('display_errors', getenv('APP_DEBUG') ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Never serve the database, the encryption key, or any dotfile as a static
// asset — the database and PHP sources must only ever be reached through
// this router, never downloaded directly.
if (str_starts_with($uri, '/data/') || preg_match('#/\.#', $uri)) {
    http_response_code(404);
    exit('Not found');
}

// Let the built-in server handle real static files (css, etc.)
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

if (str_starts_with($uri, '/api')) {
    require __DIR__ . '/api.php';
    exit;
}

session_start();
require_once __DIR__ . '/helpers.php';

$user = current_web_user();
$page = trim($uri, '/') ?: 'dashboard';

// ---- Public quotation page (no login required) ----
if (preg_match('#^q/([a-f0-9]{20,})$#', $page, $m)) {
    $quotation = get_quotation_by_token($m[1]);
    if (!$quotation) { http_response_code(404); exit('Quotation not found'); }
    $accepted = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'accept'
        && $quotation['status'] === 'pending' && !$quotation['expired']) {
        db()->prepare("UPDATE quotations SET status='accepted', updated_at=datetime('now') WHERE id=?")
            ->execute([(int) $quotation['id']]);
        $quotation = get_quotation((int) $quotation['id']);
        $accepted = true;
    }
    $settings = settings_all();
    require __DIR__ . '/views/quotation_public.php';
    exit;
}

// ---- Public invoice page (no login required) ----
if (preg_match('#^i/([a-f0-9]{20,})$#', $page, $m)) {
    $invoice = get_invoice_by_token($m[1]);
    if (!$invoice) { http_response_code(404); exit('Invoice not found'); }
    $unlocked = empty($invoice['password_hash']) || !empty($_SESSION['inv_ok_' . $invoice['id']]);
    $pwError = null;
    if (!$unlocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (password_verify($_POST['password'] ?? '', $invoice['password_hash'])) {
            $_SESSION['inv_ok_' . $invoice['id']] = true;
            $unlocked = true;
        } else {
            $pwError = 'Wrong password. Please try again.';
        }
    }
    $settings = settings_all();
    $accounts = query_accounts(true);
    require __DIR__ . '/views/invoice_public.php';
    exit;
}

// ---- Login / logout ----
if ($page === 'logout') {
    session_destroy();
    header('Location: /login');
    exit;
}

if ($page === 'login') {
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($u) {
            $_SESSION['user_id'] = (int) $u['id'];
            header('Location: /');
            exit;
        }
        $error = 'Invalid email or password.';
    }
    require __DIR__ . '/views/login.php';
    exit;
}

if (!$user) {
    header('Location: /login');
    exit;
}

// ---- Authenticated pages ----
switch (true) {
    case $page === 'dashboard':
        $stats = dashboard_stats();
        render('dashboard', compact('user', 'stats'));
        break;

    case $page === 'leads':
        $filters = lead_filters_from($_GET);
        $leads = query_leads($filters);
        render('leads', compact('user', 'leads', 'filters'));
        break;

    case $page === 'leads/new' || (bool) preg_match('#^leads/(\d+)/edit$#', $page, $m):
        $lead = isset($m[1]) ? get_lead((int) $m[1]) : null;
        if (isset($m[1]) && !$lead) { http_response_code(404); exit('Lead not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = validate_lead($_POST);
            if (!$errors) {
                $id = save_lead($_POST, $lead ? (int) $lead['id'] : null, (int) $user['id']);
                header("Location: /leads/$id");
                exit;
            }
            $lead = array_merge($lead ?? [], $_POST);
        }
        render('lead_form', compact('user', 'lead', 'errors'));
        break;

    case (bool) preg_match('#^leads/(\d+)$#', $page, $m):
        $lead = get_lead((int) $m[1]);
        if (!$lead) { http_response_code(404); exit('Lead not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['_action'] ?? '') === 'add_followup') {
                [, $errors] = add_followup((int) $lead['id'], $_POST, (int) $user['id']);
            } elseif (($_POST['_action'] ?? '') === 'followup_done') {
                db()->prepare("UPDATE lead_followups SET status='done' WHERE id=? AND lead_id=?")
                    ->execute([(int) $_POST['followup_id'], (int) $lead['id']]);
            }
            if (!$errors) { header('Location: /leads/' . $lead['id']); exit; }
            $lead = get_lead((int) $lead['id']);
        }
        $followups = get_followups((int) $lead['id']);
        render('lead_view', compact('user', 'lead', 'followups', 'errors'));
        break;

    case $page === 'clients':
        $filters = ['search' => trim($_GET['search'] ?? ''), 'status' => $_GET['status'] ?? ''];
        $clients = query_clients($filters);
        render('clients', compact('user', 'clients', 'filters'));
        break;

    case $page === 'clients/new' || (bool) preg_match('#^clients/(\d+)/edit$#', $page, $m):
        $client = isset($m[1]) ? get_client((int) $m[1]) : null;
        if (isset($m[1]) && !$client) { http_response_code(404); exit('Client not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = validate_client($_POST);
            if (!$errors) {
                $id = save_client($_POST, $client ? (int) $client['id'] : null, (int) $user['id']);
                header("Location: /clients/$id");
                exit;
            }
            $client = array_merge($client ?? [], $_POST);
        }
        render('client_form', compact('user', 'client', 'errors'));
        break;

    case (bool) preg_match('#^clients/(\d+)$#', $page, $m):
        $client = get_client((int) $m[1]);
        if (!$client) { http_response_code(404); exit('Client not found'); }
        render('client_view', compact('user', 'client'));
        break;

    case $page === 'projects':
        $filters = ['search' => trim($_GET['search'] ?? ''), 'status' => $_GET['status'] ?? '', 'client_id' => $_GET['client_id'] ?? ''];
        $projects = query_projects($filters);
        render('projects', compact('user', 'projects', 'filters'));
        break;

    case $page === 'projects/new' || (bool) preg_match('#^projects/(\d+)/edit$#', $page, $m):
        $project = isset($m[1]) ? get_project((int) $m[1]) : null;
        if (isset($m[1]) && !$project) { http_response_code(404); exit('Project not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = validate_project($_POST);
            if (!$errors) {
                $id = save_project($_POST, $project ? (int) $project['id'] : null, (int) $user['id']);
                header("Location: /projects/$id");
                exit;
            }
            $project = array_merge($project ?? [], $_POST);
        }
        $clients = query_clients([]);
        render('project_form', compact('user', 'project', 'errors', 'clients'));
        break;

    case (bool) preg_match('#^projects/(\d+)$#', $page, $m):
        $project = get_project((int) $m[1]);
        if (!$project) { http_response_code(404); exit('Project not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['_action'] ?? '') === 'add_service') {
                $errors = validate_service($_POST);
                if (!$errors) save_service($_POST, null, (int) $user['id'], (int) $project['id']);
            } elseif (($_POST['_action'] ?? '') === 'add_credential') {
                $errors = validate_credential($_POST);
                if (!$errors) save_credential($_POST, null, (int) $user['id'], (int) $project['id']);
            } elseif (($_POST['_action'] ?? '') === 'delete_credential') {
                db()->prepare('DELETE FROM project_credentials WHERE id = ? AND project_id = ?')
                    ->execute([(int) $_POST['credential_id'], (int) $project['id']]);
            } elseif (($_POST['_action'] ?? '') === 'add_task') {
                $errors = validate_task($_POST);
                if (!$errors) save_task($_POST, null, (int) $user['id'], (int) $project['id']);
            } elseif (($_POST['_action'] ?? '') === 'task_status') {
                $task = get_task((int) $_POST['task_id']);
                if ($task && (int) $task['project_id'] === (int) $project['id']
                    && in_array($_POST['status'] ?? '', TASK_STATUSES, true)) {
                    db()->prepare("UPDATE project_tasks SET status=?, updated_at=datetime('now') WHERE id=?")
                        ->execute([$_POST['status'], (int) $task['id']]);
                }
            }
            if (!$errors) { header('Location: /projects/' . $project['id']); exit; }
        }
        $tasks = get_project_tasks((int) $project['id']);
        $employees = query_employees(['status' => 'active']);
        $services = query_services(['project_id' => (int) $project['id']]);
        $credentials = get_project_credentials((int) $project['id']);
        render('project_view', compact('user', 'project', 'tasks', 'employees', 'services', 'credentials', 'errors'));
        break;

    case $page === 'renewals':
        $filters = ['search' => trim($_GET['search'] ?? ''), 'type' => $_GET['type'] ?? '', 'expiring_days' => $_GET['expiring_days'] ?? ''];
        $services = query_services($filters);
        render('renewals', compact('user', 'services', 'filters'));
        break;

    case $page === 'employees':
        $filters = ['search' => trim($_GET['search'] ?? ''), 'status' => $_GET['status'] ?? ''];
        $employees = query_employees($filters);
        render('employees', compact('user', 'employees', 'filters'));
        break;

    case $page === 'employees/new' || (bool) preg_match('#^employees/(\d+)/edit$#', $page, $m):
        $employee = isset($m[1]) ? get_employee((int) $m[1]) : null;
        if (isset($m[1]) && !$employee) { http_response_code(404); exit('Employee not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = validate_employee($_POST);
            if (!$errors) {
                $id = save_employee($_POST, $employee ? (int) $employee['id'] : null, (int) $user['id']);
                header("Location: /employees/$id");
                exit;
            }
            $employee = array_merge($employee ?? [], $_POST);
        }
        render('employee_form', compact('user', 'employee', 'errors'));
        break;

    case (bool) preg_match('#^employees/(\d+)$#', $page, $m):
        $employee = get_employee((int) $m[1]);
        if (!$employee) { http_response_code(404); exit('Employee not found'); }
        render('employee_view', compact('user', 'employee'));
        break;

    case $page === 'tasks':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'task_status') {
            if (in_array($_POST['status'] ?? '', TASK_STATUSES, true)) {
                db()->prepare("UPDATE project_tasks SET status=?, updated_at=datetime('now') WHERE id=?")
                    ->execute([$_POST['status'], (int) $_POST['task_id']]);
            }
            header('Location: /tasks?' . http_build_query($_GET));
            exit;
        }
        $filters = [
            'status'      => $_GET['status'] ?? '',
            'project_id'  => $_GET['project_id'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? '',
            'search'      => trim($_GET['search'] ?? ''),
        ];
        $tasks = query_all_tasks($filters);
        $projects = query_projects([]);
        $employees = query_employees([]);
        render('tasks', compact('user', 'tasks', 'filters', 'projects', 'employees'));
        break;

    case $page === 'invoices':
        $filters = [
            'search'     => trim($_GET['search'] ?? ''),
            'status'     => $_GET['status'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'type'       => $_GET['type'] ?? '',
            'date_from'  => $_GET['date_from'] ?? '',
            'date_to'    => $_GET['date_to'] ?? '',
        ];
        $invoices = query_invoices($filters);
        $projects = query_projects([]);
        render('invoices', compact('user', 'invoices', 'filters', 'projects'));
        break;

    case $page === 'invoices/new' || (bool) preg_match('#^invoices/(\d+)/edit$#', $page, $m):
        $invoice = isset($m[1]) ? get_invoice((int) $m[1]) : null;
        if (isset($m[1]) && !$invoice) { http_response_code(404); exit('Invoice not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $items = [];
            foreach ($_POST['item_description'] ?? [] as $i => $desc) {
                $items[] = [
                    'description' => $desc,
                    'category' => $_POST['item_category'][$i] ?? 'other',
                    'qty' => $_POST['item_qty'][$i] ?? 1,
                    'rate' => $_POST['item_rate'][$i] ?? 0,
                ];
            }
            $errors = validate_invoice($_POST, $items);
            if (!$errors) {
                $id = save_invoice($_POST, $items, $invoice ? (int) $invoice['id'] : null, (int) $user['id']);
                header("Location: /invoices/$id");
                exit;
            }
        }
        $projects = query_projects([]);
        $settings = settings_all();
        render('invoice_form', compact('user', 'invoice', 'errors', 'projects', 'settings'));
        break;

    case (bool) preg_match('#^invoices/(\d+)$#', $page, $m):
        $invoice = get_invoice((int) $m[1]);
        if (!$invoice) { http_response_code(404); exit('Invoice not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['_action'] ?? '') === 'add_payment') {
                $errors = add_invoice_payment((int) $invoice['id'], $_POST, (int) $user['id']);
            } elseif (($_POST['_action'] ?? '') === 'cancel') {
                db()->prepare("UPDATE invoices SET status='cancelled' WHERE id=?")->execute([(int) $invoice['id']]);
            } elseif (($_POST['_action'] ?? '') === 'delete') {
                db()->prepare('DELETE FROM invoices WHERE id = ?')->execute([(int) $invoice['id']]);
                header('Location: /invoices');
                exit;
            } elseif (($_POST['_action'] ?? '') === 'reopen') {
                refresh_invoice_status((int) $invoice['id']);
                db()->prepare("UPDATE invoices SET status = CASE WHEN status='cancelled' THEN 'pending' ELSE status END WHERE id=?")
                    ->execute([(int) $invoice['id']]);
                refresh_invoice_status((int) $invoice['id']);
            }
            if (!$errors) { header('Location: /invoices/' . $invoice['id']); exit; }
            $invoice = get_invoice((int) $invoice['id']);
        }
        $accounts = query_accounts(true);
        render('invoice_view', compact('user', 'invoice', 'accounts', 'errors'));
        break;

    case $page === 'quotations':
        $filters = ['search' => trim($_GET['search'] ?? ''), 'status' => $_GET['status'] ?? ''];
        $quotations = query_quotations($filters);
        render('quotations', compact('user', 'quotations', 'filters'));
        break;

    case $page === 'quotations/new' || (bool) preg_match('#^quotations/(\d+)/edit$#', $page, $m):
        $quotation = isset($m[1]) ? get_quotation((int) $m[1]) : null;
        if (isset($m[1]) && !$quotation) { http_response_code(404); exit('Quotation not found'); }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $items = [];
            foreach ($_POST['item_description'] ?? [] as $i => $desc) {
                $items[] = [
                    'description' => $desc,
                    'category' => $_POST['item_category'][$i] ?? 'other',
                    'qty' => $_POST['item_qty'][$i] ?? 1,
                    'rate' => $_POST['item_rate'][$i] ?? 0,
                ];
            }
            $errors = validate_quotation($_POST, $items);
            if (!$errors) {
                $id = save_quotation($_POST, $items, $quotation ? (int) $quotation['id'] : null, (int) $user['id']);
                header("Location: /quotations/$id");
                exit;
            }
        }
        $clients = query_clients([]);
        $settings = settings_all();
        render('quotation_form', compact('user', 'quotation', 'errors', 'clients', 'settings'));
        break;

    case (bool) preg_match('#^quotations/(\d+)$#', $page, $m):
        $quotation = get_quotation((int) $m[1]);
        if (!$quotation) { http_response_code(404); exit('Quotation not found'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['_action'] ?? '';
            if ($action === 'convert') {
                [$projectId, ] = convert_quotation_to_project((int) $quotation['id'], (int) $user['id']);
                header('Location: /projects/' . $projectId);
                exit;
            }
            if (in_array($action, ['accepted', 'rejected', 'pending'], true)) {
                db()->prepare("UPDATE quotations SET status=?, updated_at=datetime('now') WHERE id=?")
                    ->execute([$action, (int) $quotation['id']]);
            }
            header('Location: /quotations/' . $quotation['id']);
            exit;
        }
        render('quotation_view', compact('user', 'quotation'));
        break;

    case $page === 'expenses':
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['_action'] ?? '';
            if ($action === 'add_expense' || $action === 'update_expense') {
                $errors = validate_expense($_POST);
                if (!$errors) {
                    $editId = $action === 'update_expense' ? (int) $_POST['expense_id'] : null;
                    save_expense($_POST, $editId, (int) $user['id']);
                    header('Location: /expenses?month=' . urlencode($_GET['month'] ?? date('Y-m')));
                    exit;
                }
            } elseif ($action === 'delete_expense') {
                db()->prepare('DELETE FROM expenses WHERE id = ?')->execute([(int) $_POST['expense_id']]);
                header('Location: /expenses?' . http_build_query(array_diff_key($_GET, ['edit' => 1])));
                exit;
            }
        }
        $filters = [
            'month'      => $_GET['month'] ?? date('Y-m'),
            'category'   => $_GET['category'] ?? '',
            'account_id' => $_GET['account_id'] ?? '',
            'search'     => trim($_GET['search'] ?? ''),
        ];
        $expenses = query_expenses($filters);
        $summary = expenses_summary($expenses);
        // Previous month total for comparison
        $prevMonth = $filters['month'] ? date('Y-m', strtotime($filters['month'] . '-01 -1 month')) : '';
        $prevTotal = $prevMonth ? expenses_summary(query_expenses(['month' => $prevMonth] + array_diff_key($filters, ['month' => 1])))['total'] : 0.0;
        // Row being edited (prefills the form)
        $editing = !empty($_GET['edit']) ? (function (int $id) {
            $stmt = db()->prepare('SELECT * FROM expenses WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        })((int) $_GET['edit']) : null;
        $accounts = query_accounts(true);
        $projects = query_projects([]);
        render('expenses', compact('user', 'expenses', 'summary', 'filters', 'accounts', 'projects', 'errors', 'prevMonth', 'prevTotal', 'editing'));
        break;

    case $page === 'search':
        $q = trim($_GET['q'] ?? '');
        $results = global_search($q);
        render('search', compact('user', 'q', 'results'));
        break;

    case $page === 'settings':
        $saved = false;
        $pwdError = null;
        $pwdSaved = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['_action'] ?? '') === 'save_settings') {
                settings_save(array_intersect_key($_POST, array_flip([
                    'company_name', 'company_tagline', 'company_address', 'company_phone',
                    'company_email', 'company_gstin', 'invoice_prefix', 'default_terms',
                ])));
                $saved = true;
            } elseif (($_POST['_action'] ?? '') === 'change_password') {
                if (($_POST['new_password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
                    $pwdError = 'New password and confirmation do not match.';
                } else {
                    $pwdError = change_password((int) $user['id'], $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
                }
                $pwdSaved = $pwdError === null;
            } elseif (($_POST['_action'] ?? '') === 'save_account') {
                if (trim($_POST['name'] ?? '') !== '') {
                    save_account($_POST, !empty($_POST['account_id']) ? (int) $_POST['account_id'] : null);
                }
                header('Location: /settings');
                exit;
            } elseif (($_POST['_action'] ?? '') === 'delete_account') {
                $aid = (int) $_POST['account_id'];
                // Detach references so history keeps rows, then remove the account
                db()->prepare('UPDATE invoice_payments SET account_id = NULL WHERE account_id = ?')->execute([$aid]);
                db()->prepare('UPDATE expenses SET account_id = NULL WHERE account_id = ?')->execute([$aid]);
                db()->prepare('DELETE FROM payment_accounts WHERE id = ?')->execute([$aid]);
                header('Location: /settings');
                exit;
            }
        }
        $settings = settings_all();
        $accounts = query_accounts();
        render('settings', compact('user', 'settings', 'accounts', 'saved', 'pwdError', 'pwdSaved'));
        break;

    default:
        http_response_code(404);
        exit('Page not found');
}

function render(string $view, array $vars): void
{
    extract($vars);
    ob_start();
    require __DIR__ . "/views/$view.php";
    $content = ob_get_clean();
    require __DIR__ . '/views/layout.php';
}
