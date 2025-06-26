<?php $options = json_decode($puzzle['puzzle_data'], true); ?>
<form id="puzzle-form">
    <input type="hidden" name="id" value="<?php echo $puzzle['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <div class="form-group">
        <label>Select the correct option:</label>
        <?php foreach ($options as $option): ?>
            <div class="form-check">
                <input type="radio" name="answer" value="<?php echo htmlspecialchars($option); ?>" id="option-<?php echo htmlspecialchars($option); ?>" required>
                <label for="option-<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></label>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="btn">Submit Answer</button>
</form>