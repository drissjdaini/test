<?php
require_once 'config/database.php';
$numero = trim($_GET['id'] ?? '');
if (!$numero) { header('Location: factures.php'); exit; }

$db = getDB();
$stmt = $db->prepare("
    SELECT f.*,
           CONCAT(a.prenom,' ',a.nom) AS abonne_nom,
           a.numero_abonne, a.cin, a.telephone, a.email, a.adresse,
           z.nom AS zone_nom, z.code AS zone_code, z.tarif_m3,
           r.date_releve, r.index_releve, r.index_precedent, r.agent_releve,
           c.numero_serie
    FROM factures f
    JOIN abonnes a ON a.id = f.abonne_id
    JOIN zones z ON z.id = a.zone_id
    JOIN releves r ON r.id = f.releve_id
    JOIN compteurs c ON c.id = r.compteur_id
    WHERE f.numero_facture = ?
");
$stmt->execute([$numero]);
$f = $stmt->fetch();
if (!$f) { die('<p style="font-family:sans-serif;padding:40px;color:red;">Facture introuvable.</p>'); }

$badge = match($f['statut']) {
    'payée'   => ['color'=>'#059669','bg'=>'#ecfdf5','label'=>'PAYÉE'],
    'émise'   => ['color'=>'#0284c7','bg'=>'#e0f2fe','label'=>'ÉMISE'],
    'impayée' => ['color'=>'#dc2626','bg'=>'#fef2f2','label'=>'IMPAYÉE'],
    default   => ['color'=>'#64748b','bg'=>'#f1f5f9','label'=>strtoupper($f['statut'])],
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= htmlspecialchars($f['numero_facture']) ?></title>
    <link rel="stylesheet" href="/water-irrigation/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #f0f9ff; padding: 24px; }
        .print-actions { text-align:center; margin-bottom:24px; display:flex; gap:12px; justify-content:center; }
        @media print {
            body { background: white; padding: 0; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>

<div class="print-actions no-print">
    <button class="btn btn-primary btn-lg" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimer la Facture
    </button>
    <a href="factures.php" class="btn btn-outline btn-lg">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<div class="invoice-wrapper">

    <!-- EN-TÊTE -->
    <div class="invoice-header">
        <div class="invoice-logo-area">
            <div class="invoice-org">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                    <div style="width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div>
                        <h2 style="font-size:20px;font-weight:800;">AquaIrrig</h2>
                        <p style="opacity:.6;font-size:12px;">Gestion de l'Irrigation</p>
                    </div>
                </div>
                <p style="opacity:.5;font-size:12px;line-height:1.6;">
                    Office Régional de Mise en Valeur Agricole<br>
                    Service Gestion de l'Eau d'Irrigation<br>
                    Maroc
                </p>
            </div>
            <div class="invoice-title-area">
                <div class="facture-label">Facture d'Eau d'Irrigation</div>
                <div class="facture-num"><?= htmlspecialchars($f['numero_facture']) ?></div>
                <div style="margin-top:12px;">
                    <span style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:1px;">
                        <?= $badge['label'] ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="invoice-meta-grid">
            <div class="invoice-meta-item">
                <div class="meta-label">Date d'émission</div>
                <div class="meta-value"><?= date('d/m/Y', strtotime($f['date_emission'])) ?></div>
            </div>
            <div class="invoice-meta-item">
                <div class="meta-label">Date d'échéance</div>
                <div class="meta-value"><?= date('d/m/Y', strtotime($f['date_echeance'])) ?></div>
            </div>
            <div class="invoice-meta-item">
                <div class="meta-label">Période facturée</div>
                <div class="meta-value"><?= getSemestreLabel($f['semestre'], $f['annee']) ?></div>
            </div>
        </div>
    </div>

    <!-- CORPS -->
    <div class="invoice-body">

        <!-- Parties -->
        <div class="invoice-parties">
            <div class="party-box">
                <h4><i class="fas fa-building" style="margin-right:6px;"></i>Émetteur</h4>
                <div class="name">Office AquaIrrig</div>
                <p>
                    Zone <?= htmlspecialchars($f['zone_code']) ?> – <?= htmlspecialchars($f['zone_nom']) ?><br>
                    Tarif unitaire : <?= number_format($f['tarif_m3'],4,',',' ') ?> MAD/m³<br>
                    ICE : 001234567000021<br>
                    Tél : 05 25 XX XX XX
                </p>
            </div>
            <div class="party-box">
                <h4><i class="fas fa-user" style="margin-right:6px;"></i>Abonné</h4>
                <div class="name"><?= htmlspecialchars($f['abonne_nom']) ?></div>
                <p>
                    N° Abonné : <?= htmlspecialchars($f['numero_abonne']) ?><br>
                    CIN : <?= htmlspecialchars($f['cin'] ?? '—') ?><br>
                    Tél : <?= htmlspecialchars($f['telephone'] ?? '—') ?><br>
                    <?= htmlspecialchars($f['adresse'] ?? '') ?>
                </p>
            </div>
        </div>

        <!-- Infos Compteur -->
        <div style="background:var(--primary-light);border-radius:var(--radius);padding:16px;margin-bottom:24px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--primary-dark);font-weight:600;margin-bottom:4px;">N° Compteur</div>
                <div style="font-weight:700;font-family:monospace;"><?= htmlspecialchars($f['numero_serie']) ?></div>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--primary-dark);font-weight:600;margin-bottom:4px;">Date relevé</div>
                <div style="font-weight:700;"><?= date('d/m/Y', strtotime($f['date_releve'])) ?></div>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--primary-dark);font-weight:600;margin-bottom:4px;">Index précédent</div>
                <div style="font-weight:700;"><?= number_format($f['index_precedent'],3,',',' ') ?> m³</div>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--primary-dark);font-weight:600;margin-bottom:4px;">Index actuel</div>
                <div style="font-weight:700;"><?= number_format($f['index_releve'],3,',',' ') ?> m³</div>
            </div>
        </div>

        <!-- Détail facturation -->
        <div class="invoice-table-wrap" style="margin-bottom:24px;">
            <table>
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th style="text-align:right;">Quantité</th>
                        <th style="text-align:right;">Prix Unitaire</th>
                        <th style="text-align:right;">Montant HT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="font-weight:600;">Consommation Eau d'Irrigation</div>
                            <div class="text-muted">
                                Période : <?= getSemestreLabel($f['semestre'],$f['annee']) ?><br>
                                Relevé du <?= date('d/m/Y',strtotime($f['date_releve'])) ?>
                                · Agent : <?= htmlspecialchars($f['agent_releve'] ?? '—') ?>
                            </div>
                        </td>
                        <td style="text-align:right;font-weight:700;">
                            <?= number_format($f['consommation_m3'],3,',',' ') ?> m³
                        </td>
                        <td style="text-align:right;"><?= number_format($f['tarif_m3'],4,',',' ') ?> MAD</td>
                        <td style="text-align:right;font-weight:700;"><?= number_format($f['montant_ht'],2,',',' ') ?> MAD</td>
                    </tr>
                    <?php if ($f['redevance_fixe'] > 0): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;">Redevance fixe d'abonnement</div>
                            <div class="text-muted">Forfait semestriel</div>
                        </td>
                        <td style="text-align:right;font-weight:700;">1</td>
                        <td style="text-align:right;"><?= number_format($f['redevance_fixe'],2,',',' ') ?> MAD</td>
                        <td style="text-align:right;font-weight:700;"><?= number_format($f['redevance_fixe'],2,',',' ') ?> MAD</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Totaux -->
        <div class="invoice-totals">
            <div class="totals-box">
                <div class="total-line">
                    <span class="label">Sous-total HT</span>
                    <strong><?= number_format($f['montant_ht'],2,',',' ') ?> MAD</strong>
                </div>
                <div class="total-line">
                    <span class="label">Redevance fixe</span>
                    <strong><?= number_format($f['redevance_fixe'],2,',',' ') ?> MAD</strong>
                </div>
                <div class="total-line">
                    <span class="label">TVA (<?= number_format($f['tva_taux'],0) ?>%)</span>
                    <strong><?= number_format($f['montant_tva'],2,',',' ') ?> MAD</strong>
                </div>
                <div class="total-line">
                    <span class="label">TOTAL TTC</span>
                    <strong><?= number_format($f['montant_ttc'],2,',',' ') ?> MAD</strong>
                </div>
            </div>
        </div>

        <!-- Note de bas de page -->
        <div class="invoice-footer-note">
            <strong><i class="fas fa-info-circle" style="color:var(--primary);margin-right:6px;"></i>Modalités de paiement :</strong>
            Règlement à effectuer avant le <strong><?= date('d/m/Y',strtotime($f['date_echeance'])) ?></strong> par virement bancaire, chèque, espèces ou mobile payment.
            En cas de non-paiement à l'échéance, une pénalité de retard de <strong>1,5%</strong> par mois sera appliquée.
            <br><br>
            <strong>RIB :</strong> 021 780 1234567890 12 · <strong>Banque :</strong> Attijariwafa Bank · <strong>Contact :</strong> 05 25 XX XX XX
        </div>

        <?php if ($f['statut'] === 'payée'): ?>
        <div style="text-align:center;margin-top:24px;padding:20px;background:var(--success-light);border-radius:var(--radius);border:2px solid #a7f3d0;">
            <div style="font-size:36px;color:var(--accent);margin-bottom:8px;"><i class="fas fa-stamp"></i></div>
            <div style="font-size:18px;font-weight:800;color:var(--accent-dark);letter-spacing:2px;">PAYÉE</div>
            <div style="font-size:13px;color:var(--accent-dark);margin-top:4px;">
                Le <?= $f['date_paiement'] ? date('d/m/Y',strtotime($f['date_paiement'])) : '—' ?>
                <?= $f['mode_paiement'] ? '· ' . ucfirst($f['mode_paiement']) : '' ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- end invoice-body -->
</div><!-- end invoice-wrapper -->

</body>
</html>
