<?php
// Client Sidebar Partial
$user = $user ?? currentUser();
$initials = strtoupper(mb_substr($user['first_name'] ?? 'U', 0, 1));
?>
<aside class="client-sidebar">
    <div class="client-user-info">
        <div class="client-avatar">
            <?= $initials ?>
        </div>
        <h4>
            <?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>
        </h4>
        <p>
            <?= e($user['email'] ?? '') ?>
        </p>
    </div>
    <ul class="client-nav">
        <li><a href="<?= BASE_URL ?>/client/" class="<?= ($activePage ?? '') == 'dashboard' ? 'active' : '' ?>"><i
                    class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>/client/orders.php"
                class="<?= ($activePage ?? '') == 'orders' ? 'active' : '' ?>"><i class="fas fa-shopping-bag"></i>
                Siparişlerim</a></li>
        <li><a href="<?= BASE_URL ?>/client/profile.php"
                class="<?= ($activePage ?? '') == 'profile' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i>
                Profil</a></li>
        <li><a href="<?= BASE_URL ?>/client/addresses.php"
                class="<?= ($activePage ?? '') == 'addresses' ? 'active' : '' ?>"><i class="fas fa-map-marker-alt"></i>
                Adreslerim</a></li>
        <li><a href="<?= BASE_URL ?>/client/wishlist.php"
                class="<?= ($activePage ?? '') == 'wishlist' ? 'active' : '' ?>"><i class="fas fa-heart"></i>
                Favorilerim</a></li>
        <li><a href="<?= BASE_URL ?>/client/logout.php" style="color:var(--danger)"><i class="fas fa-sign-out-alt"></i>
                Çıkış Yap</a></li>
    </ul>
</aside>