-- ================================================================= --
-- == Example Puzzle Data for ARG Framework == --
-- ================================================================= --
-- This script will insert 5 example puzzles of all available types,
-- including their hints, media, and prerequisite links.

-- Start a transaction to ensure all or no data is inserted.
START TRANSACTION;

-- Define puzzle IDs to be used, to make deleting and re-inserting easier.
SET @p1_id = 101, @p2_id = 102, @p3_id = 103, @p4_id = 104, @p5_id = 105;

-- Empty the tables first to prevent duplicate content issues on re-run.
DELETE FROM `hints` WHERE `puzzle_id` IN (@p1_id, @p2_id, @p3_id, @p4_id, @p5_id);
DELETE FROM `puzzle_prerequisites` WHERE `puzzle_id` IN (@p1_id, @p2_id, @p3_id, @p4_id, @p5_id);
DELETE FROM `puzzles` WHERE `id` IN (@p1_id, @p2_id, @p3_id, @p4_id, @p5_id);

-- ================================================================= --
-- 1. Simple Text Puzzle
-- ================================================================= --
INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `solution`, `is_visible`, `display_order`)
VALUES
(@p1_id, 'The Sphinx''s Question', 'I have cities, but no houses. I have mountains, but no trees. I have water, but no fish. What am I? (Answer: A Map)', 'text', 'A Map', 1, 10);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(@p1_id, 'My form is often folded.', 0),
(@p1_id, 'I am a representation, not the real thing.', 1);

-- ================================================================= --
-- 2. Multiple Choice Puzzle
-- ================================================================= --
INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `puzzle_data`, `solution`, `is_visible`, `display_order`)
VALUES
(@p2_id, 'A Matter of Choice', 'Which of these is not a primary color in the additive (light-based) color model used for screens? (Answer: Yellow)', 'multiple_choice', '["Red","Yellow","Blue","Green"]', 'Yellow', 1, 20);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(@p2_id, 'Think about the colors that make up a pixel on your computer monitor.', 0),
(@p2_id, 'The acronym for this color model is RGB.', 1);

-- ================================================================= --
-- 3. Cipher Puzzle with Media
-- ================================================================= --
INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `solution`, `media_url`, `media_type`, `media_pos`, `is_visible`, `display_order`)
VALUES
(@p3_id, 'The Hidden Message', 'The image contains a secret message, but it seems to be encoded. The key is written in the stars. (Answer: Orion)', 'cipher', 'Orion', 'https://upload.wikimedia.org/wikipedia/commons/4/4d/Orion_constellation_map.png', 'image', 'above', 1, 30);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(@p3_id, 'The answer is a constellation.', 0),
(@p3_id, 'The three bright stars in a row form this constellation''s famous "belt".', 1);

-- ================================================================= --
-- 4. Location (QR Code) Puzzle
-- ================================================================= --
INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `solution`, `is_visible`, `display_order`)
VALUES
(@p4_id, 'The Digital Ghost', 'Find the event poster hidden in the library. A QR code is in the corner. Scan it to get the password. (Answer: Gigabyte)', 'location_qr', 'Gigabyte', 1, 40);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(@p4_id, 'The QR code contains the exact solution text.', 0),
(@p4_id, 'The solution is a common unit of digital information.', 1);

-- ================================================================= --
-- 5. Location (GPS) Puzzle
-- ================================================================= --
INSERT INTO `puzzles` 
(`id`, `title`, `description`, `puzzle_type`, `puzzle_data`, `solution`, `is_visible`, `display_order`)
VALUES
(@p5_id, 'The Lady of the Harbor', 'Travel to the location of the great colossus, a gift from France. On a plaque near the entrance, you will find a year. That is the answer. (Answer: 1886)', 'location_gps', '{"latitude": 40.6892, "longitude": -74.0445, "radius": 50}', '1886', 1, 50);

INSERT INTO `hints` (`puzzle_id`, `hint_text`, `display_order`) VALUES
(@p5_id, 'This landmark is located in New York Harbor.', 0),
(@p5_id, 'The year is related to the dedication of the monument.', 1);

-- ================================================================= --
-- Set up the prerequisites for a linear game flow
-- ================================================================= --
INSERT INTO `puzzle_prerequisites` (`puzzle_id`, `prerequisite_puzzle_id`) VALUES
(@p2_id, @p1_id),
(@p3_id, @p2_id),
(@p4_id, @p3_id),
(@p5_id, @p4_id);

-- Commit the transaction to save all changes
COMMIT;