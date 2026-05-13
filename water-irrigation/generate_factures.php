<?php
$pageTitle = 'Générer les Factures Semestrielles';
require_once 'includes/header.php';
$db = getDB();

$msg    = '';
$result = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generer') {
    $semestre     = (int)$_POST['semestre'];
    $annee        = (int)$_POST['annee'];
    $date_emission = $_POST['date_emission'];
    $date_echeance = $_POST['date_echeance'];
    $redevance    = (float)($_POST['redevance_fixe'] ?? REDEVANCE_FIXE);
    $tva          = (float)($_POST['tva_taux'] ?? TVA_TAUX);

    // Relevés du semestre sans facture
    $releves = $db->prepare("
        SELECT r.id AS releve_id, r.abonne_id, r.consommation, r.semestre, r.annee,
               a.numero_abonne, a.prenom, a.nom,
               z.tarif_m3
        FROM releves r
        JOIN abonnes a ON a.id = r.abonne_id
        JOIN zones z ON z.id = a.zone_id
        WHERE r.semestre = ? AND r.annee = ?
          AND NOT EXISTS (
              SELECT 1 FROM factures f WHERE f.releve_id = r.id
          )
        ORDER BY a.nom
    ");
    $releves->execute([$semestre, $annee]);
    $liste = $releves->fetchAll();

    if (empty($liste)) {
        $msg = '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Aucun relevé disponible pour ce semestre sans facture existante.</div>';
    } else {
        $generees = 0;
        foreach ($liste as $r) {
            $conso     = (float)$r['consommation'];
            $tarif     = (float)$r['tarif_m3'];
            $montant_ht = round($conso * $tarif, 2);
            $tva_mnt   = round($montant_ht * $tva / 100, 2);
            $montant_ttc = round($montant_ht + $tva_mnt + $redevance, 2);

            $num = sprintf('FACT-%d-S%d-%03d', $annee, $semestre, $r['releve_id']);

            // Vérifier si pas déjà créé dans cette session
            $exists = $db->prepare("SELECT COUNT(*) FROM factures WHERE numero_facture = ?");
            $exists->execute([$num]);
            if ($exists->fetchColumn() > 0) continue;

            $db->prepare("
                INSERT INTO factures
                    (numero_facture, abonne_id, releve_id, semestre, annee,
                     date_emission, date_echeance, consommation_m3,
                     montant_ht, tva_taux, montant_tva, redevance_fixe, montant_ttc, statut)
                VALUES (?,?,?,?,?, ?,?,?, ?,?,?,?,?,'émise')
            ")->execute([
                $num, $r['abonne_id'], $r['releve_id'], $semestre, $annee,
                $date_emission, $date_echeance, $conso,
                $montant_ht, $tva, $tva_mnt, $redevance, $montant_ttc
            ]);

            $result[] = [
                'num'     => $num,
                'abonne'  => $r['prenom'].' '.$r['nom'],
                'numero'  => $r['numero_abonne'],
                'conso'   => $conso,
                'ht'      => $montant_ht,
                'tva'     => $tva_mnt,
                'rev'     => $redevance,
                'ttc'     => $montant_ttc,
            ];
            $generees++;
        }

        if ($generees > 0) {
            $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> '
                 . $generees . ' facture(s) générée(s) avec succès pour '
                 . getSemestreLabel($semestre, $annee) . '</div>';
        } else {
            $msg = '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Toutes les factures existaient déjà.</div>';
        }
    }
}

// Compter relevés disponibles par semestre
$disponibles = $db->query("
    SELECT r.semestre, r.annee, COUNT(*) AS nb
    FROM releves r
    WHERE NOT EXISTS (SELECT 1 FROM factures f WHERE f.releve_id = r.id)
    GROUP BY r.semestre, r.annee
    ORDER BY r.annee DESC, r.semestre DESC
")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-bolt" style="color:var(--warning);margin-right:10px;"></i>Génération des Factures Semestrielles</h1>
        <p>Créer automatiquement les factures à partir des relevés enregistrés</p>
    </div>
    <a href="factures.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Retour aux Factures</a>
</div>

<?= $msg ?>

<div class="grid-2" style="gap:24px;align-items:start;">

    <!-- Formulaire -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-sliders-h"></i> Paramètres de Facturation</div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="generer">
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Semestre *</label>
                    <select name="semestre" class="form-control form-select" required>
                        <option value="1">1er Semestre (Janvier – Juin)</option>
                        <option value="2">2ème Semestre (Juillet – Décembre)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Année *</label>
                    <input type="number" name="annee" class="form-control" value="<?= date('Y') ?>" min="2018" max="2030" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Date d'émission *</label>
                    <input type="date" name="date_emission" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Date d'échéance *</label>
                    <input type="date" name="date_echeance" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Redevance fixe (MAD)</label>
                    <input type="number" name="redevance_fixe" class="form-control" value="<?= REDEVANCE_FIXE ?>" step="0.01">
                </div>
                <div class="form-group" style="margin-bottom:24px;">
                    <label class="form-label">Taux TVA (%)</label>
                    <input type="number" name="tva_taux" class="form-control" value="<?= TVA_TAUX ?>" step="0.01" min="0" max="100">
                </div>

                <div style="background:var(--warning-light);border-radius:var(--radius-sm);padding:14px;border:1px solid #fde68a;margin-bottom:20px;font-size:13px;color:#92400e;">
                    <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
                    <strong>Important :</strong> La génération créera une facture par relevé enregistré pour la période choisie, si aucune facture n'existe déjà pour ce relevé.
                </div>

                <button type="submit" class="btn btn-success btn-lg" style="width:100%;">
                    <i class="fas fa-magic"></i> Générer les Factures
                </button>
            </form>
        </div>
    </div>

    <!-- Disponibilité -->
    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-info-circle"></i> Relevés Sans Facture</div>
            </div>
            <div class="card-body">
                <?php if (empty($disponibles)): ?>
                    <p class="text-muted text-center" style="padding:20px;">Tous les relevés ont été facturés.</p>
                <?php else: ?>
                    <?php foreach ($disponibles as $d): ?>
                    <div class="d-flex justify-between align-center" style="padding:12px 0;border-bottom:1px solid var(--border-light);">
                        <div>
                            <div style="font-weight:600;"><?= getSemestreLabel($d['semestre'],$d['annee']) ?></div>
                            <div class="text-muted"><?= $d['nb'] ?> relevé(s) non facturé(s)</div>
                        </div>
                        <span class="badge badge-warning"><i class="fas fa-clock"></i> En attente</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Résultat génération -->
        <?php if (!empty($result)): ?>
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-check-circle" style="color:var(--accent);"></i> Factures Générées</div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Abonné</th>
                            <th>Conso (m³)</th>
                            <th>TTC (MAD)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['num']) ?></strong></td>
                            <td>
                                <div><?= htmlspecialchars($r['abonne']) ?></div>
                                <div class="text-muted"><?= htmlspecialchars($r['numero']) ?></div>
                            </td>
                            <td><?= number_format($r['conso'],3,',',' ') ?></td>
                            <td><strong style="color:var(--primary);"><?= number_format($r['ttc'],2,',',' ') ?></strong></td>
                            <td>
                                <a href="print_facture.php?id=<?= urlencode($r['num']) ?>" class="btn btn-sm btn-primary btn-icon">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding:16px;text-align:right;border-top:1px solid var(--border);">
                <strong>Total TTC : <?= number_format(array_sum(array_column($result,'ttc')),2,',',' ') ?> MAD</strong>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
