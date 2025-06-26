// File: public/assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    const puzzleForm = document.getElementById('puzzle-form');

    if (puzzleForm) {
        // Get localized text from data attributes on the container
        const container = document.querySelector('.container');
        const textStoryHeader = container.dataset.textStoryHeader || 'Story Update';
        const textAjaxError = container.dataset.textAjaxError || 'An unexpected server error occurred.';
        const textFinalSolve = container.dataset.textFinalSolve || 'Final puzzle solved! Redirecting...';

        const feedbackPlayerContainer = document.getElementById('feedback-media-player');
        const feedbackDiv = document.getElementById('puzzle-feedback');
        const submitButton = puzzleForm.querySelector('button[type="submit"]');

        const playFeedbackMedia = (url) => {
            feedbackPlayerContainer.innerHTML = '';
            if (!url || url.trim() === '') {
                return;
            }

            const ext = url.split('.').pop().toLowerCase();
            let mediaElement;

            if (['mp4', 'webm', 'ogg', 'mov'].includes(ext)) {
                mediaElement = document.createElement('video');
                mediaElement.style.maxWidth = '100%';
                mediaElement.style.maxHeight = '300px';
                mediaElement.style.borderRadius = '8px';
            } else if (['mp3', 'wav', 'm4a'].includes(ext)) {
                mediaElement = document.createElement('audio');
                mediaElement.style.width = '100%';
            }

            if (mediaElement) {
                mediaElement.src = url;
                mediaElement.controls = true;
                mediaElement.autoplay = true;
                mediaElement.addEventListener('error', () => {
                    console.error("Failed to load feedback media:", url);
                    mediaElement.remove();
                });
                feedbackPlayerContainer.appendChild(mediaElement);
            }
        };

        puzzleForm.addEventListener('submit', function(e) {
            e.preventDefault();

            feedbackPlayerContainer.innerHTML = '';
            feedbackDiv.style.display = 'none';
            feedbackDiv.className = 'feedback-area';
            feedbackDiv.textContent = '';
            submitButton.disabled = true;
            submitButton.textContent = 'Checking...';

            const formData = new FormData(puzzleForm);

            fetch('ajax_submit_answer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    playFeedbackMedia(data.success_media_url);

                    if (data.game_complete) {
                        feedbackDiv.textContent = textFinalSolve; // Use localized text
                        feedbackDiv.className = 'feedback-area success';
                        feedbackDiv.style.display = 'block';
                        setTimeout(() => { window.location.href = 'congratulations.php'; }, 1500);
                        return;
                    }

                    feedbackDiv.textContent = data.message;
                    feedbackDiv.className = 'feedback-area success';
                    feedbackDiv.style.display = 'block';

                    let storyHtml = '';
                    if (data.story_text) {
                        storyHtml = `
                            <div class="story-log-update">
                                <h3>${textStoryHeader}</h3>
                                <p>${data.story_text.replace(/\\r\\n|\\n|\\r/g, '<br>')}</p>
                            </div>`;
                    }

                    let linksHtml = '<div class="success-links" style="margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 10px;">';
                    linksHtml += '<a href="index.php" class="btn btn-secondary">View Puzzle List</a>';
                    if (data.next_puzzles && data.next_puzzles.length > 0) {
                        data.next_puzzles.forEach(function(puzzle) {
                            linksHtml += ` <a href="puzzle.php?id=${puzzle.id}" class="btn">Proceed to: ${puzzle.title}</a>`;
                        });
                    }
                    linksHtml += '</div>';

                    puzzleForm.outerHTML = storyHtml + linksHtml;

                } else {
                    playFeedbackMedia(data.failure_media_url);
                    feedbackDiv.textContent = data.message || 'An incorrect answer was submitted.';
                    feedbackDiv.className = 'feedback-area error';
                    feedbackDiv.style.display = 'block';
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit Answer';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackDiv.textContent = textAjaxError; // Use localized text
                feedbackDiv.className = 'feedback-area error';
                feedbackDiv.style.display = 'block';
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Answer';
            });
        });
    }
});