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

    function isValidYouTubeUrl(url) {
        const patterns = [
            /^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=[\w-]+)/,
            /^(https?:\/\/)?(www\.)?(youtu\.be\/[\w-]+)/,
            /^(https?:\/\/)?(www\.)?(youtube\.com\/embed\/[\w-]+)/,
            /^(https?:\/\/)?(www\.)?(youtube\.com\/v\/[\w-]+)/
        ];
        return patterns.some(pattern => pattern.test(url));
    }

    function resetUIState() {
        errorMessage.classList.add('hidden');
        progressContainer.classList.add('hidden');
        progressBar.style.width = '0%';
        progressText.textContent = 'Preparing download...';
        progressPercentage.textContent = '0%';
    }

    function startProgressAnimation() {
        progressBar.classList.add('progress-animated');
        progressContainer.classList.remove('hidden');
    }

    function stopProgressAnimation() {
        progressBar.classList.remove('progress-animated');
    }

    async function handleDownload(response) {
        const contentType = response.headers['content-type'];
        const data = response.data;

        // Check if response is JSON (error message)
        if (contentType.includes('application/json')) {
            const textDecoder = new TextDecoder();
            const jsonText = textDecoder.decode(data);
            const jsonResponse = JSON.parse(jsonText);
            
            if (!jsonResponse.success) {
                throw new Error(jsonResponse.message || 'Download failed');
            }
        }

        // Handle successful binary download
        const blob = new Blob([data], { type: contentType });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        
        // Get filename from headers or generate one
        let filename = 'download';
        const disposition = response.headers['content-disposition'];
        if (disposition && disposition.includes('filename=')) {
            filename = disposition.split('filename=')[1].replace(/['"]/g, '');
        }
        
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const url = urlInput.value.trim();
        const format = formatSelect.value;

        resetUIState();
        
        if (!isValidYouTubeUrl(url)) {
            errorMessage.textContent = 'Please enter a valid YouTube URL';
            errorMessage.classList.remove('hidden');
            urlInput.focus();
            return;
        }

        try {
            startProgressAnimation();
            progressText.textContent = 'Fetching video information...';
            
            const response = await axios.post('php/download.php', {
                url: url,
                format: format
            }, {
                responseType: 'blob',
                headers: {
                    'Accept': 'application/octet-stream'
                },
                onDownloadProgress: (progressEvent) => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
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

            await handleDownload(response);
            
            stopProgressAnimation();
            progressText.textContent = 'Download complete!';
            progressBar.style.width = '100%';
            progressPercentage.textContent = '100%';
            
            setTimeout(resetUIState, 3000);
            form.reset();

        } catch (error) {
            stopProgressAnimation();
            
            let errorMsg = 'An unexpected error occurred';
            if (error.response) {
                try {
                    const textDecoder = new TextDecoder();
                    const jsonText = textDecoder.decode(error.response.data);
                    const jsonResponse = JSON.parse(jsonText);
                    errorMsg = jsonResponse.message || errorMsg;
                } catch (e) {
                    errorMsg = error.response.statusText || errorMsg;
                }
            } else if (error.message) {
                errorMsg = error.message;
            }
            
            errorMessage.textContent = errorMsg;
            errorMessage.classList.remove('hidden');
            progressContainer.classList.add('hidden');
        }
    });

    urlInput.addEventListener('input', function() {
        errorMessage.classList.add('hidden');
    });
});
