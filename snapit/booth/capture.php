<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';
require_login();

$session_id = current_booth_session_id($conn);
if (!$session_id) {
    set_flash('No active session found. Start a new session.', 'warning');
    redirect(site_url('booth/index.php'));
}

$presets = mysqli_query($conn, "SELECT * FROM camera_presets WHERE is_active = 1 ORDER BY preset_id ASC");
$filters = mysqli_query($conn, "SELECT * FROM filters WHERE is_active = 1 ORDER BY filter_id ASC");
$layouts = mysqli_query($conn, "SELECT * FROM layouts WHERE is_active = 1 ORDER BY photo_count ASC");
$frames  = mysqli_query($conn, "SELECT * FROM frame_designs WHERE is_active = 1 ORDER BY design_id ASC");

$first_preset = mysqli_fetch_assoc(mysqli_query($conn, "SELECT preset_id FROM camera_presets WHERE is_active = 1 ORDER BY preset_id ASC LIMIT 1"));
$first_filter = mysqli_fetch_assoc(mysqli_query($conn, "SELECT filter_id FROM filters WHERE is_active = 1 ORDER BY filter_id ASC LIMIT 1"));
$first_layout = mysqli_fetch_assoc(mysqli_query($conn, "SELECT layout_id FROM layouts WHERE is_active = 1 ORDER BY photo_count ASC LIMIT 1"));
$first_frame  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT design_id FROM frame_designs WHERE is_active = 1 ORDER BY design_id ASC LIMIT 1"));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/alert.php';
?>

<div class="container py-4">
    <div class="text-center mb-3">
        <h2 class="fw-bold" style="color:var(--snapit-primary)">
            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Customize Your Booth
        </h2>
        <p class="text-muted mb-0">Choose your preset, filter, layout, and frame design.</p>
    </div>

    <div class="step-indicator no-print">
        <div class="step active" data-step="1">1</div>
        <div class="step" data-step="2">2</div>
        <div class="step" data-step="3">3</div>
        <div class="step" data-step="4">4</div>
    </div>

    <form method="POST" action="<?= e(site_url('booth/store_selection.php')) ?>" id="selectionForm">
        <div class="row g-4">
            <div class="col-lg-7">

                <div class="card p-3 mb-4" id="step-preset">
                    <div class="card-header bg-transparent border-bottom mb-3">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-sliders me-2"></i>1. Camera Presets</h5>
                    </div>
                    <div class="row g-3">
                        <?php while ($p = mysqli_fetch_assoc($presets)): ?>
                            <label class="col-md-6 col-lg-4">
                                <input type="radio" name="preset_id" value="<?= (int)$p['preset_id'] ?>"
                                       class="d-none preset-radio"
                                       <?= $first_preset && $p['preset_id'] == $first_preset['preset_id'] ? 'checked' : '' ?>>
                                <div class="preset-card">
                                    <div class="fw-bold mb-1"><?= e($p['name']) ?></div>
                                    <div class="small text-muted mb-2"><?= e($p['description'] ?? '') ?></div>
                                    <div class="d-flex justify-content-around small text-muted">
                                        <span>B:<?= (int)$p['brightness'] ?></span>
                                        <span>C:<?= (int)$p['contrast'] ?></span>
                                        <span>S:<?= (int)$p['saturation'] ?></span>
                                        <span>W:<?= (int)$p['warmness'] ?></span>
                                    </div>
                                </div>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="card p-3 mb-4" id="step-filter">
                    <div class="card-header bg-transparent border-bottom mb-3">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-palette me-2"></i>2. Filters</h5>
                    </div>
                    <div class="row g-3">
                        <?php while ($f = mysqli_fetch_assoc($filters)): ?>
                            <label class="col-md-6 col-lg-4">
                                <input type="radio" name="filter_id" value="<?= (int)$f['filter_id'] ?>"
                                       class="d-none filter-radio"
                                       data-css="<?= e($f['css_filter']) ?>"
                                       <?= $first_filter && $f['filter_id'] == $first_filter['filter_id'] ? 'checked' : '' ?>>
                                <div class="filter-card">
                                    <div class="filter-preview" style="background:<?= e($f['preview_color'] ?? 'linear-gradient(135deg, #a78bfa, #f472b6)') ?>;filter:<?= e($f['css_filter']) ?>"></div>
                                    <div class="fw-bold"><?= e($f['name']) ?></div>
                                </div>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="card p-3 mb-4" id="step-layout">
                    <div class="card-header bg-transparent border-bottom mb-3">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-table-cells me-2"></i>3. Layouts</h5>
                    </div>
                    <div class="row g-3">
                        <?php while ($L = mysqli_fetch_assoc($layouts)): ?>
                            <label class="col-md-6 col-lg-4">
                                <input type="radio" name="layout_id" value="<?= (int)$L['layout_id'] ?>"
                                       class="d-none layout-radio"
                                       data-count="<?= (int)$L['photo_count'] ?>"
                                       data-cols="<?= (int)$L['grid_cols'] ?>"
                                       data-rows="<?= (int)$L['grid_rows'] ?>"
                                       <?= $first_layout && $L['layout_id'] == $first_layout['layout_id'] ? 'checked' : '' ?>>
                                <div class="layout-card">
                                    <div class="mb-2">
                                        <?php
                                        $gridStyle = "display:grid;grid-template-columns:repeat({$L['grid_cols']},1fr);gap:4px;";
                                        $items = '';
                                        for ($i = 0; $i < $L['photo_count']; $i++) {
                                            $items .= '<div style="background:#6f42c1;border-radius:3px;height:22px;"></div>';
                                        }
                                        echo '<div style="' . $gridStyle . '">' . $items . '</div>';
                                        ?>
                                    </div>
                                    <div class="fw-bold mb-0"><?= e($L['name']) ?></div>
                                    <div class="small text-muted"><?= (int)$L['photo_count'] ?> photos</div>
                                </div>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="card p-3 mb-4" id="step-frame">
                    <div class="card-header bg-transparent border-bottom mb-3">
                        <h5 class="fw-bold mb-0"><i class="fa-regular fa-image me-2"></i>4. Frame Designs</h5>
                    </div>
                    <div class="row g-3">
                        <?php while ($fr = mysqli_fetch_assoc($frames)): ?>
                            <label class="col-md-6 col-lg-4">
                                <input type="radio" name="design_id" value="<?= (int)$fr['design_id'] ?>"
                                       class="d-none frame-radio"
                                       data-border-color="<?= e($fr['border_color']) ?>"
                                       data-border-width="<?= (int)$fr['border_width'] ?>"
                                       <?= $first_frame && $fr['design_id'] == $first_frame['design_id'] ? 'checked' : '' ?>>
                                <div class="frame-card">
                                    <div style="border:<?= (int)$fr['border_width'] ?>px solid <?= e($fr['border_color']) ?>;border-radius:10px;height:90px;background:<?= e($fr['border_color']) ?>22;display:flex;align-items:center;justify-content:center;margin-bottom:6px;">
                                        <i class="fa-regular fa-image text-muted"></i>
                                    </div>
                                    <div class="fw-bold"><?= e($fr['name']) ?></div>
                                    <div class="small text-muted"><?= (int)$fr['border_width'] ?>px border</div>
                                </div>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

            </div>

            <div class="col-lg-5">
                <div class="sticky-top" style="top:1rem;">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3 text-center"><i class="fa-regular fa-eye me-2"></i>Live Preview</h5>
                        <div id="previewFrame" class="booth-frame">
                            <div id="previewPhotos"></div>
                        </div>
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-snapit btn-lg fw-semibold">
                                <i class="fa-solid fa-camera me-2"></i>Start Capturing
                            </button>
                            <a href="<?= e(site_url('booth/index.php')) ?>" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-left me-1"></i>Back to bookings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="preset_id_val" id="preset_id_val" value="<?= (int)($first_preset['preset_id'] ?? 0) ?>">
        <input type="hidden" name="filter_id_val" id="filter_id_val" value="<?= (int)($first_filter['filter_id'] ?? 0) ?>">
        <input type="hidden" name="layout_id_val" id="layout_id_val" value="<?= (int)($first_layout['layout_id'] ?? 0) ?>">
        <input type="hidden" name="design_id_val" id="design_id_val" value="<?= (int)($first_frame['design_id'] ?? 0) ?>">
    </form>
</div>

<script>
(function() {
    function updateSelectionCards(radioClass, cardClass) {
        document.querySelectorAll('.' + radioClass).forEach(r => {
            const card = r.closest('label').querySelector('.' + cardClass);
            if (r.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    }

    function syncHidden() {
        const p = document.querySelector('input[name="preset_id"]:checked');
        const f = document.querySelector('input[name="filter_id"]:checked');
        const L = document.querySelector('input[name="layout_id"]:checked');
        const fr = document.querySelector('input[name="design_id"]:checked');
        if (p) document.getElementById('preset_id_val').value = p.value;
        if (f) document.getElementById('filter_id_val').value = f.value;
        if (L) document.getElementById('layout_id_val').value = L.value;
        if (fr) document.getElementById('design_id_val').value = fr.value;
    }

    function renderPreview() {
        const layoutEl = document.querySelector('input[name="layout_id"]:checked');
        const filterEl = document.querySelector('input[name="filter_id"]:checked');
        const frameEl  = document.querySelector('input[name="design_id"]:checked');
        const photoCount = layoutEl ? parseInt(layoutEl.dataset.count) : 1;
        const cols = layoutEl ? parseInt(layoutEl.dataset.cols) : 1;
        const rows = layoutEl ? parseInt(layoutEl.dataset.rows) : 1;
        const cssFilter = filterEl ? filterEl.dataset.css : 'none';
        const borderColor = frameEl ? frameEl.dataset.borderColor : '#6f42c1';
        const borderWidth = frameEl ? parseInt(frameEl.dataset.borderWidth) : 6;

        const preview = document.getElementById('previewFrame');
        preview.style.borderColor = borderColor;
        preview.style.borderWidth = borderWidth + 'px';

        const container = document.getElementById('previewPhotos');
        const seed = 'preview-' + Date.now();
        let gridClass = 'photo-grid-solo';
        if (photoCount === 4) gridClass = 'photo-grid-4';
        if (photoCount === 6) gridClass = 'photo-grid-6';

        let html = '<div class="' + gridClass + '" style="grid-template-columns:repeat(' + cols + ',1fr);grid-template-rows:repeat(' + rows + ',1fr);">';
        for (let i = 0; i < photoCount; i++) {
            html += '<img src="https://picsum.photos/seed/' + seed + '-' + i + '/600/400" alt="preview" style="filter:' + cssFilter + '">';
        }
        html += '</div>';
        container.innerHTML = html;
    }

    function markSteps() {
        const steps = [1,2,3,4];
        steps.forEach(s => {
            const el = document.querySelector('.step-indicator .step[data-step="' + s + '"]');
            if (!el) return;
            el.classList.remove('active','done');
        });
        const p = document.querySelector('input[name="preset_id"]:checked');
        const f = document.querySelector('input[name="filter_id"]:checked');
        const L = document.querySelector('input[name="layout_id"]:checked');
        const fr = document.querySelector('input[name="design_id"]:checked');
        if (p) document.querySelector('.step-indicator .step[data-step="1"]').classList.add('done');
        if (p && f) document.querySelector('.step-indicator .step[data-step="2"]').classList.add('done');
        if (p && f && L) document.querySelector('.step-indicator .step[data-step="3"]').classList.add('done');
        if (p && f && L && fr) {
            document.querySelector('.step-indicator .step[data-step="4"]').classList.add('done');
        } else {
            let active = 1;
            if (!p) active = 1;
            else if (!f) active = 2;
            else if (!L) active = 3;
            else active = 4;
            document.querySelector('.step-indicator .step[data-step="' + active + '"]').classList.add('active');
        }
    }

    function refreshAll() {
        updateSelectionCards('preset-radio', 'preset-card');
        updateSelectionCards('filter-radio', 'filter-card');
        updateSelectionCards('layout-radio', 'layout-card');
        updateSelectionCards('frame-radio', 'frame-card');
        syncHidden();
        renderPreview();
        markSteps();
    }

    document.addEventListener('change', function(e) {
        if (e.target.matches('.preset-radio,.filter-radio,.layout-radio,.frame-radio')) {
            refreshAll();
        }
    });

    document.getElementById('selectionForm').addEventListener('submit', function(e) {
        syncHidden();
        const p = document.getElementById('preset_id_val').value;
        const f = document.getElementById('filter_id_val').value;
        const L = document.getElementById('layout_id_val').value;
        const fr = document.getElementById('design_id_val').value;
        if (!p || !f || !L || !fr) {
            e.preventDefault();
            alert('Please select one option in each category.');
        }
    });

    refreshAll();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
