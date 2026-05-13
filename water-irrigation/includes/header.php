<?php
require_once __DIR__ . '/../config/database.php';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> – <?= $pageTitle ?? 'Tableau de Bord' ?></title>
    <link rel="stylesheet" href="/water-irrigation/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-tint"></i></div>
            <div class="logo-text">
                <span class="logo-name"><?= APP_NAME ?></span>
                <span class="logo-sub">Irrigation</span>
            </div>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="sidebar-menu">
        <div class="menu-section">
            <span class="menu-label">PRINCIPAL</span>
            <a href="/water-irrigation/index.php" class="menu-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Tableau de Bord</span>
            </a>
        </div>

        <div class="menu-section">
            <span class="menu-label">GESTION</span>
            <a href="/water-irrigation/abonnes.php" class="menu-item <?= $currentPage === 'abonnes' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Abonnés</span>
            </a>
            <a href="/water-irrigation/compteurs.php" class="menu-item <?= $currentPage === 'compteurs' ? 'active' : '' ?>">
                <i class="fas fa-gauge-high"></i>
                <span>Compteurs</span>
            </a>
            <a href="/water-irrigation/releves.php" class="menu-item <?= $currentPage === 'releves' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Relevés</span>
            </a>
            <a href="/water-irrigation/zones.php" class="menu-item <?= $currentPage === 'zones' ? 'active' : '' ?>">
                <i class="fas fa-map-marked-alt"></i>
                <span>Zones</span>
            </a>
        </div>

        <div class="menu-section">
            <span class="menu-label">FACTURATION</span>
            <a href="/water-irrigation/factures.php" class="menu-item <?= $currentPage === 'factures' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Factures</span>
            </a>
            <a href="/water-irrigation/paiements.php" class="menu-item <?= $currentPage === 'paiements' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Paiements</span>
            </a>
            <a href="/water-irrigation/generate_factures.php" class="menu-item <?= $currentPage === 'generate_factures' ? 'active' : '' ?>">
                <i class="fas fa-bolt"></i>
                <span>Générer Factures</span>
            </a>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><i class="fas fa-user-shield"></i></div>
            <div>
                <div class="user-name">Administrateur</div>
                <div class="user-role">Gestionnaire</div>
            </div>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="main-wrapper" id="mainWrapper">
    <!-- TOP BAR -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                <span><?= $pageTitle ?? 'Tableau de Bord' ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <?= date('d/m/Y') ?>
            </div>
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notif-badge">3</span>
            </div>
        </div>
    </header>

    <div class="page-content">
