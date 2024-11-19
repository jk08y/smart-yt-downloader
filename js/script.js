// js/script.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('downloadForm');
    const urlInput = document.getElementById('videoUrl');
    const formatSelect = document.getElementById('formatSelect');
    const errorMessage = document.getElementById('errorMessage');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressPercentage = document.getElementById('progressPercentage');

    // Add loading animation to progress bar
    function startProgressAnimation() {
        progressBar.classList.add('progress-animated');
    }

    function stopProgressAnimation() {
        progressBar.classList.remove('progress-animated');
    }

    // URL validation
    function isValidYouTubeUrl(url) {
        const pattern = /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/;
        return pattern.test(url);
    }

    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const url = urlInput.value.trim();
        const format = formatSelect.value;

        // Reset states
        errorMessage.classList.add('hidden');
        progressContainer.classList.add('hidden');
        
        // Validate URL
        if (!isValidYouTubeUrl(url)) {
            errorMessage.textContent = 'Please enter a valid YouTube URL';
            errorMessage.classList.remove('hidden');
            urlInput.focus();
            return;
        }

        try {
            // Show progress container
            progressContainer.classList.remove('hidden');
            startProgressAnimation();
            progressText.textContent = 'Fetching video information...';
            progressPercentage.textContent = '0%';
            
            // Send download request
            const response = await axios.post('php/download.php', {
                url: url,
                format: format
            }, {
                onDownloadProgress: (progressEvent) => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = `${percentCompleted}%`;
                    progressPercentage.textContent = `${percentCompleted}%`;
                    
                    if (percentCompleted < 30) {
                        progressText.textContent = 'Downloading video...';
                    } else if (percentCompleted < 60) {
                        progressText.textContent = 'Converting format...';
                    } else if (percentCompleted < 90) {
                        progressText.textContent = 'Preparing file...';
                    } else {
                        progressText.textContent = 'Almost done...';
                    }
                }
            });

            if (response.data.success) {
                stopProgressAnimation();
                progressText.textContent = 'Download complete!';
                progressBar.style.width = '100%';
                progressPercentage.textContent = '100%';
                
                // Trigger download
                window.location.href = response.data.file;
                
                // Reset form after 3 seconds
                setTimeout(() => {
                    progressContainer.classList.add('hidden');
                    progressBar.style.width = '0%';
                    form.reset();
                }, 3000);
            } else {
                throw new Error(response.data.message || 'Download failed');
            }
        } catch (error) {
            stopProgressAnimation();
            errorMessage.textContent = error.message || 'An unexpected error occurred';
            errorMessage.classList.remove('hidden');
            progressContainer.classList.add('hidden');
        }
    });

    // Clear error message when input changes
    urlInput.addEventListener('input', function() {
        errorMessage.classList.add('hidden');
    });
});
