# YouTube Video Analysis Tool<br>
<br>
This is a PHP-based tool for analyzing and evaluating the credibility of YouTube video content. The application transcribes videos, analyzes the transcript using AI models, and provides metrics on the validity, bullshit percentage, and overall quality of the content.<br>
<br>
## Table of Contents<br>
- [Features](#features)<br>
- [Installation](#installation)<br>
- [Usage](#usage)<br>
- [Configuration](#configuration)<br>
- [Database Schema](#database-schema)<br>
<br>
## Features<br>
<br>
- Transcribe YouTube videos to text using Whisper model.<br>
- Analyze transcripts with AI language models from LM Studio or OpenRouter API.<br>
- Generate credibility metrics for video content:<br>
  - Bullshitometer: % of questionable claims<br>
  - Validometer: % of verifiable statements<br>
  - Niveau: Quality and depth of arguments<br>
- Display analysis results in a user-friendly dashboard<br>
- Save analyzed videos with their transcripts and evaluations to a database<br>
<br>
## Installation<br>
<br>
1. Clone the repository:<br>
   ```<br>
   git clone https://github.com/mimikri/youtube-bullshitometer.git<br>
   cd youtube-bullshitometer<br>
   ```<br>
<br>
2. Install necessary dependencies:<br>
<br>
   - PHP >= 8.x<br>
   - Composer for PHP packages<br>
   - MySQL/MariaDB database server<br>
   - FFmpeg and yt-dlp installed on the system<br>
<br>
3. Set up the database:<br>
   ```<br>
   mysql -u root -p < db.sql<br>
   ```<br>
<br>
4. Configure your settings by copying `config/sample.config.php` to `config/config.php` and filling in the required information.<br>
<br>
5. Ensure that you have a local instance of LM Studio running, or obtain an API key for OpenRouter.<br>
<br>
## Usage<br>
<br>
1. Start PHP's built-in server:<br>
   ```<br>
   php -S localhost:8000<br>
   ```<br>
<br>
2. Navigate to `http://localhost:8000` in your web browser.<br>
<br>
3. Enter a YouTube video URL, select analysis provider and model, then submit the form.<br>
<br>
4. The application will process the video, analyze it, and display results once complete.<br>
<br>
5. Use localhost:8000/overview.php to see all saved results.<br>
<br>
## Configuration<br>
<br>
The main configuration file is located at `/config/config.php`. It contains constants for:<br>
<br>
- `OPENROUTER_API_KEY`: Your API key from OpenRouter<br>
- `LM_STUDIO_ENDPOINT`: URL to your local LM Studio server<br>
- Database connection details: DB_HOST, DB_NAME, DB_USER, and DB_PASS<br>
<br>
<br>
<br>
### Video Table <br>
<br>
Run db.sql in your mysql database to create the db wich saves results.<br>
<br>
<br>
<br>
## Contributing<br>
<br>
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.<br>
<br>
## License<br>
This project is licensed under the MIT License.<br>
