<form id="puzzle-form">
    <input type="hidden" name="id" value="<?php echo $puzzle['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <div class="form-group">
        <label for="answer">Your Answer</label>
        <input type="text" name="answer" id="answer" class="form-control" autocomplete="off" required>
    </div>
    <button type="submit" class="btn">Submit Answer</button>
</form>