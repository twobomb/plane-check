<button class="menu-toggle" id="menuToggle">
    <i class="fas fa-bars"></i>
</button>
<!-- Оверлей для закрытия меню -->
<div class="menu-overlay" id="menuOverlay"></div>

<!-- Боковое меню -->
<div class="menu-sidebar" id="menuSidebar">
    <div class="menu-header">
        <h2><i class="fas fa-bars"></i> Меню системы</h2>
        <button class="menu-close" id="menuClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="menu-content">
        <div class="menu-section">
            <h3>Основная навигация</h3>
            <a href="/" class="menu-item ">
                <i class="fas fa-home"></i>
                <span>Главная</span>
            </a>
        </div>

        <div class="menu-section">
            <h3>Данные</h3>

            <a href="/plan.php" class="menu-item">
                <i class="fas fa-list-check"></i>
                <span>Планы</span>
                <!--<span class="menu-badge">47</span>-->
            </a>

            <a href="/project-list.php" class="menu-item">
                <i class="fas fa-layer-group"></i>
                <span>Проекты карт</span>
                <!--<span class="menu-badge">47</span>-->
            </a>

            <a href="http://map.mchs.lnr" class="menu-item">
                <i class="fa-solid fa-map-location-dot"></i>
                <span>Карта</span>
                <!--<span class="menu-badge">47</span>-->
            </a>
        </div>

        <?PHP if(canAccess(CORE::ROLE_USERCONTROL)): ?>
        <div class="menu-section">
            <h3>Управление</h3>
            <a href="/usercontrol.php" class="menu-item">
                <i class="fas fa-users-cog"></i>
                <span>Пользователи</span>
            </a>
        </div>
        <?PHP endif;  ?>

    </div>

    <div class="menu-footer">
        <div class="menu-footer-info">
            <?php include "includes/avatar_block.php"?>
        </div>
    </div>
</div>