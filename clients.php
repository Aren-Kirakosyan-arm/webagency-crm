<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$error = '';
$success = '';
if (isset($_GET['created'])) $success = 'Client added.';
if (isset($_GET['updated'])) $success = 'Client details updated.';
if (isset($_GET['deleted'])) $success = 'Client deleted.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = (string)($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $company = trim((string)($_POST['company'] ?? ''));

    if ($action === 'create' || $action === 'update') {
        if ($action === 'update' && (!$id || $id < 1)) {
            $error = 'Invalid client.';
        } elseif ($name === '' || mb_strlen($name) > 150) {
            $error = 'Enter a client name (up to 150 characters).';
        } elseif ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
            $error = 'Enter a valid email address.';
        } elseif (mb_strlen($phone) > 50 || mb_strlen($company) > 150) {
            $error = 'Phone number or company name is too long.';
        } else {
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO clients (name,email,phone,company) VALUES (:name,:email,:phone,:company)');
                $stmt->execute(['name'=>$name,'email'=>$email !== '' ? $email : null,'phone'=>$phone !== '' ? $phone : null,'company'=>$company !== '' ? $company : null]);
                header('Location: clients.php?created=1'); exit;
            } else {
                $stmt = $pdo->prepare('UPDATE clients SET name=:name,email=:email,phone=:phone,company=:company WHERE id=:id');
                $stmt->execute(['name'=>$name,'email'=>$email !== '' ? $email : null,'phone'=>$phone !== '' ? $phone : null,'company'=>$company !== '' ? $company : null,'id'=>$id]);
                header('Location: clients.php?updated=1'); exit;
            }
        }
    } elseif ($action === 'delete') {
        if (!$id || $id < 1) {
            $error = 'Invalid client.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM clients WHERE id=:id');
            $stmt->execute(['id'=>$id]);
            header('Location: clients.php?deleted=1'); exit;
        }
    } else {
        $error = 'Unknown action.';
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$stmt = $pdo->prepare(
    'SELECT c.id,c.name,c.email,c.phone,c.company,c.created_at,COUNT(p.id) AS project_count
     FROM clients c LEFT JOIN projects p ON p.client_id=c.id
     WHERE c.name LIKE :q1 OR COALESCE(c.email,"") LIKE :q2 OR COALESCE(c.company,"") LIKE :q3
     GROUP BY c.id,c.name,c.email,c.phone,c.company,c.created_at
     ORDER BY c.created_at DESC'
);
$like = '%' . $search . '%';
$stmt->execute(['q1'=>$like,'q2'=>$like,'q3'=>$like]);
$clients = $stmt->fetchAll();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Clients — WebAgency CRM</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><div class="app-shell">
<aside class="sidebar" id="sidebar">
<a class="brand" href="index.php"><span class="brand-mark">W</span><span>WebAgency <b>CRM</b></span></a>
<nav class="nav"><a class="nav-link" href="index.php">▦ <span>Dashboard</span></a><a class="nav-link active" href="clients.php">♙ <span>Clients</span></a><a class="nav-link" href="index.php#projects">▤ <span>Projects</span></a></nav>
<div class="sidebar-bottom"><div class="user-mini"><div class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'],0,1))) ?></div><div><strong><?= e($user['name']) ?></strong><small><?= e($user['email']) ?></small></div></div>
<form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="logout-button" type="submit">↪ Log out</button></form></div></aside>
<main class="main-content"><header class="topbar"><button class="menu-toggle" id="menuToggle" aria-label="Open menu">☰</button><div><span class="eyebrow">WORKSPACE</span><h1>Clients</h1></div></header>
<section class="welcome-row"><div><h2>Client directory</h2><p>Customer contacts and information.</p></div></section>
<?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success" role="status"><?= e($success) ?></div><?php endif; ?>
<section class="panel"><div class="panel-heading"><div><h3>Add client</h3><p>Enter the contact information.</p></div></div>
<form method="post" action="clients.php" class="project-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
<label>Client name<input name="name" maxlength="150" required></label><label>Email<input name="email" type="email" maxlength="190"></label><label>Phone<input name="phone" type="tel" maxlength="50"></label><label>Company<input name="company" maxlength="150"></label><div class="modal-actions"><button class="primary-button" type="submit">Add client</button></div></form></section>
<section class="panel" style="margin-top:20px"><div class="panel-heading"><div><h3>All clients (<?= count($clients) ?>)</h3><p>Database records.</p></div></div>
<form method="get" action="clients.php" class="search-form"><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by name, email, or company"><button class="secondary-button" type="submit">Search</button></form>
<div class="table-wrap"><table><thead><tr><th>Client</th><th>Contact details</th><th>Company</th><th>Projects</th><th>Created</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($clients as $client): ?>
<tr><td><strong><?= e($client['name']) ?></strong></td><td><?= e($client['email'] ?? '—') ?><small class="subline"><?= e($client['phone'] ?? '') ?></small></td><td><?= e($client['company'] ?? '—') ?></td><td><?= (int)$client['project_count'] ?></td><td><?= e(date('d.m.Y',strtotime($client['created_at']))) ?></td><td><div class="client-actions">
<button type="button" class="secondary-button edit-client-button" data-id="<?= (int)$client['id'] ?>" data-name="<?= e($client['name']) ?>" data-email="<?= e($client['email'] ?? '') ?>" data-phone="<?= e($client['phone'] ?? '') ?>" data-company="<?= e($client['company'] ?? '') ?>">Edit</button>
<form method="post" action="clients.php" onsubmit="return confirm('Delete this client?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$client['id'] ?>"><button class="delete-button" type="submit">Delete</button></form></div></td></tr>
<?php endforeach; ?>
<?php if (!$clients): ?><tr><td colspan="6">No clients found.</td></tr><?php endif; ?>
</tbody></table></div></section></main></div>
<div class="modal-backdrop" id="clientModal" aria-hidden="true"><section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="clientModalTitle"><div class="modal-heading"><div><h2 id="clientModalTitle">Edit client</h2><p>Update contact details.</p></div><button type="button" class="close-modal" id="closeClientModal">×</button></div>
<form method="post" action="clients.php" class="project-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" id="editClientId">
<label>Client name<input name="name" id="editClientName" maxlength="150" required></label><label>Email<input name="email" id="editClientEmail" type="email" maxlength="190"></label><label>Phone<input name="phone" id="editClientPhone" maxlength="50"></label><label>Company<input name="company" id="editClientCompany" maxlength="150"></label><div class="modal-actions"><button type="button" class="secondary-button" id="cancelClientModal">Cancel</button><button class="primary-button" type="submit">Save changes</button></div></form></section></div>
<script>
(() => {
 const modal=document.getElementById('clientModal');
 const close=()=>{modal.classList.remove('visible');modal.setAttribute('aria-hidden','true');};
 document.querySelectorAll('.edit-client-button').forEach(button=>button.addEventListener('click',()=>{
  document.getElementById('editClientId').value=button.dataset.id;
  document.getElementById('editClientName').value=button.dataset.name;
  document.getElementById('editClientEmail').value=button.dataset.email;
  document.getElementById('editClientPhone').value=button.dataset.phone;
  document.getElementById('editClientCompany').value=button.dataset.company;
  modal.classList.add('visible');modal.setAttribute('aria-hidden','false');
 }));
 document.getElementById('closeClientModal').addEventListener('click',close);
 document.getElementById('cancelClientModal').addEventListener('click',close);
 modal.addEventListener('click',e=>{if(e.target===modal)close();});
 document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
 const menu=document.getElementById('menuToggle'),sidebar=document.getElementById('sidebar');
 if(menu&&sidebar)menu.addEventListener('click',()=>sidebar.classList.toggle('sidebar-open'));
})();
</script></body></html>
