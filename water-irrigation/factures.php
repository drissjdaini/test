<?php
$pageTitle = 'Gestion des Factures';
require_once 'includes/header.php';
$db = getDB();

$msg = '';

// Mise à jour statut facture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'payer') {
    $facture_id = (int)$_POST['facture_id'];
    $mode       = $_POST['mode_paiement'];
    $today      = date('Y-m-d');

    $f = $db->prepare("SELECT * FROM factures WHERE id=?")->execute([$facture_id]);
    $db->prepare("UPDATE factures SET statut='payée', date_paiement=?, mode_paiement=? WHERE id=?")
       ->execute([$today, $mode, $facture_id]);

    $facture = $db->prepare("SELECT montant_ttc FROM factures WHERE id=?")->execute([$facture_id]);
    $facture = $db->query("SELECT montant_ttc FROM factures WHERE id=$facture_id")->fetch();

    $db->prepare("INSERT INTO paiements (facture_id, montant, date_paiement, mode_paiement, caissier)
                  VALUES (?,?,?,?,?)")
       ->execute([$facture_id, $facture['montant_ttc'], $today, $mode, 'Caissier']);

    $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Paiement enregistré avec succès.</div>';
}

// Filtres
$abonne_f = (int)($_GET['abonne'] ?? 0);
$statut_f = trim($_GET['statut'] ?? '');
$sem_f    = (int)($_GET['semestre'] ?? 0);
$an_f     = (int)($_GET['annee'] ?? date('Y'));

$sql = "
    SELECT f.*, CONCAT(a.prenom,' ',a.nom) AS abonne, a.numero_abonne, z.nom AS zone
    FROM factures f
    JOIN abonnes a ON a.id = f.abonne_id
    JOIN zones z ON z.id = a.zone_id
    WHERE 1=1
";
$params = [];
if ($abonne_f) { $sql .= " AND f.abonne_id = ?"; $params[] = $abonne_f; }
if ($statut_f) { $sql .= " AND f.statut = ?"; $params[] = $statut_f; }
if ($sem_f)    { $sql .= " AND f.semestre = ?"; $params[] = $sem_f; }
if ($an_f)     { $sql .= " AND f.annee = ?"; $params[] = $an_f; }
$sql .= " ORDER BY f.date_emission DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$factures = $stmt->fetchAll();

// Totaux
$total_ttc  = array_sum(array_column($factures, 'montant_ttc'));
$total_paye = array_sum(array_column(array_filter($factures, fn($f) => $f['statut']==='payée'), 'montant_ttc'));
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:10px;"></i>Factures Semestrielles</h1>
        <p><?= count($factures) ?> facture(s) · Total : <?= number_format($total_ttc,2,',',' ') ?> MAD</p>
    </div>
    <a href="generate_factures.php" class="btn btn-success">
        <i class="fas fa-bolt"></i> Générer des Factures
    </a>
</div>

<?= $msg ?>

<!-- KPIs factures -->
<div class="kpi-grid" style="margin-bottom:24px;">
    <?php
    $counts = ['payée'=>0,'émise'=>0,'impayée'=>0,'annulée'=>0];
    $mounts = ['payée'=>0,'émise'=>0,'impayée'=>0,'annulée'=>0];
    foreach ($factures as $f) {
        $counts[$f['statut']]++;
        $mounts[$f['statut']] += $f['montant_ttc'];
    }
    ?>
    <div class="kpi-card green">
        <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $counts['payée'] ?></div>
            <div class="kpi-label">Payées · <?= number_format($mounts['payée'],0,',',' ') ?> MAD</div>
        </div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-icon"><i class="fas fa-clock"></i></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $counts['émise'] ?></div>
            <div class="kpi-label">En Attente · <?= number_format($mounts['émise'],0,',',' ') ?> MAD</div>
        </div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $counts['impayée'] ?></div>
            <div class="kpi-label">Impayées · <?= number_format($mounts['impayée'],0,',',' ') ?> MAD</div>
        </div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="kpi-content">
            <div class="kpi-value"><?= number_format($total_ttc,0,',',' ') ?></div>
            <div class="kpi-label">Total Émis (MAD)</div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px;">
        <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
            <select name="statut" class="form-control" style="width:160px;">
                <option value="">Tous statuts</option>
                <option value="payée"   <?= $statut_f==='payée'   ?'selected':'' ?>>Payées</option>
                <option value="émise"   <?= $statut_f==='émise'   ?'selected':'' ?>>Émises</option>
                <option value="impayée" <?= $statut_f==='impayée' ?'selected':'' ?>>Impayées</option>
                <option value="annulée" <?= $statut_f==='annulée' ?'selected':'' ?>>Annulées</option>
            </select>
            <select name="semestre" class="form-control" style="width:160px;">
                <option value="">Tous semestres</option>
                <option value="1" <?= $sem_f===1?'selected':'' ?>>1er Semestre</option>
                <option value="2" <?= $sem_f===2?'selected':'' ?>>2ème Semestre</option>
            </select>
            <input type="number" name="annee" class="form-control" style="width:100px;" value="<?= $an_f ?>" min="2018" max="2030">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer</button>
            <a href="factures.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>N° Facture</th>
                    <th>Abonné</th>
                    <th>Zone</th>
                    <th>Période</th>
                    <th>Consommation</th>
                    <th>Montant HT</th>
                    <th>TVA</th>
                    <th>Montant TTC</th>
                    <th>Échéance</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($factures as $f): ?>
                <?php
                    $badge = match($f['statut']) {
                        'payée'   => 'badge-success',
                        'émise'   => 'badge-info',
                        'impayée' => 'badge-danger',
                        default   => 'badge-gray'
                    };
                    $retard = $f['statut'] !== 'payée' && strtotime($f['date_echeance']) < time();
                ?>
                <tr <?= $retard ? 'style="background:#fff5f5;"' : '' ?>>
                    <td><strong><?= htmlspecialchars($f['numero_facture']) ?></strong></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($f['abonne']) ?></div>
                        <div class="text-muted"><?= htmlspecialchars($f['numero_abonne']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($f['zone']) ?></td>
                    <td><?= getSemestreLabel($f['semestre'],$f['annee']) ?></td>
                    <td><?= number_format($f['consommation_m3'],3,',',' ') ?> m³</td>
                    <td><?= number_format($f['montant_ht'],2,',',' ') ?> MAD</td>
                    <td><?= number_format($f['montant_tva'],2,',',' ') ?> MAD</td>
                    <td><strong style="color:var(--primary);"><?= number_format($f['montant_ttc'],2,',',' ') ?> MAD</strong></td>
                    <td>
                        <?php if ($retard): ?>
                            <span style="color:var(--danger);font-weight:600;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?= date('d/m/Y',strtotime($f['date_echeance'])) ?>
                            </span>
                        <?php else: ?>
                            <?= date('d/m/Y',strtotime($f['date_echeance'])) ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($f['statut']) ?></span></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="print_facture.php?id=<?= urlencode($f['numero_facture']) ?>"
                               class="btn btn-sm btn-primary btn-icon" title="Imprimer">
                                <i class="fas fa-print"></i>
                            </a>
                            <?php if ($f['statut'] !== 'payée' && $f['statut'] !== 'annulée'): ?>
                            <button class="btn btn-sm btn-success btn-icon" title="Marquer payée"
                                    onclick="openPaiement(<?= $f['id'] ?>,'<?= addslashes($f['numero_facture']) ?>')">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($factures)): ?>
                <tr><td colspan="11" class="text-center text-muted" style="padding:40px;">Aucune facture trouvée</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PAIEMENT -->
<div class="modal-overlay" id="modalPaiement">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-money-bill-wave" style="color:var(--accent);margin-right:8px;"></i>Enregistrer Paiement</div>
            <button class="modal-close" onclick="closeModal('modalPaiement')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="payer">
            <input type="hidden" name="facture_id" id="pay_facture_id">
            <div class="modal-body">
                <div class="alert alert-info" id="pay_info" style="margin-bottom:16px;">
                    <i class="fas fa-info-circle"></i> <span id="pay_num"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Mode de paiement *</label>
                    <select name="mode_paiement" class="form-control form-select" required>
                        <option value="espèces">💵 Espèces</option>
                        <option value="virement">🏦 Virement bancaire</option>
                        <option value="chèque">📝 Chèque</option>
                        <option value="mobile">📱 Mobile Money</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalPaiement')">Annuler</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Confirmer le Paiement</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaiement(id, num) {
    document.getElementById('pay_facture_id').value = id;
    document.getElementById('pay_num').textContent = 'Facture : ' + num;
    openModal('modalPaiement');
}
</script>

<?php require_once 'includes/footer.php'; ?>
