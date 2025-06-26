<form id="puzzle-form">
    <input type="hidden" name="id" value="<?php echo $puzzle['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <div class="form-group">
        <label for="answer">Your Decoded Answer</label>
        <textarea name="answer" id="answer" rows="3" required></textarea>
    </div>
    <button type="submit" class="btn">Submit Answer</button>
</form>