<?php
/**
 * Installer
 * Author: SamDevX
 */

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

$step = $_GET['step'] ?? 1;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

function view($file, $data = []) {
    extract($data);
    require __DIR__ . "/views/$file.php";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/process.php';
    exit;
}

switch ($step) {
    case 1:
        view('step1');
        break;
    case 2:
        view('step2');
        break;
    case 3:
        view('step3');
        break;
    case 4:
        view('step4'); // Success page
        break;
    default:
        view('step1');
        break;
}
