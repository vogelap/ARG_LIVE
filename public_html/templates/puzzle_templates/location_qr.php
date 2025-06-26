<p>Scan the QR code you find at the physical location. It will reveal the secret code needed to solve this puzzle.</p>
<a href="<?php echo SITE_URL; ?>/public/qr_scanner.html" target="_blank" class="btn">Open QR Scanner</a>

<form id="puzzle-form" style="margin-top: 1rem;">
    <input type="hidden" name="id" value="<?php echo $puzzle['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <div class="form-group">
        <label for="answer">Code from QR Scan</label>
        <input type="text" name="answer" id="answer" autocomplete="off" required>
    </div>
    <button type="submit" class="btn">Submit Code</button>
</form>