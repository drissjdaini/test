<?php
$pageTitle = 'Historique des Paiements';
require_once 'includes/header.php';
$db = getDB();

$paiements = $db->query("
    SELECT p.*, f.numero_facture, f.semestre, f.annee,
           CONCAT(a.prenom,' ',a.nom) AS abonne, a.numero_abonne
    FROM paiements p
    JOIN factures f ON f.id = p.facture_id
    JOIN abonnes a ON a.id = f.abonne_id
    ORDER BY p.date_paiement DESC
")->fetchAll();

$total = array_sum(array_column($paiements, 'montant'));

$par_mode = [];
foreach ($paiements as $p) {
    $par_mode[$p['mode_paiement']] = ($par_mode[$p['mode_paiement']] ?? 0) + $p['montant'];
}
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-money-bill-wave" style="color:var(--accent);margin-right:10px;"></i>Historique des Paiements</h1>
        <p><?= count($paiements) ?> paiement(s) · Total encaissé : <?= number_format($total,2,',',' ') ?> MAD</p>
    </div>
</div>

<div class="kpi-grid" style="margin-bottom:24px;">
    <div class="kpi-card green">
        <div class="kpi-icon"><i class="fas fa-coins"></i></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= number_format($total,2,',',' ') ?></div>
            <div class="kpi-label">Total Encaissé (MAD)</div>
        </div>
    </div>
    <?php
    $mode_icons = ['espèces'=>'fa-money-bill','virement'=>'fa-university','chèque'=>'fa-file-invoice','mobile'=>'fa-mobile-alt'];
    $mode_colors= ['espèces'=>'green','virement'=>'blue','chèque'=>'orange','mobile'=>'red'];
    foreach ($par_mode as $mode => $mnt): ?>
    <div class="kpi-card <?= $mode_colors[$mode] ?? 'blue' ?>">
        <div class="kpi-icon"><i class="fas <?= $mode_icons[$mode] ?? 'fa-credit-card' ?>"></i></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= number_format($mnt,2,',',' ') ?></div>
            <div class="kpi-label"><?= ucfirst($mode) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Abonné</th>
                    <th>Facture</th>
                    <th>Période</th>
                    <th>Montant</th>
                    <th>Mode</th>
                    <th>Référence</th>
                    <th>Caissier</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paiements as $p): ?>
                <tr>
                    <td><strong><?= date('d/m/Y',strtotime($p['date_paiement'])) ?></strong></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($p['abonne']) ?></div>
                        <div class="text-muted"><?= htmlspecialchars($p['numero_abonne']) ?></div>
                    </td>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($p['numero_facture']) ?></td>
                    <td><?= getSemestreLabel($p['semestre'],$p['annee']) ?></td>
                    <td><strong style="color:var(--accent);font-size:15px;"><?= number_format($p['montant'],2,',',' ') ?> MAD</strong></td>
                    <td>
                        <?php $icon = $mode_icons[$p['mode_paiement']] ?? 'fa-credit-card'; ?>
                        <span class="badge badge-success">
                            <i class="fas <?= $icon ?>"></i> <?= ucfirst($p['mode_paiement']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($p['reference_paiement'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['caissier'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($paiements)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:40px;">Aucun paiement enregistré</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
