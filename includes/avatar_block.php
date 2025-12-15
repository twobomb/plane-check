<?php

$user = getUser();
// Определение цвета аватара по первой букве имени
$first_letter = mb_strtoupper(mb_substr($user['username'], 0, 1));
$avatar_color = '#' . substr(md5($user['username']), 0, 6);

?>
<div class="user-info">
    <div class="user-avatar" style="background-color: <?php echo $avatar_color; ?>">
        <?php echo $first_letter; ?>
    </div>
    <div class="user-details">
        <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
        <div class="user-login">@<?php echo htmlspecialchars($user['login']); ?></div>
    </div>
    <a href="/logout.php">
        <i class="fas fa-sign-out-alt"></i>
        Выход
    </a>
</div>