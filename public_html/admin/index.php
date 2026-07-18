<?php
require __DIR__ . '/auth.php';
require_admin();

$view = $_GET['view'] ?? 'overview';
if (!in_array($view, ['overview', 'investors', 'realtors', 'dispatch', 'assets'], true)) {
    $view = 'overview';
}
$q = trim($_GET['q'] ?? '');

$titles = [
    'overview' => 'Overview',
    'investors' => 'Investors Directory',
    'realtors' => 'Realtors Directory',
    'dispatch' => 'Invite Dispatch Tracker',
    'assets' => 'Asset Manager',
];

$STATUS_STYLE = [
    'Contacted' => 'background:#FFF3E0;color:#C46A00',
    'Meeting Scheduled' => 'background:#E8F5E9;color:#2E7D32',
    'Cold' => 'background:#F0EFEC;color:#999',
    'New' => 'background:#FDE8DB;color:#FF6200',
];
$PACK_STYLE = [
    'Pending' => 'background:#F0EFEC;color:#999',
    'Dispatched' => 'background:#FFF3E0;color:#C46A00',
    'Delivered' => 'background:#E8F5E9;color:#2E7D32',
];
$VISIT_STYLE = [
    'Scheduled' => 'background:#E3F2FD;color:#1565C0',
    'Not Scheduled' => 'background:#F0EFEC;color:#999',
    'Completed' => 'background:#E8F5E9;color:#2E7D32',
];
const PILL = 'padding:6px 12px;font:600 12px/1 \'DM Sans\',sans-serif;border-radius:3px;white-space:nowrap';

$pdo = db();
$flash = '';

// ── Mutations ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'investor-status') {
        $status = $_POST['status'] ?? '';
        if (isset($STATUS_STYLE[$status])) {
            $stmt = $pdo->prepare('UPDATE investors SET status = ? WHERE id = ?');
            $stmt->execute([$status, (int) ($_POST['id'] ?? 0)]);
        }
        header('Location: ?view=investors');
        exit;
    }

    if ($action === 'dispatch-update') {
        $pack = $_POST['pack_status'] ?? '';
        $visit = $_POST['visit_status'] ?? '';
        $date = trim($_POST['visit_date'] ?? '');
        if (isset($PACK_STYLE[$pack]) && isset($VISIT_STYLE[$visit])) {
            $stmt = $pdo->prepare(
                'UPDATE dispatches SET pack_status = ?, visit_status = ?, visit_date = ? WHERE id = ?'
            );
            $stmt->execute([$pack, $visit, $date !== '' ? $date : null, (int) ($_POST['id'] ?? 0)]);
        }
        header('Location: ?view=dispatch');
        exit;
    }

    if ($action === 'asset-replace') {
        $assetId = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();
        $allowed = ['pdf' => 'application/pdf', 'zip' => 'application/zip', 'mp4' => 'video/mp4'];
        if ($asset && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($asset['filename'], PATHINFO_EXTENSION));
            $uploadedExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            if ($uploadedExt !== $ext || !isset($allowed[$ext])) {
                $flash = 'That asset must be a .' . strtoupper($ext) . ' file — upload was rejected.';
            } else {
                $dest = __DIR__ . '/../downloads/' . $asset['filename'];
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                    $pdo->prepare('UPDATE assets SET updated_at = NOW() WHERE id = ?')->execute([$assetId]);
                    header('Location: ?view=assets');
                    exit;
                }
                $flash = 'Upload failed — check that the downloads/ directory is writable.';
            }
        } else {
            $flash = 'No file received — it may exceed the server upload limit.';
        }
        $view = 'assets';
    }
}

// ── Data for the current view ────────────────────────────────────────────────
$like = '%' . $q . '%';

$investors = [];
$realtors = [];
$dispatches = [];
$assets = [];
$stats = null;

if ($view === 'overview') {
    $stats = [
        'investorCount' => (int) $pdo->query('SELECT COUNT(*) FROM investors')->fetchColumn(),
        'realtorCount' => (int) $pdo->query('SELECT COUNT(*) FROM realtors')->fetchColumn(),
        'pendingPacks' => (int) $pdo->query("SELECT COUNT(*) FROM dispatches WHERE pack_status = 'Pending'")->fetchColumn(),
        'scheduledVisits' => (int) $pdo->query("SELECT COUNT(*) FROM dispatches WHERE visit_status = 'Scheduled'")->fetchColumn(),
    ];
    $recentInvestors = $pdo->query('SELECT name, status FROM investors ORDER BY created_at DESC LIMIT 3')->fetchAll();
    $recentRealtors = $pdo->query('SELECT name, created_at FROM realtors ORDER BY created_at DESC LIMIT 3')->fetchAll();
} elseif ($view === 'investors') {
    $stmt = $pdo->prepare('SELECT * FROM investors WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC');
    $stmt->execute([$like, $like]);
    $investors = $stmt->fetchAll();
} elseif ($view === 'realtors') {
    $stmt = $pdo->prepare('SELECT * FROM realtors WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC');
    $stmt->execute([$like, $like]);
    $realtors = $stmt->fetchAll();
} elseif ($view === 'dispatch') {
    $stmt = $pdo->prepare(
        'SELECT d.*, r.name FROM dispatches d JOIN realtors r ON r.id = d.realtor_id
         WHERE r.name LIKE ? ORDER BY r.created_at DESC'
    );
    $stmt->execute([$like]);
    $dispatches = $stmt->fetchAll();
} elseif ($view === 'assets') {
    $assets = $pdo->query('SELECT * FROM assets ORDER BY id')->fetchAll();
}

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES); }
function fmt_date(?string $d): string { return $d ? date('M j, Y', strtotime($d)) : '—'; }

function nav_item(string $key, string $label, string $current): string
{
    $active = $key === $current;
    $style = "display:block;padding:13px 12px;margin-bottom:2px;font:600 14px/1 'DM Sans',sans-serif;cursor:pointer;border-radius:4px;text-decoration:none;"
        . ($active ? 'background:#FF6200;color:#fff' : 'background:transparent;color:#FF6200');
    return '<a href="?view=' . $key . '" style="' . $style . '">' . e($label) . '</a>';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titles[$view]) ?> — Rentcom Admin</title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600&family=Nunito:wght@800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/site.css">
<style>
  .row-select {
    border: 1px solid #E6E2DB; background: #fff; font: 500 12px/1 'DM Sans', sans-serif;
    color: #333; padding: 6px 8px; border-radius: 3px; cursor: pointer;
  }
</style>
</head>
<body>
<div style="background:#F5F4F1;width:100%;min-height:100vh;display:flex;flex-wrap:wrap;font-family:'DM Sans',sans-serif">

  <!-- SIDEBAR -->
  <div style="width:260px;background:#111;flex-shrink:0;display:flex;flex-direction:column;padding:28px 0">
    <div style="display:flex;align-items:flex-start;gap:6px;padding:0 28px;margin-bottom:48px">
      <div style="width:9px;height:9px;border-radius:50%;background:#FF3C3C;margin-top:5px;flex-shrink:0"></div>
      <span style="font:800 24px/1 'Nunito',sans-serif;color:#FF6200;letter-spacing:-.02em">Rentcom</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:2px;padding:0 16px;flex:1">
      <?= nav_item('overview', 'Overview', $view) ?>
      <?= nav_item('investors', 'Investors Directory', $view) ?>
      <?= nav_item('realtors', 'Realtors Directory', $view) ?>
      <?= nav_item('dispatch', 'Invite Dispatch Tracker', $view) ?>
      <?= nav_item('assets', 'Asset Manager', $view) ?>
    </div>
    <div style="padding:16px 28px;border-top:1px solid #222;margin-top:16px">
      <a href="logout.php" style="font:500 13px/1 'DM Sans',sans-serif;color:#AAA;cursor:pointer;text-decoration:none">← Log Out</a>
    </div>
  </div>

  <!-- MAIN -->
  <div style="flex:1;min-width:280px;display:flex;flex-direction:column;overflow:hidden">
    <div style="min-height:80px;background:#fff;border-bottom:1px solid #E6E2DB;display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;padding:12px clamp(20px,4vw,40px);flex-shrink:0">
      <h1 style="font:700 22px/1 'Playfair Display',serif;color:#111"><?= e($titles[$view]) ?></h1>
      <?php if (in_array($view, ['investors', 'realtors', 'dispatch'], true)): ?>
      <form method="get" style="width:min(260px,60vw)">
        <input type="hidden" name="view" value="<?= e($view) ?>">
        <input class="field" style="background:#F2F0EB;border-color:#E6E2DB;padding:11px 18px;font-size:14px" type="search" name="q" value="<?= e($q) ?>" placeholder="Search…">
      </form>
      <?php endif; ?>
    </div>
    <div style="flex:1;padding:clamp(20px,4vw,40px);overflow:auto">

      <?php if ($flash): ?>
      <div style="background:#FDE8DB;border:1px solid #F5C9AE;color:#B84A00;padding:14px 20px;font:500 14px/1.4 'DM Sans',sans-serif;margin-bottom:24px"><?= e($flash) ?></div>
      <?php endif; ?>

      <?php if ($view === 'overview'): ?>
      <div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;margin-bottom:32px">
          <div style="background:#fff;border:1px solid #E6E2DB;padding:28px"><div style="font:700 36px/1 'Playfair Display',serif;color:#111;margin-bottom:8px"><?= $stats['investorCount'] ?></div><div style="font:400 12px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Total Investors</div></div>
          <div style="background:#fff;border:1px solid #E6E2DB;padding:28px"><div style="font:700 36px/1 'Playfair Display',serif;color:#111;margin-bottom:8px"><?= $stats['realtorCount'] ?></div><div style="font:400 12px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Total Realtors</div></div>
          <div style="background:#fff;border:1px solid #E6E2DB;padding:28px"><div style="font:700 36px/1 'Playfair Display',serif;color:#FF6200;margin-bottom:8px"><?= $stats['pendingPacks'] ?></div><div style="font:400 12px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Invite Packs Pending</div></div>
          <div style="background:#fff;border:1px solid #E6E2DB;padding:28px"><div style="font:700 36px/1 'Playfair Display',serif;color:#111;margin-bottom:8px"><?= $stats['scheduledVisits'] ?></div><div style="font:400 12px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Site Visits Scheduled</div></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">
          <div style="background:#fff;border:1px solid #E6E2DB;padding:28px">
            <h3 style="font:700 17px/1 'Playfair Display',serif;color:#111;margin-bottom:20px">Recent Investor Leads</h3>
            <?php foreach ($recentInvestors as $row): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #F2F0EB;gap:12px">
              <div style="font:500 14px/1 'DM Sans',sans-serif;color:#111"><?= e($row['name']) ?></div>
              <span style="<?= PILL ?>;<?= $STATUS_STYLE[$row['status']] ?? '' ?>"><?= e($row['status']) ?></span>
            </div>
            <?php endforeach; if (!$recentInvestors): ?>
            <div style="font:400 14px/1.5 'DM Sans',sans-serif;color:#999">No investor leads yet.</div>
            <?php endif; ?>
          </div>
          <div style="background:#fff;border:1px solid #E6E2DB;padding:28px">
            <h3 style="font:700 17px/1 'Playfair Display',serif;color:#111;margin-bottom:20px">Recent Realtor Signups</h3>
            <?php foreach ($recentRealtors as $row): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #F2F0EB;gap:12px">
              <div style="font:500 14px/1 'DM Sans',sans-serif;color:#111"><?= e($row['name']) ?></div>
              <div style="font:400 13px/1 'DM Sans',sans-serif;color:#999"><?= fmt_date($row['created_at']) ?></div>
            </div>
            <?php endforeach; if (!$recentRealtors): ?>
            <div style="font:400 14px/1.5 'DM Sans',sans-serif;color:#999">No realtor signups yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($view === 'investors'): ?>
      <div style="overflow-x:auto">
      <div style="background:#fff;border:1px solid #E6E2DB;min-width:720px">
        <div style="display:grid;grid-template-columns:2fr 2.4fr 1.6fr 1.8fr;padding:18px 28px;border-bottom:1px solid #ECEAE5">
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Name</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Email</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">WhatsApp</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Status</div>
        </div>
        <?php foreach ($investors as $row): ?>
        <div style="display:grid;grid-template-columns:2fr 2.4fr 1.6fr 1.8fr;padding:20px 28px;border-bottom:1px solid #F2F0EB;align-items:center">
          <div style="font:600 14px/1 'DM Sans',sans-serif;color:#111"><?= e($row['name']) ?></div>
          <div style="font:400 14px/1 'DM Sans',sans-serif;color:#666;word-break:break-all"><?= e($row['email']) ?></div>
          <div style="font:400 14px/1 'DM Sans',sans-serif;color:#666"><?= e($row['whatsapp']) ?></div>
          <div>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="investor-status">
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <select class="row-select" name="status" onchange="this.form.submit()" style="<?= $STATUS_STYLE[$row['status']] ?? '' ?>;border:none;<?= PILL ?>">
                <?php foreach (array_keys($STATUS_STYLE) as $s): ?>
                <option value="<?= e($s) ?>" <?= $s === $row['status'] ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>
        <?php endforeach; if (!$investors): ?>
        <div style="padding:28px;font:400 14px/1.5 'DM Sans',sans-serif;color:#999"><?= $q ? 'No investors match your search.' : 'No investor leads yet.' ?></div>
        <?php endif; ?>
      </div>
      </div>
      <?php endif; ?>

      <?php if ($view === 'realtors'): ?>
      <div style="overflow-x:auto">
      <div style="background:#fff;border:1px solid #E6E2DB;min-width:640px">
        <div style="display:grid;grid-template-columns:2fr 2.4fr 1.6fr 1.6fr;padding:18px 28px;border-bottom:1px solid #ECEAE5">
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Name</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Email</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">WhatsApp</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Onboarding Date</div>
        </div>
        <?php foreach ($realtors as $row): ?>
        <div style="display:grid;grid-template-columns:2fr 2.4fr 1.6fr 1.6fr;padding:20px 28px;border-bottom:1px solid #F2F0EB;align-items:center">
          <div style="font:600 14px/1 'DM Sans',sans-serif;color:#111"><?= e($row['name']) ?></div>
          <div style="font:400 14px/1 'DM Sans',sans-serif;color:#666;word-break:break-all"><?= e($row['email']) ?></div>
          <div style="font:400 14px/1 'DM Sans',sans-serif;color:#666"><?= e($row['whatsapp']) ?></div>
          <div style="font:400 14px/1 'DM Sans',sans-serif;color:#666"><?= fmt_date($row['created_at']) ?></div>
        </div>
        <?php endforeach; if (!$realtors): ?>
        <div style="padding:28px;font:400 14px/1.5 'DM Sans',sans-serif;color:#999"><?= $q ? 'No realtors match your search.' : 'No realtor signups yet.' ?></div>
        <?php endif; ?>
      </div>
      </div>
      <?php endif; ?>

      <?php if ($view === 'dispatch'): ?>
      <div style="overflow-x:auto">
      <div style="background:#fff;border:1px solid #E6E2DB;min-width:760px">
        <div style="display:grid;grid-template-columns:2fr 1.6fr 1.6fr 1.8fr 1fr;padding:18px 28px;border-bottom:1px solid #ECEAE5">
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Realtor</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Invite Pack</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Site Visit</div>
          <div style="font:600 11px/1 'DM Sans',sans-serif;color:#999;text-transform:uppercase;letter-spacing:.08em">Visit Date</div>
          <div></div>
        </div>
        <?php foreach ($dispatches as $row): ?>
        <form method="post" style="display:grid;grid-template-columns:2fr 1.6fr 1.6fr 1.8fr 1fr;padding:20px 28px;border-bottom:1px solid #F2F0EB;align-items:center;gap:8px">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="dispatch-update">
          <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
          <div style="font:600 14px/1 'DM Sans',sans-serif;color:#111"><?= e($row['name']) ?></div>
          <div>
            <select class="row-select" name="pack_status" style="<?= $PACK_STYLE[$row['pack_status']] ?? '' ?>;border:none;<?= PILL ?>">
              <?php foreach (array_keys($PACK_STYLE) as $s): ?>
              <option value="<?= e($s) ?>" <?= $s === $row['pack_status'] ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <select class="row-select" name="visit_status" style="<?= $VISIT_STYLE[$row['visit_status']] ?? '' ?>;border:none;<?= PILL ?>">
              <?php foreach (array_keys($VISIT_STYLE) as $s): ?>
              <option value="<?= e($s) ?>" <?= $s === $row['visit_status'] ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><input class="row-select" type="date" name="visit_date" value="<?= e($row['visit_date']) ?>"></div>
          <div><button type="submit" class="btn" style="padding:8px 14px;font:600 12px/1 'DM Sans',sans-serif;width:auto">Save</button></div>
        </form>
        <?php endforeach; if (!$dispatches): ?>
        <div style="padding:28px;font:400 14px/1.5 'DM Sans',sans-serif;color:#999"><?= $q ? 'No realtors match your search.' : 'No dispatches yet — rows appear when realtors register.' ?></div>
        <?php endif; ?>
      </div>
      </div>
      <?php endif; ?>

      <?php if ($view === 'assets'): ?>
      <div>
        <div style="border:2px dashed #D8D4CC;background:#fff;padding:40px;text-align:center;margin-bottom:32px">
          <div style="font:600 15px/1 'DM Sans',sans-serif;color:#333;margin-bottom:8px">Upload a New Asset</div>
          <div style="font:400 13px/1.4 'DM Sans',sans-serif;color:#999">Use "Replace File" on a card below — the new version goes live on the site instantly.</div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">
          <?php foreach ($assets as $a): $exists = file_exists(__DIR__ . '/../downloads/' . $a['filename']); ?>
          <div style="background:#fff;border:1px solid #E6E2DB;padding:24px;display:flex;gap:20px;align-items:center">
            <div style="width:52px;height:66px;background:#F2F0EB;flex-shrink:0;display:flex;align-items:center;justify-content:center;font:600 10px/1 'DM Sans',sans-serif;color:#999"><?= e($a['type']) ?></div>
            <div style="flex:1">
              <div style="font:600 15px/1.3 'DM Sans',sans-serif;color:#111;margin-bottom:4px"><?= e($a['name']) ?></div>
              <div style="font:400 12px/1 'DM Sans',sans-serif;color:#999;margin-bottom:12px">
                <?= $exists ? 'Updated ' . fmt_date($a['updated_at']) : 'No file uploaded yet' ?>
              </div>
              <form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="asset-replace">
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <label style="font:500 13px/1 'DM Sans',sans-serif;color:#FF6200;cursor:pointer">
                  Replace File →
                  <input type="file" name="file" accept=".<?= e(strtolower(pathinfo($a['filename'], PATHINFO_EXTENSION))) ?>" style="display:none" onchange="this.form.submit()">
                </label>
                <?php if ($exists): ?>
                <a href="../downloads/<?= e($a['filename']) ?>" style="font:400 12px/1 'DM Sans',sans-serif;color:#999;text-decoration:none">View current</a>
                <?php endif; ?>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
