-- ============================================
-- BASE DE DONNÉES - GESTION EAU IRRIGATION
-- ============================================

CREATE DATABASE IF NOT EXISTS eau_irrigation
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE eau_irrigation;

-- TABLE: Zones d'irrigation
CREATE TABLE zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    tarif_m3 DECIMAL(10,4) NOT NULL DEFAULT 0.5000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABLE: Abonnés
CREATE TABLE abonnes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_abonne VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    cin VARCHAR(20),
    telephone VARCHAR(20),
    email VARCHAR(150),
    adresse TEXT,
    zone_id INT NOT NULL,
    superficie_ha DECIMAL(10,4) DEFAULT 0,
    date_abonnement DATE NOT NULL,
    statut ENUM('actif','suspendu','résilié') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES zones(id)
);

-- TABLE: Compteurs
CREATE TABLE compteurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_serie VARCHAR(50) NOT NULL UNIQUE,
    abonne_id INT NOT NULL,
    date_pose DATE NOT NULL,
    index_initial DECIMAL(12,3) DEFAULT 0,
    statut ENUM('actif','défectueux','remplacé') DEFAULT 'actif',
    FOREIGN KEY (abonne_id) REFERENCES abonnes(id)
);

-- TABLE: Relevés de compteur
CREATE TABLE releves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compteur_id INT NOT NULL,
    abonne_id INT NOT NULL,
    date_releve DATE NOT NULL,
    index_releve DECIMAL(12,3) NOT NULL,
    index_precedent DECIMAL(12,3) NOT NULL DEFAULT 0,
    consommation DECIMAL(12,3) GENERATED ALWAYS AS (index_releve - index_precedent) STORED,
    semestre TINYINT NOT NULL COMMENT '1 ou 2',
    annee YEAR NOT NULL,
    agent_releve VARCHAR(100),
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compteur_id) REFERENCES compteurs(id),
    FOREIGN KEY (abonne_id) REFERENCES abonnes(id)
);

-- TABLE: Tarifs (historique)
CREATE TABLE tarifs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    tranche_min DECIMAL(10,3) NOT NULL DEFAULT 0,
    tranche_max DECIMAL(10,3),
    prix_unitaire DECIMAL(10,4) NOT NULL,
    annee YEAR NOT NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id)
);

-- TABLE: Factures
CREATE TABLE factures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_facture VARCHAR(30) NOT NULL UNIQUE,
    abonne_id INT NOT NULL,
    releve_id INT NOT NULL,
    semestre TINYINT NOT NULL,
    annee YEAR NOT NULL,
    date_emission DATE NOT NULL,
    date_echeance DATE NOT NULL,
    consommation_m3 DECIMAL(12,3) NOT NULL,
    montant_ht DECIMAL(12,2) NOT NULL,
    tva_taux DECIMAL(5,2) DEFAULT 20.00,
    montant_tva DECIMAL(12,2) NOT NULL,
    redevance_fixe DECIMAL(10,2) DEFAULT 0,
    montant_ttc DECIMAL(12,2) NOT NULL,
    statut ENUM('émise','payée','impayée','annulée') DEFAULT 'émise',
    date_paiement DATE,
    mode_paiement ENUM('espèces','virement','chèque','mobile') NULL,
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (abonne_id) REFERENCES abonnes(id),
    FOREIGN KEY (releve_id) REFERENCES releves(id)
);

-- TABLE: Paiements
CREATE TABLE paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facture_id INT NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    date_paiement DATE NOT NULL,
    mode_paiement ENUM('espèces','virement','chèque','mobile') NOT NULL,
    reference_paiement VARCHAR(50),
    caissier VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facture_id) REFERENCES factures(id)
);

-- ============================================
-- DONNÉES DE DÉMONSTRATION
-- ============================================

INSERT INTO zones (code, nom, description, tarif_m3) VALUES
('Z01', 'Zone Nord', 'Périmètre irrigué nord - Cultures céréalières', 0.4500),
('Z02', 'Zone Sud', 'Périmètre irrigué sud - Maraîchage', 0.5500),
('Z03', 'Zone Est', 'Périmètre irrigué est - Arboriculture', 0.6000),
('Z04', 'Zone Ouest', 'Périmètre irrigué ouest - Polyculture', 0.5000);

INSERT INTO abonnes (numero_abonne, nom, prenom, cin, telephone, email, adresse, zone_id, superficie_ha, date_abonnement) VALUES
('AB-2020-001', 'ALAOUI', 'Mohammed', 'AB123456', '0661234567', 'malaoui@email.ma', 'Douar Ouled Driss, Commune Sidi Yahia', 1, 5.50, '2020-01-15'),
('AB-2020-002', 'BENALI', 'Fatima', 'CD789012', '0662345678', 'fbenali@email.ma', 'Douar Beni Amir, Commune El Kelaa', 1, 3.25, '2020-03-20'),
('AB-2021-003', 'CHAOUI', 'Ahmed', 'EF345678', '0663456789', 'achaoui@email.ma', 'Quartier Industriel, Route de Marrakech', 2, 8.75, '2021-02-10'),
('AB-2021-004', 'IDRISSI', 'Khadija', 'GH901234', '0664567890', 'kidrissi@email.ma', 'Douar Lalla Fatma, Commune Skhirat', 2, 2.00, '2021-06-05'),
('AB-2022-005', 'ZIANI', 'Omar', 'IJ567890', '0665678901', 'oziani@email.ma', 'Ferme Ziani, Route de Fes', 3, 12.30, '2022-01-08'),
('AB-2022-006', 'TAHIRI', 'Aicha', 'KL123456', '0666789012', 'atahiri@email.ma', 'Domaine Tahiri, Commune Aïn Leuh', 3, 6.80, '2022-04-15'),
('AB-2023-007', 'MRANI', 'Youssef', 'MN789012', '0667890123', 'ymrani@email.ma', 'Exploitation Mrani, Zone Ouest', 4, 4.50, '2023-03-01'),
('AB-2023-008', 'BELHAJ', 'Zineb', 'OP345678', '0668901234', 'zbelhaj@email.ma', 'Douar Belhaj, Route Atlantique', 4, 9.20, '2023-07-20');

INSERT INTO compteurs (numero_serie, abonne_id, date_pose, index_initial) VALUES
('CPT-2020-001', 1, '2020-01-15', 0.000),
('CPT-2020-002', 2, '2020-03-20', 0.000),
('CPT-2021-003', 3, '2021-02-10', 0.000),
('CPT-2021-004', 4, '2021-06-05', 0.000),
('CPT-2022-005', 5, '2022-01-08', 0.000),
('CPT-2022-006', 6, '2022-04-15', 0.000),
('CPT-2023-007', 7, '2023-03-01', 0.000),
('CPT-2023-008', 8, '2023-07-20', 0.000);

INSERT INTO releves (compteur_id, abonne_id, date_releve, index_releve, index_precedent, semestre, annee, agent_releve) VALUES
(1, 1, '2025-06-30', 12500.000, 10800.000, 1, 2025, 'Agent Hassan'),
(2, 2, '2025-06-30', 8200.000, 7100.000, 1, 2025, 'Agent Hassan'),
(3, 3, '2025-06-30', 25800.000, 22400.000, 1, 2025, 'Agent Karim'),
(4, 4, '2025-06-30', 5600.000, 4900.000, 1, 2025, 'Agent Karim'),
(5, 5, '2025-06-30', 38900.000, 33200.000, 1, 2025, 'Agent Said'),
(6, 6, '2025-06-30', 18700.000, 16100.000, 1, 2025, 'Agent Said'),
(7, 7, '2025-06-30', 9800.000, 8300.000, 1, 2025, 'Agent Mehdi'),
(8, 8, '2025-06-30', 22100.000, 19400.000, 1, 2025, 'Agent Mehdi');

INSERT INTO factures (numero_facture, abonne_id, releve_id, semestre, annee, date_emission, date_echeance, consommation_m3, montant_ht, tva_taux, montant_tva, redevance_fixe, montant_ttc, statut) VALUES
('FACT-2025-S1-001', 1, 1, 1, 2025, '2025-07-01', '2025-08-01', 1700.000, 765.00, 20.00, 153.00, 50.00, 968.00, 'payée'),
('FACT-2025-S1-002', 2, 2, 1, 2025, '2025-07-01', '2025-08-01', 1100.000, 495.00, 20.00, 99.00, 50.00, 644.00, 'émise'),
('FACT-2025-S1-003', 3, 3, 1, 2025, '2025-07-01', '2025-08-01', 3400.000, 1870.00, 20.00, 374.00, 50.00, 2294.00, 'impayée'),
('FACT-2025-S1-004', 4, 4, 1, 2025, '2025-07-01', '2025-08-01', 700.000, 385.00, 20.00, 77.00, 50.00, 512.00, 'payée'),
('FACT-2025-S1-005', 5, 5, 1, 2025, '2025-07-01', '2025-08-01', 5700.000, 3420.00, 20.00, 684.00, 50.00, 4154.00, 'émise'),
('FACT-2025-S1-006', 6, 6, 1, 2025, '2025-07-01', '2025-08-01', 2600.000, 1560.00, 20.00, 312.00, 50.00, 1922.00, 'payée'),
('FACT-2025-S1-007', 7, 7, 1, 2025, '2025-07-01', '2025-08-01', 1500.000, 750.00, 20.00, 150.00, 50.00, 950.00, 'émise'),
('FACT-2025-S1-008', 8, 8, 1, 2025, '2025-07-01', '2025-08-01', 2700.000, 1350.00, 20.00, 270.00, 50.00, 1670.00, 'impayée');

INSERT INTO paiements (facture_id, montant, date_paiement, mode_paiement, reference_paiement, caissier) VALUES
(1, 968.00, '2025-07-15', 'virement', 'VIR-20250715-001', 'Mme Laila'),
(4, 512.00, '2025-07-18', 'espèces', 'ESP-20250718-004', 'M. Driss'),
(6, 1922.00, '2025-07-20', 'chèque', 'CHQ-20250720-006', 'Mme Laila');
