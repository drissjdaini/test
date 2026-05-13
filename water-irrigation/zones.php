<?php
$pageTitle = 'Zones d\'Irrigation';
require_once 'includes/header.php';
$db = getDB();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $db->prepare("INSERT INTO zones (code, nom, description, tarif_m3) VALUES (?,?,?,?)")
       ->execute([strtoupper(trim($_POST['code'])), trim($_POST['nom']), trim($_POST['description']), (float)$_POST['tarif_m3']]);
    $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Zone ajoutée avec succès.</div>';
}

$zones = $db->query("
    SELECT z.*, COUNT(a.id) AS nb_abonnes,
           COALESCE(SUM(f.consommation_m3),0) AS total_conso,
           COALESCE(SUM(f.montant_ttc),0) AS total_mad
    FROM zones z
    LEFT JOIN abonnes a ON a.zone_id = z.id AND a.statut = 'actif'
    LEFT JOIN factures f ON f.abonne_id = a.id AND f.annee = YEAR(CURDATE())
    GROUP BY z.id
    ORDER BY z.code
")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-map-marked-alt" style="color:var(--primary);margin-right:10px;"></i>Zones d'Irrigation</h1>
        <p><?= count($zones) ?> zone(s) configurée(s)</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalZone')">
        <i class="fas fa-plus"></i> Nouvelle Zone
    </button>
</div>

<?= $msg ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">
    <?php
    $colors = ['blue','green','orange','red'];
    $icons  = ['fa-seedling','fa-leaf','fa-apple-alt','fa-wheat-awn'];
    foreach ($zones as $i => $z):
        $c = $colors[$i % count($colors)];
        $ic = $icons[$i % count($icons)];
    ?>
    <div class="kpi-card <?= $c ?>" style="flex-direction:column;align-items:flex-start;gap:12px;">
        <div style="display:flex;justify-content:space-between;width:100%;align-items:center;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="kpi-icon"><i class="fas <?= $ic ?>"></i></div>
                <div>
                    <div style="font-size:18px;font-weight:800;color:var(--text-primary);"><?= htmlspecialchars($z['code']) ?></div>
                    <div style="font-size:13px;color:var(--text-secondary);"><?= htmlspecialchars($z['nom']) ?></div>
                </div>
            </div>
            <span class="badge badge-info"><?= $z['nb_abonnes'] ?> abonné(s)</span>
        </div>
        <p style="font-size:13px;color:var(--text-muted);line-height:1.5;"><?= htmlspecialchars($z['description'] ?? '') ?></p>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;width:100%;background:var(--bg-main);border-radius:10px;padding:12px;">
            <div style="text-align:center;">
                <div style="font-size:16px;font-weight:700;color:var(--primary);"><?= number_format($z['tarif_m3'],4,',',' ') ?></div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;">MAD/m³</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:16px;font-weight:700;color:var(--accent);"><?= number_format($z['total_conso'],0,',',' ') ?></div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;">m³ consommés</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:16px;font-weight:700;color:var(--warning);"><?= number_format($z['total_mad'],0,',',' ') ?></div>
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;">MAD facturé</div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalZone">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-map-marked-alt" style="color:var(--primary);margin-right:8px;"></i>Nouvelle Zone</div>
            <button class="modal-close" onclick="closeModal('modalZone')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ajouter">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="Z05" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarif m³ (MAD) *</label>
                        <input type="number" name="tarif_m3" class="form-control" step="0.0001" value="0.5000" required>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalZone')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
