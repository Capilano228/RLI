<?php
$pageTitle = 'Республиканский лицей-интернат | Республика Саха (Якутия)';
include 'includes/header.php';

$newsList = $pdo->query("
    SELECT n.*, u.full_name AS author_name 
    FROM news n 
    JOIN users u ON n.author_id = u.id 
    ORDER BY n.created_at DESC
")->fetchAll();

$specialAnnouncements = $pdo->query("SELECT * FROM special_announcements WHERE expires_at > NOW() ORDER BY created_at DESC")->fetchAll();
?>


<section class="hero-section">
    <div class="container hero-grid">
        <div>
            <h1 class="hero-title">РЕСПУБЛИКАНСКИЙ<br>ЛИЦЕЙ-ИНТЕРНАТ</h1>
            
            <div class="hero-phone-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                <a href="tel:+74112357710" class="hero-phone">+7 4112 35 77 10</a>
            </div>

            <div class="hero-socials">
                <a href="#" class="social-circle" style="background:#0077ff;">VK</a>
                <a href="#" class="social-circle" style="background:#26a5e4;">TG</a>
                <a href="#" class="social-circle" style="background:#0a0a8c;">SF</a>
            </div>

            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <a href="menu.php" class="btn-hero">Меню</a>
                <a href="schedule.php" class="btn-hero">Расписание</a>
                <a href="constitution.php" class="btn-hero">Конституция</a>
                <a href="activists.php" class="btn-hero">Активисты</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn-hero">Мой Кабинет</a>
                <?php else: ?>
                    <a href="login.php" class="btn-hero">Мой Кабинет</a>
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
                <img src="<?= $crestToDisplay ?>" alt="Эмблема" style="width:280px; height:280px; object-fit:cover; border-radius:50%;">
            <?php endif; ?>
        </div>
    </div>
</section>


<?php if (!empty($specialAnnouncements)): ?>
    <section style="padding: 10px 0 30px;">
        <div class="container">
            <h2 style="font-size:20px; color:var(--text-main); margin-bottom:14px;">Специальные объявления руководства</h2>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <?php foreach ($specialAnnouncements as $sa): ?>
                    <div class="special-announcement-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                            <span class="special-announcement-tag"><?= $sa['is_ball'] ? 'Специальное событие • Бал' : 'Официальное объявление' ?></span>
                            <span style="font-size:12px; color:#475569;">Актуально до: <?= date('d.m.Y H:i', strtotime($sa['expires_at'])) ?></span>
                        </div>
                        <h3 class="special-announcement-title"><?= htmlspecialchars($sa['title']) ?></h3>
                        <p class="special-announcement-text"><?= htmlspecialchars($sa['content']) ?></p>
                        <?php if ($sa['is_ball'] && isset($_SESSION['user_id'])): ?>
                            <div style="margin-top:14px;">
                                <a href="dashboard.php" class="btn-primary special-announcement-btn">Оформить билет на бал в кабинете</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>


<section style="padding: 20px 0 40px;">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:22px; color:var(--text-main);">Лента новостей и объявлений</h2>
            <?php if (count($newsList) > 3): ?>
                <div style="display:flex; gap:8px;">
                    <button onclick="slideNews(-1)" class="btn-primary" style="padding:6px 14px;">Назад</button>
                    <button onclick="slideNews(1)" class="btn-primary" style="padding:6px 14px;">Вперед</button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($newsList)): ?>
            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px;">
                <div style="background:#ffffff; border:2px solid var(--card-border); border-radius:var(--radius); padding:24px; color:var(--text-muted); text-align:center;">Пока нет объявлений</div>
                <div style="background:#ffffff; border:2px solid var(--card-border); border-radius:var(--radius); padding:24px; color:var(--text-muted); text-align:center;">Пока нет объявлений</div>
                <div style="background:#ffffff; border:2px solid var(--card-border); border-radius:var(--radius); padding:24px; color:var(--text-muted); text-align:center;">Пока нет объявлений</div>
            </div>
        <?php else: ?>
            <div style="overflow:hidden; width:100%;">
                <div id="newsSlider" style="display:flex; gap:20px; transition:transform 0.3s ease; width:100%;">
                    <?php foreach ($newsList as $item): ?>
                        <div style="min-width: calc(33.333% - 14px); flex: 0 0 calc(33.333% - 14px); background:#ffffff; border-radius:var(--radius); border:2px solid var(--card-border); overflow:hidden; cursor:pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.04);"
                             onclick="openNewsModal(<?= htmlspecialchars(json_encode($item)) ?>)">
                            <?php if (!empty($item['photo']) && file_exists(__DIR__ . '/' . $item['photo'])): ?>
                                <img src="<?= htmlspecialchars($item['photo']) ?>" alt="Фото" style="width:100%; height:160px; object-fit:cover;">
                            <?php endif; ?>
                            <div style="padding: 16px;">
                                <span style="font-size:11px; color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></span>
                                <h3 style="font-size:16px; margin: 6px 0; color:var(--text-main);"><?= htmlspecialchars($item['title']) ?></h3>
                                <p style="font-size:13px; color:var(--text-muted);"><?= mb_substr(strip_tags($item['content']), 0, 85) ?>...</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>


<section class="profiles-section" id="study" style="padding: 30px 0 70px;">
    <div class="container">
        <h2 style="font-size:24px; margin-bottom:24px; color:var(--text-main);">Профильные классы лицея</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
            <div class="profile-card-box">
                <img src="assets/img/crests/fizmat.jpg" alt="Физмат" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; margin-bottom:4px;">Физико-математический</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Углубленная математика и физика</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/engineering.jpg" alt="Инженерный" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; margin-bottom:4px;">Инженерный</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Робототехника и моделирование</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/polytech.jpg" alt="Политех" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; margin-bottom:4px;">Политехнический</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Программирование и IT</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/rosneft.jpg" alt="Роснефть" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; margin-bottom:4px;">Технический</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Профильная подготовка</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/biohim.jpg" alt="Биохим" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; margin-bottom:4px;">Биолого-химический</h3>
                    <p style="font-size:12px; color:var(--text-muted);">Естественные науки и медицина</p>
                </div>
            </div>
            <div class="profile-card-box">
                <img src="assets/img/crests/humanitarian.jpg" alt="Гумнар" style="width:68px; height:68px; object-fit:cover; border-radius:50%;">
                <div>
                    <h3 style="font-size:15px; margin-bottom:4px;">Гуманитарный</h3>
                    <p style="font-size:12px; color:var(--text-muted);">История, право и языки</p>
                </div>
            </div>
        </div>
    </div>
</section>


<div id="newsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; color:var(--text-main); padding:30px; border-radius:var(--radius); max-width:560px; width:90%; max-height:85vh; overflow-y:auto; position:relative; border:2px solid var(--primary); box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <button onclick="closeNewsModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:20px; cursor:pointer; font-weight:900;">✕</button>
        <img id="modalNewsPhoto" src="" alt="" style="width:100%; max-height:240px; object-fit:cover; border-radius:var(--radius); margin-bottom:15px; display:none;">
        <span id="modalNewsDate" style="font-size:11px; color:var(--text-muted);"></span>
        <h2 id="modalNewsTitle" style="margin:8px 0; font-size:18px; color:var(--primary);"></h2>
        <div id="modalNewsContent" style="font-size:14px; line-height:1.6; white-space: pre-wrap;"></div>
    </div>
</div>

<script>
let currentSlide = 0;
function slideNews(direction) {
    const slider = document.getElementById('newsSlider');
    if (!slider) return;
    const items = slider.children;
    if (items.length === 0) return;
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