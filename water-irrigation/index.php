<?php
$pageTitle = 'Tableau de Bord';
require_once 'includes/header.php';
$db = getDB();

// KPIs
$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM abonnes WHERE statut='actif') AS abonnes_actifs,
        (SELECT COUNT(*) FROM factures WHERE annee=YEAR(CURDATE())) AS factures_annee,
        (SELECT COALESCE(SUM(montant_ttc),0) FROM factures WHERE statut='payée' AND annee=YEAR(CURDATE())) AS recettes,
        (SELECT COALESCE(SUM(montant_ttc),0) FROM factures WHERE statut IN('émise','impayée') AND annee=YEAR(CURDATE())) AS impayees,
        (SELECT COALESCE(SUM(consommation_m3),0) FROM factures WHERE annee=YEAR(CURDATE())) AS conso_totale,
        (SELECT COUNT(*) FROM factures WHERE statut='impayée') AS nb_impayes
")->fetch();

// Consommation par zone
$conso_zones = $db->query("
    SELECT z.nom, z.code,
           COALESCE(SUM(f.consommation_m3),0) AS total_m3,
           COALESCE(SUM(f.montant_ttc),0)     AS total_mad,
           COUNT(DISTINCT a.id)                AS nb_abonnes
    FROM zones z
    LEFT JOIN abonnes a ON a.zone_id = z.id
    LEFT JOIN factures f ON f.abonne_id = a.id AND f.annee = YEAR(CURDATE())
    GROUP BY z.id
    ORDER BY total_m3 DESC
")->fetchAll();

$max_conso = max(array_column($conso_zones, 'total_m3')) ?: 1;

// Dernières factures
$dernieres = $db->query("
    SELECT f.numero_facture, f.montant_ttc, f.statut, f.date_emission,
           CONCAT(a.prenom,' ',a.nom) AS abonne,
           f.semestre, f.annee
    FROM factures f
    JOIN abonnes a ON a.id = f.abonne_id
    ORDER BY f.created_at DESC
    LIMIT 8
")->fetchAll();

// Taux de recouvrement
$total_emis  = $stats['recettes'] + $stats['impayees'];
$taux_rec    = $total_emis > 0 ? round(($stats['recettes'] / $total_emis) * 100, 1) : 0;
?>

<!-- WAVE HEADER -->
<div class="wave-bg">
    <div style="margin-bottom:20px;">
        <h1 style="color:white;font-size:26px;font-weight:800;">Bienvenue sur AquaIrrig</h1>
        <p style="color:rgba(255,255,255,.6);font-size:14px;">Tableau de bord – Année <?= date('Y') ?></p>
    </div>
    <div class="kpi-grid">
        <div class="kpi-card blue">
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" data-count="<?= $stats['abonnes_actifs'] ?>"><?= $stats['abonnes_actifs'] ?></div>
                <div class="kpi-label">Abonnés Actifs</div>
            </div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-icon"><i class="fas fa-tint"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" data-count="<?= number_format($stats['conso_totale'],0,',','') ?>">
                    <?= number_format($stats['conso_totale'],0,',' ,' ') ?> m³
                </div>
                <div class="kpi-label">Consommation Totale</div>
            </div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-content">
                <div class="kpi-value"><?= number_format($stats['recettes'],2,',',' ') ?></div>
                <div class="kpi-label">Recettes Encaissées (MAD)</div>
                <div class="kpi-trend up"><i class="fas fa-arrow-up"></i> <?= $taux_rec ?>% recouvrement</div>
            </div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="kpi-content">
                <div class="kpi-value"><?= number_format($stats['impayees'],2,',',' ') ?></div>
                <div class="kpi-label">Impayés en Cours (MAD)</div>
                <div class="kpi-trend down"><i class="fas fa-arrow-up"></i> <?= $stats['nb_impayes'] ?> factures</div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="gap:24px;margin-bottom:24px;">

    <!-- Consommation par Zone -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-map-marked-alt"></i> Consommation par Zone</div>
        </div>
        <div class="card-body">
            <?php foreach ($conso_zones as $z): ?>
            <?php $pct = $max_conso > 0 ? ($z['total_m3'] / $max_conso) * 100 : 0; ?>
            <div style="margin-bottom:18px;">
                <div class="d-flex justify-between align-center" style="margin-bottom:6px;">
                    <span style="font-weight:600;font-size:14px;"><?= htmlspecialchars($z['nom']) ?></span>
                    <span style="font-size:13px;color:var(--text-secondary);"><?= number_format($z['total_m3'],0,',' ,' ') ?> m³</span>
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                    <?= $z['nb_abonnes'] ?> abonné(s) · <?= number_format($z['total_mad'],2,',',' ') ?> MAD
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Taux de recouvrement -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-chart-donut"></i> Recouvrement</div>
        </div>
        <div class="card-body">
            <div style="text-align:center;padding:20px 0;">
                <div style="position:relative;display:inline-block;">
                    <svg viewBox="0 0 120 120" style="width:160px;height:160px;transform:rotate(-90deg);">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--border-light)" stroke-width="12"/>
                        <circle cx="60" cy="60" r="50" fill="none"
                            stroke="url(#grad)" stroke-width="12"
                            stroke-dasharray="<?= round($taux_rec * 3.14) ?> 314"
                            stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#0ea5e9"/>
                                <stop offset="100%" stop-color="#10b981"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <div style="font-size:28px;font-weight:800;color:var(--text-primary);"><?= $taux_rec ?>%</div>
                        <div style="font-size:11px;color:var(--text-muted);">Recouvrement</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px;">
                    <div style="background:var(--success-light);border-radius:10px;padding:14px;text-align:center;">
                        <div style="font-size:18px;font-weight:700;color:var(--accent-dark);"><?= number_format($stats['recettes'],0,',' ,' ') ?></div>
                        <div style="font-size:11px;color:var(--accent-dark);margin-top:2px;">Encaissé (MAD)</div>
                    </div>
                    <div style="background:var(--danger-light);border-radius:10px;padding:14px;text-align:center;">
                        <div style="font-size:18px;font-weight:700;color:#dc2626;"><?= number_format($stats['impayees'],0,',' ,' ') ?></div>
                        <div style="font-size:11px;color:#dc2626;margin-top:2px;">Impayé (MAD)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dernières Factures -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-file-invoice"></i> Dernières Factures</div>
        <a href="factures.php" class="btn btn-outline btn-sm">Voir tout</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>N° Facture</th>
                    <th>Abonné</th>
                    <th>Période</th>
                    <th>Montant TTC</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dernieres as $f): ?>
                <?php
                    $badge = match($f['statut']) {
                        'payée'   => 'badge-success',
                        'émise'   => 'badge-info',
                        'impayée' => 'badge-danger',
                        default   => 'badge-gray'
                    };
                    $icon = match($f['statut']) {
                        'payée'   => 'fa-check-circle',
                        'émise'   => 'fa-clock',
                        'impayée' => 'fa-times-circle',
                        default   => 'fa-circle'
                    };
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['numero_facture']) ?></strong></td>
                    <td><?= htmlspecialchars($f['abonne']) ?></td>
                    <td><?= getSemestreLabel($f['semestre'], $f['annee']) ?></td>
                    <td><strong><?= number_format($f['montant_ttc'],2,',',' ') ?> MAD</strong></td>
                    <td><span class="badge <?= $badge ?>"><i class="fas <?= $icon ?>"></i> <?= ucfirst($f['statut']) ?></span></td>
                    <td>
                        <a href="print_facture.php?id=<?= urlencode($f['numero_facture']) ?>"
                           class="btn btn-sm btn-primary btn-icon" title="Voir/Imprimer">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
