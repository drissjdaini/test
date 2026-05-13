<?php
$pageTitle = 'Gestion des Abonnés';
require_once 'includes/header.php';
$db = getDB();

$msg = '';

// Ajout abonné
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $stmt = $db->prepare("
        INSERT INTO abonnes (numero_abonne,nom,prenom,cin,telephone,email,adresse,zone_id,superficie_ha,date_abonnement)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        trim($_POST['numero_abonne']),
        strtoupper(trim($_POST['nom'])),
        ucfirst(trim($_POST['prenom'])),
        trim($_POST['cin']),
        trim($_POST['telephone']),
        trim($_POST['email']),
        trim($_POST['adresse']),
        (int)$_POST['zone_id'],
        (float)$_POST['superficie_ha'],
        $_POST['date_abonnement'],
    ]);

    // Créer le compteur associé
    $abonne_id = $db->lastInsertId();
    $num_serie = 'CPT-' . date('Y') . '-' . str_pad($abonne_id, 3, '0', STR_PAD_LEFT);
    $db->prepare("INSERT INTO compteurs (numero_serie, abonne_id, date_pose, index_initial) VALUES (?,?,?,0)")
       ->execute([$num_serie, $abonne_id, $_POST['date_abonnement']]);

    $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Abonné ajouté avec succès. Compteur créé : ' . $num_serie . '</div>';
}

// Modification statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'statut') {
    $db->prepare("UPDATE abonnes SET statut=? WHERE id=?")
       ->execute([$_POST['statut'], (int)$_POST['id']]);
    $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Statut mis à jour.</div>';
}

// Zones pour le formulaire
$zones = $db->query("SELECT * FROM zones ORDER BY code")->fetchAll();

// Liste abonnés
$search = trim($_GET['q'] ?? '');
$zone_f = (int)($_GET['zone'] ?? 0);
$sql = "
    SELECT a.*, z.nom AS zone_nom, z.code AS zone_code,
           c.numero_serie, c.statut AS cpt_statut
    FROM abonnes a
    JOIN zones z ON z.id = a.zone_id
    LEFT JOIN compteurs c ON c.abonne_id = a.id AND c.statut = 'actif'
    WHERE 1=1
";
$params = [];
if ($search) { $sql .= " AND (a.nom LIKE ? OR a.prenom LIKE ? OR a.numero_abonne LIKE ? OR a.telephone LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]); }
if ($zone_f)  { $sql .= " AND a.zone_id = ?"; $params[] = $zone_f; }
$sql .= " ORDER BY a.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$abonnes = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-users" style="color:var(--primary);margin-right:10px;"></i>Gestion des Abonnés</h1>
        <p><?= count($abonnes) ?> abonné(s) trouvé(s)</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalAjout')">
        <i class="fas fa-plus"></i> Nouvel Abonné
    </button>
</div>

<?= $msg ?>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px;">
        <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
            <div class="search-input-wrap" style="flex:1;min-width:200px;">
                <i class="fas fa-search"></i>
                <input type="text" name="q" class="form-control" placeholder="Nom, prénom, N° abonné..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="zone" class="form-control" style="width:200px;">
                <option value="">Toutes les zones</option>
                <?php foreach ($zones as $z): ?>
                <option value="<?= $z['id'] ?>" <?= $zone_f === (int)$z['id'] ? 'selected' : '' ?>><?= htmlspecialchars($z['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer</button>
            <a href="abonnes.php" class="btn btn-outline"><i class="fas fa-times"></i> Réinitialiser</a>
        </form>
    </div>
</div>

<!-- Tableau -->
<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>N° Abonné</th>
                    <th>Nom & Prénom</th>
                    <th>Contact</th>
                    <th>Zone</th>
                    <th>Superficie</th>
                    <th>Compteur</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($abonnes as $a): ?>
                <?php
                    $badge = match($a['statut']) {
                        'actif'    => 'badge-success',
                        'suspendu' => 'badge-warning',
                        'résilié'  => 'badge-danger',
                        default    => 'badge-gray'
                    };
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($a['numero_abonne']) ?></strong></td>
                    <td>
                        <div style="font-weight:600;"><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></div>
                        <div class="text-muted"><?= htmlspecialchars($a['cin'] ?? '-') ?></div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($a['telephone'] ?? '-') ?></div>
                        <div class="text-muted"><?= htmlspecialchars($a['email'] ?? '-') ?></div>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($a['zone_code']) ?> – <?= htmlspecialchars($a['zone_nom']) ?></span></td>
                    <td><?= number_format($a['superficie_ha'],2,',',' ') ?> ha</td>
                    <td>
                        <?php if ($a['numero_serie']): ?>
                            <span style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($a['numero_serie']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($a['statut']) ?></span></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="releves.php?abonne=<?= $a['id'] ?>" class="btn btn-sm btn-primary btn-icon" title="Relevés">
                                <i class="fas fa-clipboard-list"></i>
                            </a>
                            <a href="factures.php?abonne=<?= $a['id'] ?>" class="btn btn-sm btn-success btn-icon" title="Factures">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($abonnes)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:40px;">Aucun abonné trouvé</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL AJOUT ABONNÉ -->
<div class="modal-overlay" id="modalAjout">
    <div class="modal" style="max-width:680px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user-plus" style="color:var(--primary);margin-right:8px;"></i>Nouvel Abonné</div>
            <button class="modal-close" onclick="closeModal('modalAjout')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ajouter">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">N° Abonné *</label>
                        <input type="text" name="numero_abonne" class="form-control" placeholder="AB-2025-009" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CIN</label>
                        <input type="text" name="cin" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control" placeholder="06xxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Zone *</label>
                        <select name="zone_id" class="form-control form-select" required>
                            <?php foreach ($zones as $z): ?>
                            <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['code'].' – '.$z['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Superficie (ha)</label>
                        <input type="number" name="superficie_ha" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date d'abonnement *</label>
                        <input type="date" name="date_abonnement" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAjout')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
