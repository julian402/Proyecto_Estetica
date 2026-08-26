<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Treatment.php';
require_once __DIR__ . '/models/User.php';

start_session();

// Datos para los templates
$currentUser  = current_user();
$treatments   = Treatment::getAll();
$esteticistas = User::getEsteticistas();

// Incluir templates en orden
require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/hero.php';
require __DIR__ . '/templates/treatments.php';
require __DIR__ . '/templates/booking.php';
require __DIR__ . '/templates/modals.php';
require __DIR__ . '/templates/footer.php';
