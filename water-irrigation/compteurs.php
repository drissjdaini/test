<?php
$pageTitle = 'Compteurs';
require_once 'includes/header.php';
$db = getDB();

$compteurs = $db->query("
    SELECT c.*,
           CONCAT(a.prenom,' ',a.nom) AS abonne, a.numero_abonne,
           z.nom AS zone,
           (SELECT index_releve FROM releves r WHERE r.compteur_id = c.id ORDER BY date_releve DESC LIMIT 1) AS dernier_index,
           (SELECT date_releve  FROM releves r WHERE r.compteur_id = c.id ORDER BY date_releve DESC LIMIT 1) AS derniere_date
    FROM compteurs c
    JOIN abonnes a ON a.id = c.abonne_id
    JOIN zones z ON z.id = a.zone_id
    ORDER BY c.id DESC
")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-gauge-high" style="color:var(--primary);margin-right:10px;"></i>Compteurs</h1>
        <p><?= count($compteurs) ?> compteur(s)</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>N° Série</th>
                    <th>Abonné</th>
                    <th>Zone</th>
                    <th>Date Pose</th>
                    <th>Index Initial</th>
                    <th>Dernier Index</th>
                    <th>Dernier Relevé</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compteurs as $c): ?>
                <?php
                    $badge = match($c['statut']) {
                        'actif'      => 'badge-success',
                        'défectueux' => 'badge-danger',
                        'remplacé'   => 'badge-gray',
                        default      => 'badge-gray'
                    };
                    $conso_totale = ($c['dernier_index'] ?? $c['index_initial']) - $c['index_initial'];
                ?>
                <tr>
                    <td style="font-family:monospace;font-weight:600;"><?= htmlspecialchars($c['numero_serie']) ?></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($c['abonne']) ?></div>
                        <div class="text-muted"><?= htmlspecialchars($c['numero_abonne']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($c['zone']) ?></td>
                    <td><?= date('d/m/Y',strtotime($c['date_pose'])) ?></td>
                    <td><?= number_format($c['index_initial'],3,',',' ') ?> m³</td>
                    <td>
                        <?php if ($c['dernier_index']): ?>
                            <strong style="color:var(--primary);"><?= number_format($c['dernier_index'],3,',',' ') ?> m³</strong>
                            <div class="text-muted" style="font-size:11px;">Cumul : <?= number_format($conso_totale,3,',',' ') ?> m³</div>
                        <?php else: ?>
                            <span class="text-muted">Pas encore relevé</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $c['derniere_date'] ? date('d/m/Y',strtotime($c['derniere_date'])) : '—' ?></td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($c['statut']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
