<?php
$pageTitle = 'Конституция РЛИ';
include 'includes/header.php';

$customLaws = $pdo->query("SELECT * FROM laws ORDER BY created_at DESC")->fetchAll();
?>

<div class="container" style="padding: 40px 0; max-width: 860px;">
    <div style="background:#ffffff; padding:40px; border-radius:4px; border:2px solid var(--card-border); box-shadow:var(--shadow);">
        <h1 style="text-align:center; font-size:22px; font-weight:900; margin-bottom:6px;">КОНСТИТУЦИЯ</h1>
        <h2 style="text-align:center; font-size:15px; font-weight:normal; color:var(--text-muted); margin-bottom:30px;">РЕСПУБЛИКАНСКОГО ЛИЦЕЯ – ИНТЕРНАТА</h2>
        
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:24px; line-height:1.7;">Мы, учащиеся из разных улусов и городов Республики Саха (Якутия), объединённые общей идеей, во имя воспитания в себе активного отношения к жизни, во благо развития родного лицея, приняли настоящую Конституцию.</p>

        <hr style="border:0; border-top:1.5px solid var(--border-color); margin:24px 0;">

        <!-- ЗАКОНЫ ПАРЛАМЕНТА -->
        <?php if (!empty($customLaws)): ?>
            <h3 style="font-size:15px; text-transform:uppercase; color:var(--primary); margin-bottom:14px;">Законы и акты Парламента РЛИ</h3>
            <?php foreach ($customLaws as $law): 
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
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 1.</strong> Республиканский лицей-интернат (далее — РЛИ) есть демократическое правовое сообщество с республиканской формой правления. Источником власти является лицейский народ — учащиеся и педагоги РЛИ.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 2.</strong> Ученик, его права и свободы являются высшей ценностью РЛИ. Признание, соблюдение и защита прав лицеиста — обязанность всех органов лицейского самоуправления.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 3.</strong> Вся власть в РЛИ принадлежит лицейскому народу. Лицейский народ осуществляет власть непосредственно через общие собрания, референдумы и органы самоуправления.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 4.</strong> Никто не может присваивать власть в РЛИ. Захват власти вопреки воле лицейского народа незаконен и влечёт ответственность по законам РЛИ.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 5.</strong> РЛИ признаёт идею разделения властей как основу устройства лицейского сообщества.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 6.</strong> В РЛИ признаются равные права лицеистов независимо от профиля обучения, параллели, типа проживания, происхождения и убеждений.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 7.</strong> На территории РЛИ действует единая система внутренней валюты — лира. Никакая иная валюта не может быть принудительной к приёму.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статья 8.</strong> Высшим непосредственным выражением власти лицейского народа является Лицейское собрание (парламентская сессия).</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 2. Права и свободы лицеиста</h4>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 10.</strong> Каждый лицеист имеет право на свободное выражение своего мнения, участие в лицейских мероприятиях и защиту своих интересов.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 11.</strong> Каждый лицеист имеет право на получение трудовых часов и лир за общественно полезный труд по установленным ставкам.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 12.</strong> Каждый лицеист имеет право на участие в голосованиях по вопросам питания в столовой в соответствии со своим типом проживания.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 13.</strong> Каждый лицеист имеет право посещать осенний и весенний балы при наличии билета, приобретённого за лиры.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 14.</strong> Городские лицеисты участвуют в выборах блюд столовой за обед; интернатские лицеисты — за завтрак, обед и ужин.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 15.</strong> Каждый лицеист обязан уважать Конституцию и законы РЛИ, решения органов самоуправления, принятые в установленном порядке.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статья 18.</strong> Лицеисты обязаны соблюдать дисциплину, уважать труд преподавателей и товарищей, а также беречь имущество лицея. Нарушение дисциплины влечёт ответственность в виде штрафов, устанавливаемых СБРЛ.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 3. Лицейское устройство и денежная система</h4>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 20.</strong> Должностными лицами РЛИ являются: Директор, Президент, Вице-президент, Завуч, Премьер-министр, Председатель Парламента, Глава СБРЛ, министры, депутаты, учителя.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 21.</strong> Директор является высшим должностным лицом РЛИ и осуществляет общее руководство лицеем совместно с администрацией.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 22.</strong> Назначение на должности органов самоуправления производится Завучом (администрацией) по представлению или по итогам выборов.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 30.</strong> Основной денежной единицей РЛИ является <strong>лира</strong>. Эмиссия осуществляется исключительно Центральным банком и Парламентом РЛИ.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 31.</strong> Основным языком РЛИ является якутский. РЛИ признаёт государственный язык РФ и способствует многоязычию.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статья 32.</strong> Порядок начисления лир, обналичивания и применения штрафов устанавливается Парламентом РЛИ и публикуется на официальном портале.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 4. Законодательная власть (Парламент)</h4>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 34.</strong> Законодательная власть РЛИ осуществляется Парламентом во главе с Председателем Парламента.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 35.</strong> Парламент принимает законы РЛИ, изменяющие и дополняющие Конституцию в части, не противоречащей ей.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 40.</strong> Парламент устанавливает ставки зарплат (лир) за труд, регулирует финансовую систему лицея и контролирует обналичивание лир.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статьи 34–45.</strong> Законы Парламента публикуются в разделе «Конституция» портала. Новые законы выделяются специальной пометкой «(новый)» в течение трёх дней.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 5. Исполнительная власть (Правительство)</h4>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 46.</strong> Исполнительная власть РЛИ осуществляется Правительством во главе с Президентом и Премьер-министром.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 50.</strong> Президент издаёт специальные объявления, определяет дату балов и объявляет лицейские праздники.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статьи 46–55.</strong> Правительство организует работу лицейских служб, ведёт новостную ленту и обеспечивает исполнение законов Парламента.</p>

        <h4 style="font-size:15px; margin-bottom:8px;">Глава 6. Судебная власть и СБРЛ</h4>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 56.</strong> Поддержание порядка и дисциплины осуществляет Служба безопасности РЛИ (СБРЛ) во главе с её Главой.</p>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 60.</strong> СБРЛ выписывает штрафы за нарушения дисциплины. Размеры штрафов определяются СБРЛ и доводятся до лицеистов.</p>
        <p style="font-size:13px; margin-bottom:16px;"><strong>Статьи 56–70.</strong> Судебные органы и СБРЛ разрешают споры между лицеистами и осуществляют учёт дисциплинарных взысканий.</p>

        <hr style="border:0; border-top:1.5px solid var(--border-color); margin:24px 0;">
        <h3 style="font-size:14px; text-transform:uppercase; color:var(--text-muted); margin-bottom:14px;">Раздел второй. Заключительные положения</h3>
        <p style="font-size:13px; margin-bottom:8px;"><strong>Статья 71.</strong> Конституция имеет высшую юридическую силу на территории РЛИ. Законы и иные акты, противоречащие Конституции, не подлежат применению.</p>
        <p style="font-size:13px;">Конституция вступает в силу в день подписания её директором РЛИ — И. И. Шамаевым.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
