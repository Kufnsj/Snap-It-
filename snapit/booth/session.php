<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';
require_login();

$session_id = current_booth_session_id($conn);
if (!$session_id) {
    set_flash('No active session found. Start a new session.', 'warning');
    redirect(site_url('booth/index.php'));
}

$stmt = mysqli_prepare($conn, "SELECT gs.*, 
    cp.name AS preset_name, cp.brightness AS p_bri, cp.contrast AS p_con, cp.saturation AS p_sat, cp.warmness AS p_wrm,
    f.name AS filter_name, f.css_filter,
    l.name AS layout_name, l.photo_count, l.grid_cols, l.grid_rows,
    fd.name AS frame_name, fd.border_color, fd.border_width,
    b.event_name
    FROM guest_sessions gs
    LEFT JOIN camera_presets cp ON gs.camera_preset_id = cp.preset_id
    LEFT JOIN filters f ON gs.filter_id = f.filter_id
    LEFT JOIN layouts l ON gs.layout_id = l.layout_id
    LEFT JOIN frame_designs fd ON gs.frame_design_id = fd.design_id
    LEFT JOIN bookings b ON gs.booking_id = b.booking_id
    WHERE gs.session_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $session_id);
mysqli_stmt_execute($stmt);
$session = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$session || !$session['photo_count']) {
    set_flash('Please configure your booth selections first.', 'warning');
    redirect(site_url('booth/capture.php'));
}

$target_n = (int)$session['photo_count'];
$photo_count = 0;
$photos_stmt = mysqli_prepare($conn, "SELECT * FROM session_photos WHERE session_id = ? AND is_kept = 1 ORDER BY order_index ASC");
mysqli_stmt_bind_param($photos_stmt, 'i', $session_id);
mysqli_stmt_execute($photos_stmt);
$photos = mysqli_stmt_get_result($photos_stmt);
$photo_rows = [];
while ($ph = mysqli_fetch_assoc($photos)) {
    $photo_rows[] = $ph;
    $photo_count++;
}

$current_index = isset($_SESSION['capture_index']) ? (int)$_SESSION['capture_index'] : $photo_count;
if ($current_index < 0) $current_index = 0;

$hbStmt = mysqli_prepare($conn, "UPDATE guest_sessions SET idle_timeout_at = NOW() + INTERVAL 3 MINUTE WHERE session_id = ?");
mysqli_stmt_bind_param($hbStmt, 'i', $session_id);
mysqli_stmt_execute($hbStmt);

$p_bri = (int)($session['p_bri'] ?? 0);
$p_con = (int)($session['p_con'] ?? 0);
$p_sat = (int)($session['p_sat'] ?? 0);
$p_wrm = (int)($session['p_wrm'] ?? 0);

$preset_filter_parts = [];
$preset_filter_parts[] = 'brightness(' . (1 + $p_bri / 100) . ')';
$preset_filter_parts[] = 'contrast(' . (1 + $p_con / 100) . ')';
$sat_val = max(0, 1 + $p_sat / 100);
$preset_filter_parts[] = 'saturate(' . $sat_val . ')';
if ($p_wrm > 0) {
    $preset_filter_parts[] = 'sepia(' . min(0.6, $p_wrm / 200) . ')';
} elseif ($p_wrm < 0) {
    $preset_filter_parts[] = 'hue-rotate(' . $p_wrm . 'deg)';
}
$preset_css_filter = implode(' ', $preset_filter_parts);

$filter_css = trim($session['css_filter'] ?? 'none');
if ($filter_css === '' || $filter_css === 'none') {
    $combined_css_filter = $preset_css_filter;
} else {
    $combined_css_filter = $preset_css_filter . ' ' . $filter_css;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/alert.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="card shadow-sm border-0 p-3 mb-3 no-print">
                <div class="row align-items-center g-3">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary">
                                <i class="fa-solid fa-user me-1"></i>
                                <?= e($session['guest_name'] ?: 'Guest #' . $session_id) ?>
                            </span>
                            <span class="badge badge-snapit">
                                <i class="fa-solid fa-sliders me-1"></i><?= e($session['preset_name'] ?? 'Default') ?>
                            </span>
                            <span class="badge" style="background:var(--snapit-secondary);color:#fff;">
                                <i class="fa-solid fa-palette me-1"></i><?= e($session['filter_name'] ?? 'None') ?>
                            </span>
                            <span class="badge bg-info text-dark">
                                <i class="fa-solid fa-table-cells me-1"></i><?= e($session['layout_name'] ?? 'Solo') ?>
                            </span>
                            <span class="badge bg-secondary">
                                <i class="fa-regular fa-image me-1"></i><?= e($session['frame_name'] ?? 'Plain') ?>
                            </span>
                        </div>
                        <div class="small text-muted mt-1">
                            <i class="fa-regular fa-calendar-check me-1"></i><?= e($session['event_name']) ?>
                            &bull; Session #<?= (int)$session_id ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div id="idleWarning" class="text-muted small">
                            <i class="fa-regular fa-clock me-1"></i>Idle timeout:
                            <span id="idleCountdown" class="fw-bold">3:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="progress mb-3 no-print" style="height:28px;border-radius:14px;">
                <?php $pct = ($target_n > 0) ? min(100, (($photo_count) / $target_n) * 100) : 0; ?>
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar" style="width: <?= $pct ?>%;background:linear-gradient(90deg,var(--snapit-primary),var(--snapit-secondary));">
                    <span class="fw-bold px-2">
                        Photo <?php $showIdx = min($photo_count + 1, $target_n); echo (int)$showIdx; ?> of <?= (int)$target_n ?>
                    </span>
                </div>
            </div>

            <div id="boothContainer" class="position-relative mb-4"
                 data-border-color="<?= e($session['border_color']) ?>"
                 data-border-width="<?= (int)$session['border_width'] ?>"
                 data-filter="<?= e($combined_css_filter) ?>">

                <div id="countdownOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                     style="background:rgba(255,255,255,0.9);border-radius:24px;z-index:20;display:none !important;">
                    <div id="countdownText" class="countdown-big">3</div>
                </div>

                <div id="flashOverlay" class="position-absolute top-0 start-0 w-100 h-100"
                     style="background:#fff;border-radius:24px;z-index:15;display:none;opacity:0;pointer-events:none;"></div>

                <div class="booth-frame" id="mainFrame">
                    <video id="cameraVideo" autoplay playsinline muted
                           style="width:100%;max-height:560px;object-fit:contain;border-radius:16px;background:#000;display:none;"></video>
                    <canvas id="captureCanvas" style="display:none;"></canvas>

                    <div id="cameraError" class="text-center text-muted" style="display:none;">
                        <i class="fa-solid fa-video-slash fa-4x mb-3" style="color:#dc3545;opacity:0.6;"></i>
                        <h4 class="fw-bold mb-2 text-dark">Camera Couldn't Start</h4>
                        <p id="cameraErrorText" class="mb-3 text-muted mx-auto" style="max-width:480px;">
                            We couldn't access your camera. Please allow camera access when prompted, or use a device with a working webcam.
                        </p>
                        <button type="button" id="retryCameraBtn" class="btn btn-snapit px-4">
                            <i class="fa-solid fa-rotate me-2"></i>Try Again
                        </button>
                    </div>

                    <div id="cameraLoading" class="text-center text-muted">
                        <i class="fa-solid fa-spinner fa-spin fa-4x mb-3" style="color:var(--snapit-primary);opacity:0.6;"></i>
                        <h4 class="fw-bold mb-2">Starting Camera...</h4>
                        <p class="mb-0 small">Please allow camera access when prompted by your browser.</p>
                    </div>

                    <?php if ($photo_count > 0): ?>
                        <div id="photoDisplay" class="w-100" style="display:none;">
                            <?php
                            $grid_class = 'photo-grid-solo';
                            if ($target_n === 4) $grid_class = 'photo-grid-4';
                            if ($target_n === 6) $grid_class = 'photo-grid-6';
                            echo '<div class="' . $grid_class . '" style="grid-template-columns:repeat(' . (int)$session['grid_cols'] . ',1fr);grid-template-rows:repeat(' . (int)$session['grid_rows'] . ',1fr);">';
                            for ($i = 0; $i < $target_n; $i++) {
                                if (isset($photo_rows[$i])) {
                                    $src = $photo_rows[$i]['photo_path'];
                                    echo '<img src="' . e($src) . '" alt="Photo ' . ($i+1) . '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">';
                                } else {
                                    echo '<div style="background:#f8f5ff;border-radius:12px;min-height:140px;display:flex;align-items:center;justify-content:center;color:#ccc;">
                                        <i class="fa-regular fa-image fa-2x"></i>
                                    </div>';
                                }
                            }
                            echo '</div>';
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 no-print">
                <div class="col-md-6">
                    <?php if ($photo_count > 0): ?>
                        <a id="backBtn" href="#" class="btn btn-outline-secondary w-100 py-2 fw-semibold">
                            <i class="fa-solid fa-arrow-rotate-left me-1"></i>Back
                        </a>
                    <?php else: ?>
                        <a href="<?= e(site_url('booth/capture.php')) ?>" id="backToSelBtn" class="btn btn-outline-secondary w-100 py-2 fw-semibold">
                            <i class="fa-solid fa-arrow-left me-1"></i>Back to selections
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <form method="POST" action="<?= e(site_url('booth/end_session.php')) ?>" id="endForm" class="d-inline w-100">
                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold" id="doneBtn">
                            <i class="fa-solid fa-check me-1"></i>Done / New Guest
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($photo_count < $target_n): ?>
                <div class="mt-4 text-center no-print">
                    <button id="captureBtn" class="btn btn-snapit btn-lg px-5 py-4 fw-bold shadow-lg" style="font-size:1.25rem;min-width:260px;border-radius:18px;" disabled>
                        <i class="fa-solid fa-camera me-2 fa-xl"></i>
                        <span id="captureBtnText">
                            <?php if ($photo_count === 0): ?>Start Capturing<?php else: ?>Next Photo<?php endif; ?>
                        </span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($photo_count > 0): ?>
            <div id="actionBar" class="card p-3 mt-4 no-print" style="display:none;">
                <div class="text-center mb-2 fw-semibold">
                    <i class="fa-regular fa-circle-question me-1"></i>Keep this photo or retake?
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <button id="retakeBtn" class="btn btn-outline-warning w-100 py-3 fw-semibold">
                            <i class="fa-solid fa-rotate me-1"></i>Retake Photo
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button id="keepBtn" class="btn btn-outline-success w-100 py-3 fw-semibold">
                            <i class="fa-solid fa-check me-1"></i>Keep Photo
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<input type="hidden" id="sessionId" value="<?= (int)$session_id ?>">
<input type="hidden" id="targetN" value="<?= (int)$target_n ?>">
<input type="hidden" id="photoCount" value="<?= (int)$photo_count ?>">
<input type="hidden" id="gridCols" value="<?= (int)$session['grid_cols'] ?>">
<input type="hidden" id="gridRows" value="<?= (int)$session['grid_rows'] ?>">
<input type="hidden" id="frameBorderColor" value="<?= e($session['border_color']) ?>">
<input type="hidden" id="frameBorderWidth" value="<?= (int)$session['border_width'] ?>">

<script>
(function() {
    const sessionId = parseInt(document.getElementById('sessionId').value);
    const targetN = parseInt(document.getElementById('targetN').value);
    let photoCount = parseInt(document.getElementById('photoCount').value);
    const gridCols = parseInt(document.getElementById('gridCols').value);
    const gridRows = parseInt(document.getElementById('gridRows').value);
    const combinedFilter = document.getElementById('boothContainer').dataset.filter || 'none';
    const borderColor = document.getElementById('boothContainer').dataset.borderColor || '#6f42c1';
    const borderWidth = parseInt(document.getElementById('boothContainer').dataset.borderWidth) || 6;
    const frameBorderColor = document.getElementById('frameBorderColor').value || '#6f42c1';
    const frameBorderWidth = parseInt(document.getElementById('frameBorderWidth').value) || 6;

    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('captureCanvas');
    const cameraLoading = document.getElementById('cameraLoading');
    const cameraError = document.getElementById('cameraError');
    const cameraErrorText = document.getElementById('cameraErrorText');
    const retryCameraBtn = document.getElementById('retryCameraBtn');
    const countdownOverlay = document.getElementById('countdownOverlay');
    const countdownText = document.getElementById('countdownText');
    const flashOverlay = document.getElementById('flashOverlay');
    const captureBtn = document.getElementById('captureBtn');
    const captureBtnText = document.getElementById('captureBtnText');
    const actionBar = document.getElementById('actionBar');
    const backBtn = document.getElementById('backBtn');
    const endForm = document.getElementById('endForm');
    const doneBtn = document.getElementById('doneBtn');
    const keepBtn = document.getElementById('keepBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const photoDisplay = document.getElementById('photoDisplay');
    const mainFrame = document.getElementById('mainFrame');

    mainFrame.style.borderColor = borderColor;
    mainFrame.style.borderWidth = borderWidth + 'px';

    let mediaStream = null;
    let cameraReady = false;
    let currentPhotos = [];
    document.querySelectorAll('#photoDisplay img').forEach(img => currentPhotos.push(img.src));

    let lastPhotoId = null;
    let lastPhotoPath = null;
    let isBusy = false;

    function releaseCamera() {
        try {
            if (mediaStream) {
                mediaStream.getTracks().forEach(function(t) {
                    try { t.stop(); } catch(e){}
                });
            }
        } catch(e){}
        mediaStream = null;
        cameraReady = false;
    }

    function showCameraLoading() {
        if (cameraLoading) cameraLoading.style.display = '';
        if (cameraError) cameraError.style.display = 'none';
        if (video) video.style.display = 'none';
    }

    function showCameraError(message, allowRetry) {
        if (cameraLoading) cameraLoading.style.display = 'none';
        if (video) video.style.display = 'none';
        if (cameraError) {
            cameraError.style.display = '';
            if (cameraErrorText && message) cameraErrorText.textContent = message;
            if (retryCameraBtn) retryCameraBtn.style.display = allowRetry ? '' : 'none';
        }
        if (captureBtn) captureBtn.disabled = true;
    }

    function showCameraReady() {
        if (cameraLoading) cameraLoading.style.display = 'none';
        if (cameraError) cameraError.style.display = 'none';
        if (photoDisplay && currentPhotos.filter(function(p){return !!p;}).length === 0) {
            photoDisplay.style.display = 'none';
        }
        if (video) {
            video.style.display = '';
            video.style.filter = combinedFilter;
            video.style.webkitFilter = combinedFilter;
        }
        cameraReady = true;
        if (captureBtn) {
            const done = currentPhotos.filter(function(p){return !!p;}).length;
            if (done < targetN) captureBtn.disabled = false;
        }
    }

    function startCamera() {
        releaseCamera();
        showCameraLoading();

        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            showCameraError('Your browser does not support camera access (navigator.mediaDevices). Please use the latest Chrome, Edge, or Firefox, and open the site over HTTPS or localhost.', false);
            return;
        }

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 800 } },
            audio: false
        }).then(function(stream) {
            mediaStream = stream;
            try {
                video.srcObject = stream;
            } catch (err) {
                video.src = window.URL.createObjectURL(stream);
            }
            video.setAttribute('playsinline', '');
            video.play().catch(function(){});

            setTimeout(function() {
                try {
                    const vw = video.videoWidth || 640;
                    const vh = video.videoHeight || 480;
                    if (vw && vh) {
                        canvas.width = vw;
                        canvas.height = vh;
                    } else {
                        canvas.width = 1280;
                        canvas.height = 800;
                    }
                } catch(e){
                    canvas.width = 1280;
                    canvas.height = 800;
                }
                showCameraReady();
            }, 600);
        }).catch(function(err) {
            let msg = 'We couldn\'t access your camera. ';
            let allowRetry = true;
            if (err && err.name === 'NotAllowedError') {
                msg += 'Camera permission was denied. Please click the camera icon in your browser address bar to allow access, then try again.';
            } else if (err && err.name === 'NotFoundError' || err && err.name === 'DevicesNotFoundError') {
                msg += 'No camera device was found on this machine. Please connect a webcam and refresh.';
                allowRetry = false;
            } else if (err && err.name === 'NotReadableError') {
                msg += 'Your camera is in use by another application. Close other programs using the camera and try again.';
            } else if (err && err.name === 'SecurityError') {
                msg += 'Camera access is blocked for security reasons. The capture booth requires a secure (HTTPS) origin or localhost.';
                allowRetry = false;
            } else {
                msg += (err && err.message) ? err.message : 'An unknown error occurred.';
            }
            showCameraError(msg, allowRetry);
        });
    }

    if (retryCameraBtn) {
        retryCameraBtn.addEventListener('click', function() { startCamera(); });
    }

    function ajax(method, url, data, cb) {
        const x = new XMLHttpRequest();
        x.open(method, url, true);
        x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        x.onload = function() { if (cb) cb(x.responseText, x.status); };
        const qs = Object.keys(data).map(function(k) { return encodeURIComponent(k)+'='+encodeURIComponent(data[k]); }).join('&');
        x.send(qs);
    }

    function updateGrid(currentIdx, newImgSrc) {
        let grid_class = 'photo-grid-solo';
        if (targetN === 4) grid_class = 'photo-grid-4';
        if (targetN === 6) grid_class = 'photo-grid-6';
        if (currentIdx >= 0 && newImgSrc && currentIdx < targetN) {
            if (currentPhotos.length <= currentIdx) currentPhotos.length = currentIdx + 1;
            currentPhotos[currentIdx] = newImgSrc;
        }
        let html = '<div class="' + grid_class + '" style="grid-template-columns:repeat(' + gridCols + ',1fr);grid-template-rows:repeat(' + gridRows + ',1fr);">';
        for (let i = 0; i < targetN; i++) {
            if (currentPhotos[i]) {
                html += '<img src="' + currentPhotos[i] + '" alt="Photo ' + (i+1) + '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">';
            } else {
                html += '<div style="background:#f8f5ff;border-radius:12px;min-height:140px;display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="fa-regular fa-image fa-2x"></i></div>';
            }
        }
        html += '</div>';
        let disp = document.getElementById('photoDisplay');
        if (!disp) {
            disp = document.createElement('div');
            disp.id = 'photoDisplay';
            disp.className = 'w-100';
            mainFrame.appendChild(disp);
        }
        disp.innerHTML = html;
        disp.style.display = '';
        if (video) video.style.display = 'none';
    }

    function switchBackToLivePreview() {
        const done = currentPhotos.filter(function(p){return !!p;}).length;
        if (done < targetN) {
            if (photoDisplay) photoDisplay.style.display = 'none';
            if (video && cameraReady) {
                video.style.display = '';
            } else if (video && !cameraReady) {
                startCamera();
            }
        }
    }

    function updateProgress() {
        const done = currentPhotos.filter(function(p){return !!p;}).length;
        photoCount = done;
        document.getElementById('photoCount').value = done;
        const pct = targetN > 0 ? Math.min(100, (done / targetN) * 100) : 0;
        const pb = document.querySelector('.progress-bar');
        if (pb) {
            pb.style.width = pct + '%';
            const showIdx = Math.min(done + 1, targetN);
            pb.querySelector('span').textContent = 'Photo ' + showIdx + ' of ' + targetN;
        }
    }

    function captureVideoFrameToDataURL() {
        const ctx = canvas.getContext('2d');
        const vw = video.videoWidth || canvas.width || 1280;
        const vh = video.videoHeight || canvas.height || 800;
        canvas.width = vw;
        canvas.height = vh;

        ctx.save();
        try {
            ctx.filter = combinedFilter;
            ctx.webkitFilter = combinedFilter;
        } catch(e) {}

        try {
            ctx.drawImage(video, 0, 0, vw, vh);
        } catch(e) {
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        }
        ctx.restore();

        let dataUrl;
        try {
            dataUrl = canvas.toDataURL('image/png');
        } catch(e) {
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            dataUrl = canvas.toDataURL('image/png');
        }
        return dataUrl;
    }

    function uploadPhotoData(dataUrl, orderIndex, cb) {
        const payload = {
            session_id: sessionId,
            image_data: dataUrl,
            order_index: orderIndex
        };
        ajax('POST', '<?= e(site_url("booth/ajax_insert_photo.php")) ?>', payload, function(resp) {
            let photo_id = null;
            let photo_path = null;
            try {
                const j = JSON.parse(resp);
                photo_id = j.photo_id || null;
                photo_path = j.photo_path || null;
            } catch(e) { photo_id = null; photo_path = null; }
            lastPhotoId = photo_id;
            lastPhotoPath = photo_path;
            if (cb) cb(photo_id, photo_path);
        });
    }

    function updateLastPhotoData(dataUrl, cb) {
        if (!lastPhotoId) { if (cb) cb(null, null); return; }
        const payload = { photo_id: lastPhotoId, image_data: dataUrl };
        ajax('POST', '<?= e(site_url("booth/ajax_update_photo.php")) ?>', payload, function(resp) {
            let photo_path = null;
            try { const j = JSON.parse(resp); photo_path = j.photo_path || null; } catch(e) {}
            lastPhotoPath = photo_path;
            if (cb) cb(photo_path);
        });
    }

    function deleteLastPhoto(cb) {
        if (!lastPhotoId) { if (cb) cb(); return; }
        const payload = { photo_id: lastPhotoId, purge_file: 1 };
        ajax('POST', '<?= e(site_url("booth/ajax_delete_photo.php")) ?>', payload, function() {
            lastPhotoId = null;
            lastPhotoPath = null;
            if (cb) cb();
        });
    }

    function startCountdown(cb) {
        countdownOverlay.style.setProperty('display', 'flex', 'important');
        let n = 3;
        countdownText.textContent = n;
        const iv = setInterval(function() {
            n--;
            if (n > 0) {
                countdownText.textContent = n;
            } else {
                clearInterval(iv);
                countdownOverlay.style.display = 'none';
                flashOverlay.style.display = 'block';
                flashOverlay.style.transition = 'opacity 0.35s ease-out';
                try { requestAnimationFrame(function() { flashOverlay.style.opacity = '0.95'; }); } catch(e){ flashOverlay.style.opacity = '0.95'; }
                setTimeout(function() {
                    flashOverlay.style.opacity = '0';
                    setTimeout(function() {
                        flashOverlay.style.display = 'none';
                        cb();
                    }, 360);
                }, 140);
            }
        }, 800);
    }

    function doCapture(overwriteIndex, cb) {
        if (isBusy) return;
        const done = currentPhotos.filter(function(p){return !!p;}).length;
        if (!overwriteIndex && done >= targetN) return;
        isBusy = true;
        if (captureBtn) captureBtn.disabled = true;

        startCountdown(function() {
            try {
                const idx = overwriteIndex !== null && overwriteIndex !== undefined ? overwriteIndex : done;
                const dataUrl = captureVideoFrameToDataURL();
                const onSaved = function(pid, path) {
                    const displaySrc = path || ('data:image/png;base64,warn');
                    updateGrid(idx, displaySrc);
                    updateProgress();
                    isBusy = false;
                    if (actionBar) actionBar.style.display = 'block';
                    if (captureBtn) {
                        const newDone = currentPhotos.filter(function(p){return !!p;}).length;
                        if (newDone < targetN) {
                            captureBtn.disabled = false;
                            captureBtn.style.display = 'none';
                        } else {
                            captureBtn.disabled = false;
                        }
                    }
                    if (cb) cb();
                };
                if (overwriteIndex !== null && overwriteIndex !== undefined && lastPhotoId) {
                    updateLastPhotoData(dataUrl, function(newPath) {
                        lastPhotoPath = newPath || dataUrl;
                        onSaved(lastPhotoId, newPath || dataUrl);
                    });
                } else {
                    uploadPhotoData(dataUrl, idx, function(pid, path) {
                        onSaved(pid, path || dataUrl);
                    });
                }
            } catch (err) {
                isBusy = false;
                if (captureBtn) captureBtn.disabled = false;
                alert('Could not capture photo: ' + (err && err.message ? err.message : err));
            }
        });
    }

    if (captureBtn) captureBtn.addEventListener('click', function() {
        const done = currentPhotos.filter(function(p){return !!p;}).length;
        if (done >= targetN) return;
        doCapture(null);
    });

    if (keepBtn) keepBtn.addEventListener('click', function() {
        if (actionBar) actionBar.style.display = 'none';
        const done = currentPhotos.filter(function(p){return !!p;}).length;
        if (captureBtn) {
            if (done < targetN) {
                captureBtn.style.display = '';
                captureBtn.innerHTML = '<i class="fa-solid fa-camera me-2 fa-xl"></i><span id="captureBtnText">Next Photo</span>';
                switchBackToLivePreview();
            }
        }
        lastPhotoId = null;
        lastPhotoPath = null;
    });

    if (retakeBtn) retakeBtn.addEventListener('click', function() {
        if (isBusy) return;
        const done = currentPhotos.filter(function(p){return !!p;}).length;
        if (done === 0) return;
        if (actionBar) actionBar.style.display = 'none';
        if (photoDisplay) photoDisplay.style.display = 'none';
        if (video && cameraReady) video.style.display = '';
        const idx = done - 1;
        doCapture(idx);
    });

    if (backBtn) backBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const done = currentPhotos.filter(function(p){return !!p;}).length;
        if (done === 0) {
            releaseCamera();
            window.location.href = '<?= e(site_url("booth/capture.php")) ?>';
            return;
        }
        if (isBusy) return;
        const idx = done - 1;
        if (actionBar) actionBar.style.display = 'none';
        if (captureBtn) {
            captureBtn.style.display = '';
            captureBtn.disabled = false;
        }
        if (lastPhotoId) {
            deleteLastPhoto(function() {
                currentPhotos[idx] = null;
                updateGrid(-1, null);
                updateProgress();
                const newDone = currentPhotos.filter(function(p){return !!p;}).length;
                if (newDone === 0) {
                    releaseCamera();
                    window.location.href = '<?= e(site_url("booth/capture.php")) ?>';
                } else {
                    switchBackToLivePreview();
                }
            });
        } else {
            currentPhotos[idx] = null;
            updateGrid(-1, null);
            updateProgress();
            const newDone = currentPhotos.filter(function(p){return !!p;}).length;
            if (newDone === 0) {
                releaseCamera();
                window.location.href = '<?= e(site_url("booth/capture.php")) ?>';
            } else {
                switchBackToLivePreview();
            }
        }
    });

    if (endForm) {
        endForm.addEventListener('submit', function(e) {
            if (isBusy) { e.preventDefault(); return; }
            releaseCamera();
        });
    }
    if (doneBtn) {
        doneBtn.addEventListener('click', function() { releaseCamera(); });
    }

    let idleSeconds = 180;
    const idleEl = document.getElementById('idleCountdown');
    const idleWarn = document.getElementById('idleWarning');
    function tickIdle() {
        idleSeconds--;
        if (idleSeconds <= 0) {
            releaseCamera();
            window.location.href = '<?= e(site_url("booth/end_session.php?auto=1")) ?>';
            return;
        }
        const m = Math.floor(idleSeconds / 60);
        const s = idleSeconds % 60;
        if (idleEl) idleEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        if (idleWarn) {
            if (idleSeconds <= 30) idleWarn.className = 'text-danger small fw-bold';
            else if (idleSeconds <= 60) idleWarn.className = 'text-warning small fw-semibold';
        }
    }
    const idleTimer = setInterval(tickIdle, 1000);
    function resetIdle() {
        idleSeconds = 180;
        if (idleWarn) idleWarn.className = 'text-muted small';
        try {
            const img = new Image();
            img.src = '<?= e(site_url("booth/ajax_heartbeat.php?sid=")) ?>' + sessionId + '&t=' + Date.now();
        } catch(e) {}
    }
    ['click','keydown','touchstart','mousemove'].forEach(function(ev) {
        document.addEventListener(ev, function() { if (idleSeconds < 170) resetIdle(); });
    });

    window.addEventListener('beforeunload', function() { releaseCamera(); });
    window.addEventListener('pagehide', function() { releaseCamera(); });
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            if (idleSeconds < 170) resetIdle();
        }
    });

    startCamera();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
