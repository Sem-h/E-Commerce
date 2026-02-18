<?php
require_once __DIR__ . '/../config/config.php';
session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
