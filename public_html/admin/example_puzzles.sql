-- ================================================================= --
-- == Example Puzzle Data for ARG Framework == --
-- ================================================================= --
-- This script will insert 5 example puzzles and their associated hints.

-- Start a transaction to ensure all or no data is inserted.
START TRANSACTION;

-- Empty the tables first to prevent duplicate content issues on re-run
DELETE FROM `hints`;
DELETE FROM `puzzle_prerequisites`;
DELETE FROM `puzzles`;

-- Puzzle 1: A simple starting text puzzle
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `puzzle_data`, `solution`, `solution_hint`, `media_url`, `media_type`, `media_pos`, `is_visible`, `display_order`)
VALUES
(1, 'The First Step', 'The journey of a thousand miles begins with a single word. What is the traditional first program a coder writes?', 'With the first password accepted, a new screen flickers to life, displaying a fragmented map and a single cryptic message: "They don''t know we''re watching. Find the dead drop. The key is what all programmers learn first."', 'text', NULL, 'hello world', 'Think about the most basic output in programming tutorials.', NULL, NULL, 'above', 1, 0);

-- Puzzle 2: A multiple-choice puzzle that depends on the first one
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `puzzle_data`, `solution`, `solution_hint`, `media_url`, `media_type`, `media_pos`, `is_visible`, `display_order`)
VALUES
(2, 'A Matter of Choice', 'Which of these is not a primary color?', 'The decoded message from the previous puzzle leads you to an online forum for artists. A private post, accessible only with your new credentials, contains a simple poll. It seems to be a loyalty test.', 'multiple_choice', '["Red","Blue","Green","Yellow"]', 'Green', 'Primary colors are the foundational colors from which all other colors are derived by mixing.', NULL, NULL, 'above', 1, 1);

-- Puzzle 3: An image-based puzzle
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `puzzle_data`, `solution`, `solution_hint`, `media_url`, `media_type`, `media_pos`, `is_visible`, `display_order`)
VALUES
(3, 'Look and See', 'The image contains a hidden message. What is it?', 'Your correct answer on the art forum grants you access to a hidden image file. It looks like a modern art piece, but there must be something more to it. The filename is `message.jpg`.', 'text', NULL, 'VANGUARD', 'Sometimes, the most obvious things are the hardest to see. Look at the negative space.', 'https://i.imgur.com/8aVpA8A.png', 'image', 'below', 1, 2);

-- Puzzle 4: A QR code based puzzle
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `puzzle_data`, `solution`, `solution_hint`, `media_url`, `media_type`, `media_pos`, `is_visible`, `display_order`)
VALUES
(4, 'Digital Hunt', 'Scan the QR code to find the next password.', 'The word "VANGUARD" unlocks a new section of the website, showing a grainy video of a public park. The camera zooms in on a QR code pasted to the back of a bench.', 'location_qr', NULL, 'Orion77', 'The QR code itself is the key.', NULL, NULL, 'above', 1, 3);

-- Puzzle 5: A GPS-based puzzle
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `puzzle_data`, `solution`, `solution_hint`, `media_url`, `media_type`, `media_pos`, `is_visible`, `display_order`)
VALUES
(5, 'Final Destination', 'You must travel to the location to find the final code. Be within the specified radius and enter the code written on the plaque.', 'The final message is a set of coordinates and a warning: "The package is at the base of the monument. You must be on-site to retrieve it. The final access code is written on the plaque. Be quick." The mission is almost complete.', 'location_gps', '{"latitude": 40.748817, "longitude": -73.985428, "radius": 50}', 'LIBERTY24', 'The target is a very famous landmark in New York City.', NULL, NULL, 'above', 1, 4);

-- Empty the tables first to prevent duplicate puzzles on re-run
DELETE FROM `hints` WHERE `puzzle_id` IN (10, 20, 30, 40, 50);
DELETE FROM `puzzle_prerequisites` WHERE `puzzle_id` IN (10, 20, 30, 40, 50);
DELETE FROM `puzzles` WHERE `id` IN (10, 20, 30, 40, 50);

-- Puzzle 10: The starting point, unlocks two branches.
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `solution`, `is_visible`, `display_order`)
VALUES
(10, 'The Crossroads', 'To proceed, you must choose a path, but first, you must prove your worth. What is the capital of France?', 'A strange device hums before you. A single question is displayed on its screen. Answering it seems to be the only way forward.', 'text', 'Paris', 1, 10);

-- Puzzle 20: Branch A. Requires puzzle 10.
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `solution`, `is_visible`, `display_order`)
VALUES
(20, 'The Left Path', 'You chose the path of logic. A sequence is presented: 2, 3, 5, 7, 11, ... What is the next number?', 'The left path leads to a cold, metallic room. A sequence of numbers is etched into the wall. It feels like a test of pure logic.', 'text', '13', 1, 20);

-- Puzzle 30: Branch B. Also requires puzzle 10.
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `puzzle_data`, `solution`, `is_visible`, `display_order`)
VALUES
(30, 'The Right Path', 'You chose the path of observation. Of the options below, which is the largest planet in our solar system?', 'The right path opens into a virtual observatory. The planets of the solar system spin before you. The question is one of scale.', 'multiple_choice', '["Mars","Jupiter","Earth","Saturn"]', 'Jupiter', 1, 21);

-- Puzzle 40: The merge point. Requires BOTH puzzle 20 AND 30.
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `solution`, `solution_hint`, `is_visible`, `display_order`)
VALUES
(40, 'The Gatekeeper', 'Both paths have led you here. To pass the Gatekeeper, combine the keys you have found. The number from the logical path, and the name from the path of observation.', 'The two paths converge before a massive gate. A synthesized voice speaks: "Only by combining the knowledge of both logic and observation can you proceed. Present your two keys as one."', 'text', '13Jupiter', 'Combine the numerical answer and the text answer with no space in between.', 1, 30);

-- Puzzle 50: The final puzzle. Requires puzzle 40.
INSERT INTO `puzzles` (`id`, `title`, `description`, `story_text`, `puzzle_type`, `solution`, `is_visible`, `display_order`)
VALUES
(50, 'The End of the Line', 'You have proven your worth. The final password is the name of the project you are infiltrating.', 'The gate swings open, revealing a single, unguarded terminal. It asks for a final password. The answer has been in front of you the whole time.', 'text', 'ARG Framework', 1, 40);

-- Set up the prerequisites (Puzzle 2 requires 1, 3 requires 2, etc.)
INSERT INTO `puzzle_prerequisites` (`puzzle_id`, `prerequisite_puzzle_id`) VALUES
(2, 1),
(3, 2),
(4, 3),
(5, 4);
-- Puzzle 20 (Branch A) requires Puzzle 10
(20, 10),
-- Puzzle 30 (Branch B) requires Puzzle 10
(30, 10),
-- Puzzle 40 (Merge Point) requires Puzzle 20
(40, 20),
-- Puzzle 40 (Merge Point) ALSO requires Puzzle 30
(40, 30),
-- Puzzle 50 (Final) requires Puzzle 40
(50, 40);

-- Add hints for some of the puzzles
INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(1, 'It is often used to test if a system is operational.', 0),
(1, 'It is a two-word phrase.', 1),
(3, 'The answer is a single word written in white.', 0),
(5, 'The building has 102 stories.', 0);
(20, 'The sequence is composed of prime numbers.', 0),
(30, 'This gas giant is known for its Great Red Spot.', 0);

-- Commit the transaction to save all changes
COMMIT;