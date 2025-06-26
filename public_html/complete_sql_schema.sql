-- ================================================================= --
-- == Full Database Reset and Rebuild for Unified Login System    == --
-- == CORRECTED: Primary Keys and AUTO_INCREMENT defined on CREATE == --
-- ================================================================= --

-- Temporarily disable foreign key checks for clean dropping of tables
SET FOREIGN_KEY_CHECKS=0;

-- Drop all tables if they exist to ensure a clean slate
DROP TABLE IF EXISTS `login_tokens`;
DROP TABLE IF EXISTS `player_progress`;
DROP TABLE IF EXISTS `puzzle_prerequisites`;
DROP TABLE IF EXISTS `hints`;
DROP TABLE IF EXISTS `puzzles`;
DROP TABLE IF EXISTS `themes`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `media_library`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `site_text`;

-- ================================================================= --
-- CREATE TABLES
-- ================================================================= --

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'player',
  `score` int(11) NOT NULL DEFAULT 0,
  `password_reset_token` varchar(64) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `password_reset_token` (`password_reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- `puzzles` table
CREATE TABLE `puzzles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `story_text` text DEFAULT NULL,
  `puzzle_type` enum('text','multiple_choice','cipher','location_gps','location_qr') NOT NULL,
  `puzzle_data` text DEFAULT NULL,
  `solution` text NOT NULL,
  `solution_hint` text DEFAULT NULL,
  `success_media_url` varchar(255) DEFAULT NULL,
  `failure_media_url` varchar(255) DEFAULT NULL,
  `prerequisites_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `media_url` varchar(255) DEFAULT NULL,
  `media_type` enum('video','audio','image') DEFAULT NULL,
  `media_pos` enum('above','below') NOT NULL DEFAULT 'above',
  `link_url` varchar(255) DEFAULT NULL,
  `release_time` timestamp NULL DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `player_progress` table
CREATE TABLE `player_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `puzzle_id` int(11) NOT NULL,
  `status` enum('locked','unlocked','solved') NOT NULL DEFAULT 'locked',
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `solved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_puzzle` (`player_id`,`puzzle_id`),
  KEY `puzzle_id` (`puzzle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `hints` table
CREATE TABLE `hints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puzzle_id` int(11) NOT NULL,
  `hint_text` text NOT NULL,
  `display_order` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `puzzle_id` (`puzzle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `puzzle_prerequisites` table
CREATE TABLE `puzzle_prerequisites` (
  `puzzle_id` int(11) NOT NULL,
  `prerequisite_puzzle_id` int(11) NOT NULL,
  PRIMARY KEY (`puzzle_id`,`prerequisite_puzzle_id`),
  KEY `prerequisite_puzzle_id` (`prerequisite_puzzle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `password_resets` table
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`(191)),
  KEY `email` (`email`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `login_tokens` table for secure "View as Player" feature
CREATE TABLE `login_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`(191)),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `settings` table
CREATE TABLE `settings` (
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `themes` table
CREATE TABLE `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `settings_json` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_admin_theme` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `media_library` table
CREATE TABLE `media_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `site_text` table
CREATE TABLE `site_text` (
  `text_key` varchar(100) NOT NULL,
  `text_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`text_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `solved_puzzles`
--

CREATE TABLE `solved_puzzles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `puzzle_id` int(11) NOT NULL,
  `solved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_puzzle_unique` (`user_id`,`puzzle_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `solved_puzzles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ================================================================= --
-- INSERT DEFAULT DATA
-- ================================================================= --

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('ADMIN_DASHBOARD_NAME', 'ARG Control Panel'),
('CONGRATS_DOWNLOAD_URL', ''),
('CONGRATS_TEXT', 'You have successfully completed all the challenges. Well done!'),
('CONGRATS_TITLE', 'Congratulations!'),
('CONGRATS_VIDEO_URL', ''),
('EMAIL_FROM', 'noreply@example.com'),
('EMAIL_FROM_NAME', 'ARG Master'),
('GAME_LIVE_DATETIME', '2025-01-01 00:00:00'),
('GAME_LOCKED_IMAGE_POS', 'above'),
('GAME_LOCKED_IMAGE_URL', ''),
('GAME_LOCKED_MESSAGE', 'The game will begin soon. Prepare yourself.'),
('GAME_LOCKED_TITLE', 'The Adventure Awaits.'),
('INTRO_ENABLED', '1'),
('INTRO_TEXT', 'Welcome, agent. Your mission, should you choose to accept it, begins now. Watch the briefing video and proceed to your first challenge. UNMUTE (below) to hear.'),
('INTRO_VIDEO_URL', ''),
('SITE_DESCRIPTION', 'An interactive alternate reality game.'),
('SITE_NAME', 'ARG Framework'),
('SMTP_HOST', 'smtp.example.com'),
('SMTP_PASSWORD', 'your-password'),
('SMTP_PORT', '587'),
('SMTP_SECURE', 'tls'),
('SMTP_USERNAME', 'your-email@example.com');

INSERT INTO `site_text` (`text_key`, `text_value`, `description`) VALUES
('ajax_answer_correct', 'Correct! You have unlocked the next path(s).', 'AJAX response for a correct puzzle answer.'),
('ajax_answer_incorrect', 'That is not the correct answer. Please try again.', 'AJAX response for an incorrect puzzle answer.'),
('ajax_error_missing_data', 'Missing puzzle data.', 'AJAX error when puzzle data is not sent.'),
('ajax_error_security_token', 'Invalid security token.', 'AJAX error for an invalid CSRF token.'),
('congrats_back_to_dashboard', 'Back to Dashboard', 'Button text on the congratulations page.'),
('congrats_download_button', 'Download Victory Video', 'Button text for the video download link on the congratulations page.'),
('dashboard_all_puzzles_solved', 'You have solved all puzzles!', 'Dashboard message when all puzzles are solved.'),
('dashboard_available_puzzles', 'Available Puzzles', 'Header for the available puzzles list on the dashboard.'),
('dashboard_mission_briefing_link', 'Revisit Mission Briefing', 'Link text to watch the intro video again.'),
('dashboard_mission_briefing_tagline', 'Click here to watch the introductory video again.', 'Tagline below the mission briefing link.'),
('dashboard_mission_complete_banner_button', 'Revisit Victory Page', 'Button on the dashboard''s ''Mission Complete'' banner.'),
('dashboard_mission_complete_banner_header', 'Mission Complete!', 'Header on the dashboard''s ''Mission Complete'' banner.'),
('dashboard_mission_complete_banner_text', 'You have successfully completed all available puzzles. Congratulations!', 'Text in the dashboard''s ''Mission Complete'' banner.'),
('dashboard_no_available_puzzles', 'No new puzzles are available at this time. Check back later or look for clues!', 'Dashboard message when no puzzles are available.'),
('dashboard_no_solved_puzzles', 'You haven''t solved any puzzles yet. Get started above!', 'Dashboard message when no puzzles have been solved.'),
('dashboard_solved_puzzles', 'Solved Puzzles', 'Header for the solved puzzles list on the dashboard.'),
('dashboard_solved_timestamp_prefix', 'Solved:', 'Prefix for the puzzle solved timestamp on the dashboard.'),
('dashboard_welcome', 'Welcome,', 'Greeting on the player dashboard (username is appended).'),
('forgot_password_button', 'Send Reset Link', 'Button text on the forgot password page.'),
('forgot_password_instructions', 'Enter your email address and we will send you a link to reset your password.', 'Instructions on the Forgot Password page.'),
('forgot_password_success_message', 'If an account with that email exists, a password reset link has been sent.', 'Success message after requesting a password reset.'),
('forgot_password_title', 'Forgot Password', 'Title on the Forgot Password page.'),
('forgot_username_button', 'Send Username', 'Button text on the forgot username page.'),
('forgot_username_instructions', 'Enter your email address, and we will send you your username.', 'Instructions on the Forgot Username page.'),
('forgot_username_success_message', 'If an account with that email exists, a username reminder has been sent.', 'Success message after requesting a username reminder.'),
('forgot_username_title', 'Forgot Username', 'Title on the Forgot Username page.'),
('login_button', 'Login', 'Text for the login button.'),
('login_error_credentials', 'Invalid email or password.', 'Error message for incorrect login credentials.'),
('login_error_expired_token', 'The login link was invalid or has expired.', 'Error message for an invalid one-time login token.'),
('login_error_missing_fields', 'Please enter both email and password.', 'Error message when login form fields are empty.'),
('login_title', 'Login', 'Title on the Login page.'),
('profile_button_update', 'Update Profile', 'Button text to update a user''s profile.'),
('profile_danger_zone_button', 'Reset My Game Progress', 'Button text for the game progress reset button.'),
('profile_danger_zone_header', 'Danger Zone', 'Header for the profile''s danger zone section.'),
('profile_danger_zone_warning', 'Resetting your progress will delete all of your puzzle history and restart the game from the beginning. <strong>This action cannot be undone.</strong>', 'Warning message for resetting game progress.'),
('profile_error_reset', 'Could not reset your game progress due to a server error.', 'Error message if progress reset fails.'),
('profile_error_update', 'Error updating profile. Username may already be in use.', 'Error message if profile update fails.'),
('profile_header_edit_details', 'Edit Details', 'Header for the profile details section.'),
('profile_label_email', 'Email', 'Label for the email field in the profile.'),
('profile_label_new_password', 'New Password (leave blank to keep current password)', 'Label for the new password field in the profile.'),
('profile_label_username', 'Username', 'Label for the username field in the profile.'),
('profile_success_reset', 'Your game progress has been successfully reset!', 'Success message after resetting game progress.'),
('profile_success_update', 'Profile updated successfully!', 'Success message after updating a profile.'),
('profile_title', 'My Profile', 'Title on the user profile page.'),
('puzzle_back_to_list', '&laquo; Back to Puzzle List', 'Link text to go back to the main puzzle list.'),
('puzzle_error_loading', 'Error: Puzzle interface could not be loaded.', 'Error message if a puzzle template fails to load.'),
('puzzle_hint_button_all_revealed', 'All Hints Revealed', 'Hint button text when all hints have been revealed.'),
('puzzle_hint_button_initial', 'Request Hint', 'Initial text for the hint request button.'),
('puzzle_hint_button_remaining', 'Request Hint ({count} left)', 'Hint button text showing remaining hints. {count} is replaced by the number.'),
('puzzle_hint_button_reveal_solution', 'Reveal Solution Hint', 'Hint button text to reveal the final solution hint.'),
('puzzle_hint_confirm_solution', 'Are you sure? This will reveal the solution hint for the puzzle.', 'Confirmation message before showing the solution hint.'),
('puzzle_hint_container_header', 'Hint System', 'Header for the hint system section on the puzzle page.'),
('puzzle_hint_container_instructions', 'Stuck? You can request hints here. The final hint will reveal the solution.', 'Instructions for the hint system.'),
('puzzle_hint_solution_revealed', 'Solution Revealed', 'Hint button text after the solution hint is shown.'),
('puzzle_status_already_solved', 'You have already solved this puzzle!', 'Message shown if a player revisits a solved puzzle.'),
('register_button', 'Register', 'Text for the register button.'),
('register_error_taken', 'That username or email is already taken.', 'Error message if a username or email is already registered.'),
('register_link_to_login', 'Already have an account? Login here.', 'Text with a link to the login page from the register page.'),
('register_status_closed', 'The game is not yet live. Registration will open at the same time the game begins:', 'Message shown when registration is closed.'),
('register_status_header', 'Registration Is Not Yet Open', 'Header for the message when registration is closed.'),
('register_title', 'Register', 'Title on the Register page.'),
('reset_password_button', 'Reset Password', 'Button text on the reset password page.'),
('reset_password_error_empty', 'Password cannot be empty.', 'Error message if the new password field is empty.'),
('reset_password_error_mismatch', 'Passwords do not match.', 'Error message if the new passwords do not match.'),
('reset_password_error_server', 'An error occurred. Please try again.', 'Generic error message for a failed password reset.'),
('reset_password_error_token', 'This password reset link is invalid or has expired. Please request a new one.', 'Error message for an invalid password reset token.'),
('reset_password_label_confirm', 'Confirm New Password', 'Label for the confirm password field.'),
('reset_password_label_new', 'New Password', 'Label for the new password field.'),
('reset_password_link_new_request', 'Request a new reset link', 'Link text to request a new password reset.'),
('reset_password_success', 'Your password has been reset successfully! You can now <a href=\"login.php\">log in</a>.', 'Success message after a successful password reset.'),
('reset_password_title', 'Reset Your Password', 'Title of the reset password page.'),
('sidebar_nav_admin_panel', 'Admin Panel', 'Navigation link text to the admin panel.'),
('sidebar_nav_login', 'Login', 'Navigation link text to the login page.'),
('sidebar_nav_logout', 'Logout', 'Navigation link text for logging out.'),
('sidebar_nav_my_profile', 'My Profile', 'Navigation link text to the user profile page.'),
('sidebar_nav_puzzle_grid', 'Puzzle Grid', 'Navigation link text to the main puzzle dashboard.');

-- ================================================================= --
-- INSERT EXAMPLE PUZZLES
-- ================================================================= --

INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `solution`, `is_visible`, `display_order`)
VALUES
(101, 'The Sphinx''s Question', 'I have cities, but no houses. I have mountains, but no trees. I have water, but no fish. What am I? (Answer: A Map)', 'text', 'A Map', 1, 10);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(101, 'My form is often folded.', 0),
(101, 'I am a representation, not the real thing.', 1);

INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `puzzle_data`, `solution`, `is_visible`, `display_order`)
VALUES
(102, 'A Matter of Choice', 'Which of these is not a primary color in the additive (light-based) color model used for screens? (Answer: Yellow)', 'multiple_choice', '["Red","Yellow","Blue","Green"]', 'Yellow', 1, 20);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(102, 'Think about the colors that make up a pixel on your computer monitor.', 0),
(102, 'The acronym for this color model is RGB.', 1);

INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `solution`, `media_url`, `media_type`, `media_pos`, `is_visible`, `display_order`)
VALUES
(103, 'The Hidden Message', 'The image contains a secret message, but it seems to be encoded. The key is written in the stars. (Answer: Orion)', 'cipher', 'Orion', 'https://upload.wikimedia.org/wikipedia/commons/4/4d/Orion_constellation_map.png', 'image', 'above', 1, 30);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(103, 'The answer is a constellation.', 0),
(103, 'The three bright stars in a row form this constellation''s famous "belt".', 1);

INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `solution`, `is_visible`, `display_order`)
VALUES
(104, 'The Digital Ghost', 'Find the event poster hidden in the library. A QR code is in the corner. Scan it to get the password. (Answer: Gigabyte)', 'location_qr', 'Gigabyte', 1, 40);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(104, 'The QR code contains the exact solution text.', 0),
(104, 'The solution is a common unit of digital information.', 1);

INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `puzzle_data`, `solution`, `is_visible`, `display_order`)
VALUES
(105, 'The Lady of the Harbor', 'Travel to the location of the great colossus, a gift from France. On a plaque near the entrance, you will find a year. That is the answer. (Answer: 1886)', 'location_gps', '{"latitude": 40.6892, "longitude": -74.0445, "radius": 50}', '1886', 1, 50);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(105, 'This landmark is located in New York Harbor.', 0),
(105, 'The year is related to the dedication of the monument.', 1);

-- ================================================================= --
-- SET UP PREREQUISITES AND FOREIGN KEYS
-- ================================================================= --

INSERT INTO `puzzle_prerequisites` (`puzzle_id`, `prerequisite_puzzle_id`) VALUES
(102, 101),
(103, 102),
(104, 103),
(105, 104);

ALTER TABLE `player_progress`
  ADD CONSTRAINT `fk_progress_player` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progress_puzzle` FOREIGN KEY (`puzzle_id`) REFERENCES `puzzles` (`id`) ON DELETE CASCADE;
ALTER TABLE `hints`
  ADD CONSTRAINT `fk_hints_puzzle` FOREIGN KEY (`puzzle_id`) REFERENCES `puzzles` (`id`) ON DELETE CASCADE;
ALTER TABLE `puzzle_prerequisites`
  ADD CONSTRAINT `fk_prereq_puzzle` FOREIGN KEY (`puzzle_id`) REFERENCES `puzzles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prereq_required` FOREIGN KEY (`prerequisite_puzzle_id`) REFERENCES `puzzles` (`id`) ON DELETE CASCADE;
ALTER TABLE `login_tokens`
  ADD CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;
COMMIT;