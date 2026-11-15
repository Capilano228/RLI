<?php
$pageTitle = 'Республиканский лицей-интернат | Республика Саха (Якутия)';
include 'includes/header.php';

$newsList = $pdo->query("
    SELECT n.*, u.full_name AS author_name 
    FROM news n 
    JOIN users u ON n.author_id = u.id 
    ORDER BY n.created_at DESC
")->fetchAll();
?>

<!-- Статичные фоновые линии только на главной странице -->
<div class="global-bg-lines">
    <svg viewBox="0 0 1440 900" fill="none" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path class="bg-curve" d="M 320 -50 C 780 350, 920 650, -40 750" />
        <path class="bg-curve" d="M 1480 500 C 1120 750, 680 900, -40 950" />
    </svg>
</div>

<!-- Главный экран -->
<section class="hero-section">
    <div class="container hero-grid">
        <div>
            <h1 class="hero-title">РЕСПУБЛИКАНСКИЙ<br>ЛИЦЕЙ-ИНТЕРНАТ</h1>
            <div class="hero-contacts">
                <a href="tel:+74112357710" class="hero-phone" style="font-weight:600; color:var(--text-main); text-decoration:none; font-size:15px;">+7 4112 35 77 10</a>
                <div class="hero-socials" style="display:flex; gap:8px; margin-top:12px;">
                    <a href="#" style="background:#0077ff; color:#fff; width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-size:11px; font-weight:700;">VK</a>
                    <a href="#" style="background:#229ed9; color:#fff; width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-size:11px; font-weight:700;">TG</a>
                    <a href="#" style="background:#111184; color:#fff; width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-size:11px; font-weight:700;">SF</a>
                </div>
            </div>
            <div class="hero-buttons" style="margin-top:22px; display:flex; align-items:center; gap:20px;">
                <a href="menu.php" style="color:var(--text-main); text-decoration:none; font-weight:600; font-size:13px;">Меню столовой</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn-primary">Мой Кабинет</a>
                <?php else: ?>
                    <a href="login.php" class="btn-primary">Мой Кабинет</a>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex; justify-content:center; align-items:center;">
            <?php 
                $crestToDisplay = 'assets/img/crests/main_emblem.jpg';
                if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student' && !empty($_SESSION['profile'])) {
                    $profileKey = $_SESSION['profile'];
                    $profileCrestPath = 'assets/img/crests/' . $profileKey . '.jpg';
                    if (file_exists(__DIR__ . '/' . $profileCrestPath)) {
                        $crestToDisplay = $profileCrestPath;
                    }
                }
                if (file_exists(__DIR__ . '/' . $crestToDisplay)): 
            ?>
                <img src="<?= $crestToDisplay ?>" alt="Герб" style="width:260px; height:260px; object-fit:cover; border-radius:50%; box-shadow:var(--shadow);">
            <?php else: ?>
                <div style="width:240px; height:240px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; text-align:center; padding:20px; box-shadow:var(--shadow);">
                    РЛИ Якутия
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Лента новостей с каруселью по 3 блока -->
<section style="padding: 30px 0 40px;">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:22px; font-weight:700; color:var(--text-main);">Лента новостей и объявлений</h2>
            <?php if (count($newsList) > 3): ?>
                <div style="display:flex; gap:8px;">
                    <button onclick="slideNews(-1)" style="background:var(--bg-surface); border:1px solid var(--border-color); width:36px; height:36px; border-radius:50%; cursor:pointer; font-weight:bold; color:var(--primary);">‹</button>
                    <button onclick="slideNews(1)" style="background:var(--bg-surface); border:1px solid var(--border-color); width:36px; height:36px; border-radius:50%; cursor:pointer; font-weight:bold; color:var(--primary);">›</button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($newsList)): ?>
            <p style="color:var(--text-muted); background:rgba(255,255,255,0.85); padding:16px 20px; border-radius:8px; display:inline-block;">Пока нет объявлений</p>
        <?php else: ?>
            <div style="overflow:hidden; width:100%;">
                <div id="newsSlider" style="display:flex; gap:20px; transition:transform 0.3s ease; width:100%;">
                    <?php foreach ($newsList as $item): ?>
                        <div style="min-width: calc(33.333% - 14px); flex: 0 0 calc(33.333% - 14px); background:rgba(255,255,255,0.95); border-radius:var(--radius); border:1.5px solid var(--line-color); overflow:hidden; cursor:pointer; box-shadow:var(--shadow);"
                             onclick="openNewsModal(<?= htmlspecialchars(json_encode($item)) ?>)">
                            <?php if (!empty($item['photo']) && file_exists(__DIR__ . '/' . $item['photo'])): ?>
                                <img src="<?= htmlspecialchars($item['photo']) ?>" alt="Фото" style="width:100%; height:160px; object-fit:cover;">
                            <?php endif; ?>
                            <div style="padding: 16px;">
                                <span style="font-size:11px; color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></span>
                                <h3 style="font-size:16px; font-weight:600; margin: 6px 0; color:var(--primary);"><?= htmlspecialchars($item['title']) ?></h3>
                                <p style="font-size:13px; color:var(--text-muted);"><?= mb_substr(strip_tags($item['content']), 0, 85) ?>...</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Профильные классы лицея -->
<section class="profiles-section" id="study" style="padding: 30px 0 70px;">
    <div class="container">
        <h2 style="font-size:22px; font-weight:700; margin-bottom:24px; color:var(--text-main);">Профильные классы лицея</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 22px;">
            <div class="profile-card-box">
                <img src="assets/img/crests/fizmat.jpg" alt="Физмат" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; font-weight:600; margin-bottom:4px;">Физико-математический</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Углубленная математика и физика</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/engineering.jpg" alt="Инженерный" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; font-weight:600; margin-bottom:4px;">Инженерный</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Робототехника и моделирование</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/polytech.jpg" alt="Политех" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; font-weight:600; margin-bottom:4px;">Политехнический (PTCH)</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Программирование и IT</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/rosneft.jpg" alt="Роснефть" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; font-weight:600; margin-bottom:4px;">Технический (Роснефть)</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Профильная подготовка</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/biohim.jpg" alt="Биохим" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; font-weight:600; margin-bottom:4px;">Биолого-химический</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Естественные науки и медицина</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/humanitarian.jpg" alt="Гумнар" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; font-weight:600; margin-bottom:4px;">Гуманитарный (ГУМНАР)</h3>
                    <p style="font-size:12px; color:var(--text-muted);">История, право и языки</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Модальное окно новости -->
<div id="newsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; color:#102a43; padding:30px; border-radius:8px; max-width:560px; width:90%; max-height:85vh; overflow-y:auto; position:relative;">
        <button onclick="closeNewsModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:20px; cursor:pointer;">✕</button>
        <img id="modalNewsPhoto" src="" alt="" style="width:100%; max-height:240px; object-fit:cover; border-radius:4px; margin-bottom:15px; display:none;">
        <span id="modalNewsDate" style="font-size:11px; color:#627d98;"></span>
        <h2 id="modalNewsTitle" style="margin:8px 0; font-size:18px; font-weight:600;"></h2>
        <div id="modalNewsContent" style="font-size:14px; line-height:1.6; white-space: pre-wrap;"></div>
    </div>
</div>

<script>
let currentSlide = 0;
function slideNews(direction) {
    const slider = document.getElementById('newsSlider');
    if (!slider) return;
    const items = slider.children;
    const maxSlide = Math.max(0, items.length - 3);
    currentSlide += direction;
    if (currentSlide < 0) currentSlide = 0;
    if (currentSlide > maxSlide) currentSlide = maxSlide;
    const offset = currentSlide * (items[0].offsetWidth + 20);
    slider.style.transform = `translateX(-${offset}px)`;
}

function openNewsModal(item) {
    document.getElementById('modalNewsTitle').innerText = item.title;
    document.getElementById('modalNewsContent').innerText = item.content;
    document.getElementById('modalNewsDate').innerText = 'Дата: ' + item.created_at;
    const img = document.getElementById('modalNewsPhoto');
    if (item.photo) {
        img.src = item.photo;
        img.style.display = 'block';
    } else {
        img.style.display = 'none';
    }
    document.getElementById('newsModal').style.display = 'flex';
}
function closeNewsModal() {
    document.getElementById('newsModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>