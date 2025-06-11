<?php
// Configuration File

define('OPENROUTER_API_KEY', 'your_open_router_key');
define('LM_STUDIO_ENDPOINT', 'http://localhost:1234/v1');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'yt_analyse');
define('DB_USER', 'username');
define('DB_PASS', 'password');

// YouTube URL regex pattern
define('YOUTUBE_URL_PATTERN', '/^(https?:\/\/(?:www\.)?youtube\.com\/watch\?v=|https?:\/\/youtu\.be\/)[a-zA-Z0-9_-]+$/');