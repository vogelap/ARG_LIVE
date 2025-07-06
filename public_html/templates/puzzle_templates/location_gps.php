<?php $data = json_decode($puzzle['puzzle_data'], true); ?>
<p>You must be within <?php echo htmlspecialchars($data['radius']); ?> meters of the target location to solve this.</p>
<div id="gps-status" style="margin: 1rem 0; font-weight: bold;"></div>
<button id="gps-check-btn" class="btn" onclick="checkGpsLocation(<?php echo $data['latitude']; ?>, <?php echo $data['longitude']; ?>, <?php echo $data['radius']; ?>)">Check My Location</button>

<form id="puzzle-form" style="margin-top: 1rem;">
    <input type="hidden" name="id" value="<?php echo $puzzle['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <div class="form-group">
        <label for="gps-answer">Secret Code at Location</label>
        <input type="text" name="answer" id="gps-answer" autocomplete="off" required>
        <input type="hidden" id="gps-solution" value="<?php echo htmlspecialchars($puzzle['solution']); ?>">
    </div>
    <button type="submit" class="btn">Submit Code</button>
</form>