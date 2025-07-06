<?php
// File: admin/login.php (to be deleted or replaced)

// This file is obsolete. All logins are handled by the public login page.
// This redirect ensures any old bookmarks still work correctly.
require_once __DIR__ . '/../config.php';
header("Location: " . SITE_URL . "/public/login.php");
exit;