<?php
$pageTitle = 'Relevés de Compteurs';
require_once 'includes/header.php';
$db = getDB();

$msg = '';

// Enregistrement d'un relevé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $abonne_id   = (int)$_POST['abonne_id'];
    $compteur_id = (int)$_POST['compteur_id'];
    $index_new   = (float)$_POST['index_releve'];
    $semestre    = (int)$_POST['semestre'];
    $annee       = (int)$_POST['annee'];
    $date_releve = $_POST['date_releve'];
    $agent       = trim($_POST['agent_releve']);

    // Récupérer dernier index
    $last = $db->prepare("
        SELECT index_releve FROM releves
        WHERE compteur_id = ? ORDER BY date_releve DESC LIMIT 1
    ");
    $last->execute([$compteur_id]);
    $row = $last->fetch();
    $index_prec = $row ? (float)$row['index_releve'] : 0;

    if ($index_new < $index_prec) {
        $msg = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> L\'index relevé (' . $index_new . ') est inférieur au précédent (' . $index_prec . '). Vérifiez la saisie.</div>';
    } else {
        $db->prepare("
            INSERT INTO releves (compteur_id, abonne_id, date_releve, index_releve, index_precedent, semestre, annee, agent_releve)
            VALUES (?,?,?,?,?,?,?,?)
        ")->execute([$compteur_id, $abonne_id, $date_releve, $index_new, $index_prec, $semestre, $annee, $agent]);
        $conso = $index_new - $index_prec;
        $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Relevé enregistré. Consommation calculée : ' . number_format($conso,3,',',' ') . ' m³</div>';
    }
}

// Abonnés avec compteur actif
$abonnes = $db->query("
    SELECT a.id, a.numero_abonne, a.prenom, a.nom, c.id AS cpt_id, c.numero_serie,
           z.nom AS zone, z.tarif_m3
    FROM abonnes a
    JOIN compteurs c ON c.abonne_id = a.id AND c.statut = 'actif'
    JOIN zones z ON z.id = a.zone_id
    WHERE a.statut = 'actif'
    ORDER BY a.nom
")->fetchAll();

// Filtres
$abonne_f = (int)($_GET['abonne'] ?? 0);
$sem_f    = (int)($_GET['semestre'] ?? 0);
$an_f     = (int)($_GET['annee'] ?? date('Y'));

$sql = "
    SELECT r.*, CONCAT(a.prenom,' ',a.nom) AS abonne, a.numero_abonne,
           c.numero_serie, z.nom AS zone
    FROM releves r
    JOIN abonnes a ON a.id = r.abonne_id
    JOIN compteurs c ON c.id = r.compteur_id
    JOIN zones z ON z.id = a.zone_id
    WHERE 1=1
";
$params = [];
if ($abonne_f) { $sql .= " AND r.abonne_id = ?"; $params[] = $abonne_f; }
if ($sem_f)    { $sql .= " AND r.semestre = ?"; $params[] = $sem_f; }
if ($an_f)     { $sql .= " AND r.annee = ?"; $params[] = $an_f; }
$sql .= " ORDER BY r.date_releve DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$releves = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-clipboard-list" style="color:var(--primary);margin-right:10px;"></i>Relevés de Compteurs</h1>
        <p><?= count($releves) ?> relevé(s)</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalReleve')">
        <i class="fas fa-plus"></i> Nouveau Relevé
    </button>
</div>

<?= $msg ?>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px;">
        <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
            <select name="abonne" class="form-control" style="width:200px;">
                <option value="">Tous les abonnés</option>
                <?php foreach ($abonnes as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $abonne_f===$a['id']?'selected':'' ?>>
                    <?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select name="semestre" class="form-control" style="width:150px;">
                <option value="">Tous semestres</option>
                <option value="1" <?= $sem_f===1?'selected':'' ?>>1er Semestre</option>
                <option value="2" <?= $sem_f===2?'selected':'' ?>>2ème Semestre</option>
            </select>
            <input type="number" name="annee" class="form-control" style="width:100px;" value="<?= $an_f ?>" min="2018" max="2030">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer</button>
            <a href="releves.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Abonné</th>
                    <th>Compteur</th>
                    <th>Zone</th>
                    <th>Index Précédent</th>
                    <th>Index Relevé</th>
                    <th>Consommation</th>
                    <th>Période</th>
                    <th>Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($releves as $r): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($r['date_releve'])) ?></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($r['abonne']) ?></div>
                        <div class="text-muted"><?= htmlspecialchars($r['numero_abonne']) ?></div>
                    </td>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($r['numero_serie']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($r['zone']) ?></span></td>
                    <td><?= number_format($r['index_precedent'],3,',',' ') ?> m³</td>
                    <td><?= number_format($r['index_releve'],3,',',' ') ?> m³</td>
                    <td>
                        <strong style="color:var(--primary);font-size:15px;">
                            <?= number_format($r['consommation'],3,',',' ') ?> m³
                        </strong>
                    </td>
                    <td><span class="badge badge-gray"><?= getSemestreLabel($r['semestre'],$r['annee']) ?></span></td>
                    <td><?= htmlspecialchars($r['agent_releve'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($releves)): ?>
                <tr><td colspan="9" class="text-center text-muted" style="padding:40px;">Aucun relevé trouvé</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NOUVEAU RELEVÉ -->
<div class="modal-overlay" id="modalReleve">
    <div class="modal" style="max-width:640px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:8px;"></i>Saisir un Relevé</div>
            <button class="modal-close" onclick="closeModal('modalReleve')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" id="formReleve">
            <input type="hidden" name="action" value="ajouter">
            <input type="hidden" name="compteur_id" id="cpt_id">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Abonné *</label>
                        <select name="abonne_id" id="sel_abonne" class="form-control form-select" required onchange="fillCompteur(this)">
                            <option value="">Sélectionner un abonné...</option>
                            <?php foreach ($abonnes as $a): ?>
                            <option value="<?= $a['id'] ?>"
                                    data-cpt="<?= $a['cpt_id'] ?>"
                                    data-serie="<?= htmlspecialchars($a['numero_serie']) ?>">
                                <?= htmlspecialchars($a['numero_abonne'].' – '.$a['prenom'].' '.$a['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Compteur</label>
                        <input type="text" id="aff_cpt" class="form-control" disabled placeholder="—">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date du relevé *</label>
                        <input type="date" name="date_releve" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Index relevé (m³) *</label>
                        <input type="number" name="index_releve" class="form-control" step="0.001" required placeholder="ex: 12500.000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Semestre *</label>
                        <select name="semestre" class="form-control form-select" required>
                            <option value="1">1er Semestre (Jan–Juin)</option>
                            <option value="2">2ème Semestre (Juil–Déc)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Année *</label>
                        <input type="number" name="annee" class="form-control" value="<?= date('Y') ?>" min="2018" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Agent releveur</label>
                        <input type="text" name="agent_releve" class="form-control" placeholder="Nom de l'agent">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Observations</label>
                        <input type="text" name="observations" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalReleve')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer le relevé</button>
            </div>
        </form>
    </div>
</div>

<script>
function fillCompteur(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('cpt_id').value  = opt.dataset.cpt || '';
    document.getElementById('aff_cpt').value = opt.dataset.serie || '—';
}
</script>

<?php require_once 'includes/footer.php'; ?>
