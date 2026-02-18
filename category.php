<?php
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

require_once __DIR__ . '/config/config.php';
$category = getCategoryBySlug($slug);
if (!$category) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

// Redirect to products page with category filter
header('Location: ' . BASE_URL . '/products.php?category=' . urlencode($slug));
exit;
