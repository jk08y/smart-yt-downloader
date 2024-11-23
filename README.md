# Smart YouTube Downloader 🎥

A modern, user-friendly YouTube video downloader with a sleek UI built using PHP, JavaScript, and Tailwind CSS. Download YouTube videos in various formats (MP4/MP3) with different quality options.

## Demo

![Smart YouTube Downloader Demo](https://raw.githubusercontent.com/jk08y/smart-yt-downloader/refs/heads/improved-version/demo/download-demo.gif)

## ✨ Features

* 📱 Modern, responsive UI with gradient design
* 🎥 Multiple video formats (MP4 720p/1080p)
* 🎵 Audio extraction (MP3 192kbps/320kbps)
* ⚡ Real-time download progress tracking
* 🛡️ Comprehensive error handling and validation
* 🧹 Automatic cleanup of old downloads
* 🔒 Secure file handling and sanitization
* 📊 Progress bar with status updates

## 🔧 Prerequisites

* PHP >= 7.4
* Composer
* youtube-dl
* ffmpeg (for audio conversion)
* Web server (Apache/Nginx)
* php-curl extension
* php-json extension

## 📦 Installation

1. Clone the repository:
```bash
git clone https://github.com/jk08y/smart-yt-downloader.git
cd smart-yt-downloader
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install youtube-dl:
```bash
# On Ubuntu/Debian
sudo apt update
sudo apt install youtube-dl

# On MacOS
brew install youtube-dl

# On Windows (using Chocolatey)
choco install youtube-dl
```

4. Install ffmpeg:
```bash
# On Ubuntu/Debian
sudo apt install ffmpeg

# On MacOS
brew install ffmpeg

# On Windows (using Chocolatey)
choco install ffmpeg
```

5. Set up directory permissions:
```bash
# Create required directories
mkdir -p downloads/temp
mkdir -p logs

# Set directory permissions
chmod 755 downloads/
chmod 755 downloads/temp/
chmod 755 logs/

# Set ownership (replace www-data with your web server user)
chown -R www-data:www-data downloads/
chown -R www-data:www-data logs/
```

6. Create and configure cookies file:
```bash
touch cookies.txt
chmod 644 cookies.txt
chown www-data:www-data cookies.txt
```

## ⚙️ Configuration

1. Server Requirements:
   - Enable PHP extensions: curl, json, fileinfo
   - Ensure `allow_url_fopen` is enabled in php.ini
   - Set appropriate `post_max_size` and `upload_max_size` in php.ini

2. Web Server Configuration:

   For Apache (.htaccess):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

   For Nginx:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

3. Customize settings in `php/config.php`:
```php
// Maximum file lifetime before cleanup (default 1 hour)
define('FILE_LIFETIME', 3600);

// Memory limit
ini_set('memory_limit', '512M');

// Maximum execution time
set_time_limit(300);
```

## 🔐 Security Implementation

1. File Security:
   - Automatic file cleanup after download
   - Filename sanitization
   - MIME type validation
   - Secure file permissions
   - Temporary file handling

2. Input Validation:
   - URL validation
   - Format validation
   - Request method validation
   - Content-type validation

3. Error Handling:
   - Comprehensive error logging
   - User-friendly error messages
   - Exception handling for all operations

## 🚀 Usage

1. Access the application through your web browser
2. Paste a YouTube URL into the input field
3. Select desired format:
   - MP4: 720p or 1080p
   - MP3: 192kbps or 320kbps
4. Click "Start Download"
5. Monitor real-time progress
6. File will automatically download when ready

## 💻 Development

Project structure:
```
.
├── css/
│   └── style.css
├── downloads/
│   └── temp/
├── js/
│   └── script.js
├── logs/
├── php/
│   ├── config.php
│   └── download.php
├── vendor/
├── composer.json
├── cookies.txt
└── index.php
```

## 🔍 Troubleshooting

Common issues and solutions:

1. Permission Issues:
```bash
# Check directory permissions
ls -la downloads/
ls -la logs/

# Fix permissions if needed
chmod -R 755 downloads/
chmod -R 644 logs/*
```

2. Download Failures:
   - Check logs in `logs/download_errors.log`
   - Verify youtube-dl is up to date
   - Ensure ffmpeg is properly installed

3. Performance Issues:
   - Increase PHP memory limit
   - Adjust max execution time
   - Check server CPU/memory usage

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/improvement`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/improvement`
5. Submit a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

* [youtube-dl](https://youtube-dl.org/) - Core downloading functionality
* [Tailwind CSS](https://tailwindcss.com/) - UI framework
* [Font Awesome](https://fontawesome.com/) - Icons
* [Axios](https://axios-http.com/) - HTTP client

## ⚠️ Disclaimer

This tool is for educational purposes only. Please respect YouTube's terms of service and content creators' rights.

## 📞 Support

If you encounter issues or have questions, please [open an issue](https://github.com/jk08y/smart-yt-downloader/issues).

## 🔗 Connect

- 𝕏: [@jk08y](https://x.com/jk08y)
- GitHub: [@jk08y](https://github.com/jk08y)
