</main>
<footer class="main-footer">
    <div class="container footer-wrap">
        <div>
            <strong>ГБНОУ РС(Я) «Республиканский лицей-интернат»</strong>
            <p style="margin-top:2px;">г. Якутск, Ойунского, 37</p>
        </div>
        <div>
            <span>Локальный портал • <?= date('Y') ?></span>
        </div>
    </div>
</footer>


<div id="rliModalOverlay" class="rli-modal-overlay" style="display:none;" onclick="if(event.target===this) closeRliModal();">
    <div class="rli-modal-box">
        <div class="rli-modal-head">
            <h3 id="rliModalTitle">Подтверждение</h3>
            <button type="button" class="rli-modal-close" onclick="closeRliModal()" aria-label="Закрыть">✕</button>
        </div>
        <div class="rli-modal-body" id="rliModalText"></div>
        <div class="rli-modal-actions" id="rliModalActions"></div>
    </div>
</div>

<script>
function openRliModal(title, text, buttons) {
    document.getElementById('rliModalTitle').innerText = title;
    document.getElementById('rliModalText').innerText = text;
    const actions = document.getElementById('rliModalActions');
    actions.innerHTML = '';
    buttons.forEach(function(btn) {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = btn.primary ? 'btn-primary' : 'btn-hero';
        if (btn.danger) {
            el.style.background = '#a61b1b';
            el.style.borderColor = '#a61b1b';
            el.style.color = '#ffffff';
        }
        el.innerText = btn.label;
        el.onclick = function() {
            closeRliModal();
            if (btn.onClick) btn.onClick();
        };
        actions.appendChild(el);
    });
    document.getElementById('rliModalOverlay').style.display = 'flex';
}

function closeRliModal() {
    document.getElementById('rliModalOverlay').style.display = 'none';
}

function rliConfirmLink(title, text, url, confirmLabel) {
    openRliModal(title, text, [
        { label: 'Отмена', primary: false },
        { label: confirmLabel || 'Подтвердить', primary: true, danger: true, onClick: function() { window.location.href = url; } }
    ]);
    return false;
}

function rliConfirmForm(formEl, title, text, confirmLabel, isDanger) {
    if (formEl.dataset.confirmed === '1') {
        formEl.dataset.confirmed = '0';
        return true;
    }
    openRliModal(title, text, [
        { label: 'Отмена', primary: false },
        {
            label: confirmLabel || 'Подтвердить',
            primary: true,
            danger: !!isDanger,
            onClick: function() {
                formEl.dataset.confirmed = '1';
                formEl.submit();
            }
        }
    ]);
    return false;
}
</script>
</body>
</html>