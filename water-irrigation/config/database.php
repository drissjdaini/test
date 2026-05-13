<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'eau_irrigation');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'AquaIrrig');
define('APP_SLOGAN', 'Gestion Intelligente de l\'Irrigation');
define('TVA_TAUX', 20.00);
define('REDEVANCE_FIXE', 50.00);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion échouée: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function formatMontant(float $montant): string {
    return number_format($montant, 2, ',', ' ') . ' MAD';
}

function formatConsommation(float $m3): string {
    return number_format($m3, 3, ',', ' ') . ' m³';
}

function getSemestreLabel(int $s, int $annee): string {
    if ($s === 1) return "1er Semestre $annee (Jan–Juin)";
    return "2ème Semestre $annee (Juil–Déc)";
}
