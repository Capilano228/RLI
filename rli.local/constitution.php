<?php
$pageTitle = 'Конституция РЛИ';
include 'includes/header.php';

// Получаем законы, добавленные Парламентом
$customLaws = $pdo->query("SELECT * FROM laws ORDER BY created_at DESC")->fetchAll();
?>

<div class="container" style="padding: 40px 0; max-width: 860px;">
    <div style="background:#ffffff; padding:40px; border-radius:4px; border:2px solid var(--card-border); box-shadow:var(--shadow);">
        <h1 style="text-align:center; font-size:22px; font-weight:900; margin-bottom:6px;">КОНСТИТУЦИЯ</h1>
        <h2 style="text-align:center; font-size:15px; font-weight:normal; color:var(--text-muted); margin-bottom:30px;">РЕСПУБЛИКАНСКОГО ЛИЦЕЯ – ИНТЕРНАТА</h2>
        
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:24px; line-height:1.7;">Мы, учащиеся из разных улусов и городов Республики Саха (Якутия), объединённые общей идеей, во имя воспитания в себе активного отношения к жизни, во благо развития родного лицея, приняли настоящую Конституцию.</p>

        <hr style="border:0; border-top:1.5px solid var(--border-color); margin:24px 0;">

        <!-- ДОПОЛНИТЕЛЬНЫЕ ЗАКОНЫ ПАРЛАМЕНТА -->
        <?php if (!empty($customLaws)): ?>
            <h3 style="font-size:15px; text-transform:uppercase; color:var(--primary); margin-bottom:14px;">Законы и акты Парламента РЛИ</h3>
            <?php foreach ($customLaws as $law): 
                // Проверяем, создан ли закон за последние 3 дня
                $isNew = (time() - strtotime($law['created_at'])) < (3 * 24 * 60 * 60);
            ?>
                <div style="background:#f4f7fb; padding:16px; border-radius:4px; border-left:4px solid var(--primary); margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <h4 style="font-size:15px; color:#000;"><?= htmlspecialchars($law['title']) ?></h4>
                        <?php if ($isNew): ?>
                            <span style="background:#ffe3e3; color:#a61b1b; font-size:11px; font-weight:900; padding:2px 8px; border-radius:4px;">(новый)</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:13px; line-height:1.5; white-space: pre-wrap;"><?= htmlspecialchars($law['content']) ?></p>
                    <span style="font-size:10px; color:var(--text-muted); display:block; margin-top:6px;"><?= date('d.m.Y H:i', strtotime($law['created_at'])) ?></span>
                </div>
            <?php endforeach; ?>
            <hr style="border:0; border-top:1.5px solid var(--border-color); margin:24px 0;">
        <?php endif; ?>

        <h3 style="font-size:14px; text-transform:uppercase; color:var(--text-muted); margin-bottom:14px;">Раздел первый</h3>
        
        <h4 style="font-size:15px; margin-bottom:8px;">Глава 1. Основы конституционного строя</h4>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 1.</strong> РЛИ есть демократическое правовое сообщество с республиканской формой правления. Источником власти является лицейский народ.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 2.</strong> Ученик, его права и свободы являются высшей ценностью РЛИ.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статья 9.</strong> Самоуправление осуществляется на основе разделения на законодательную, исполнительную и судебную власть.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 2. Права и свободы лицеиста</h4>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 10.</strong> Каждый лицеист имеет право на свободное выражение своего мнения, участие в лицейских мероприятиях и защиту своих интересов.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статья 18.</strong> Лицеисты обязаны соблюдать дисциплину, уважать труд преподавателей и товарищей, а также беречь имущество лицея.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 3. Лицейское устройство и денежная система</h4>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 30.</strong> Основной денежной единицей РЛИ является <strong>лира</strong>. Эмиссия осуществляется исключительно Центральным банком и Парламентом РЛИ.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статья 31.</strong> Основным языком РЛИ является якутский. РЛИ признаёт государственный язык РФ и способствует многоязычию.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 4. Законодательная власть (Парламент)</h4>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статьи 34–45.</strong> Парламент РЛИ разрабатывает законы, устанавливает ставки зарплат (лир) за труд и регулирует финансовую систему лицея.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 5. Исполнительная власть (Правительство)</h4>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статьи 46–55.</strong> Правительство во главе с Премьер-министром осуществляет исполнительную власть, организует работу лицейских служб и ведет новостную ленту.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 6. Судебная власть и СБРЛ</h4>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статьи 56–70.</strong> Служба безопасности РЛИ (СБРЛ) и судебные органы поддерживают порядок, разрешают споры и осуществляют учет дисциплинарных штрафов.</p>

        <hr style="border:0; border-top:1.5px solid var(--border-color); margin:24px 0;">
        <h3 style="font-size:14px; text-transform:uppercase; color:var(--text-muted); margin-bottom:14px;">Раздел второй. Заключительные положения</h3>
        <p style="font-size:13px;">Конституция вступает в силу в день подписания её директором РЛИ — И. И. Шамаевым.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>