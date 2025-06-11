<?php
// Utility functions

// Use absolute path for config file
require_once __DIR__ . '/config/config.php';

/**
 * Extract YouTube video ID from URL.
 *
 * @param string $url The YouTube video URL.
 * @return string|null The video ID, or null if invalid.
 */
function getYouTubeVideoId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return null;
}



/**
 * Make a cURL request to an API endpoint.
 *
 * @param string $url The API endpoint URL.
 * @param array $headers Optional headers for the request.
 * @param int $timeout Timeout in seconds.
 * @return mixed JSON-decoded response, or false on error.
 */
function apiRequest($url, $headers = [], $timeout = 120) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        // Handle cURL error
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        try {
            return json_decode($response, true);
        } catch (Exception $e) {
            return false;
        }
    }

    return false;
}
function get_db(){
    try {
    return new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    return false;
}
}


function saveAnalysis($videoId, $vtitle, $vdescription, $channelId, $channel_title, $transcript, $analysisHtml, $metrics, $llm_provider, $llm_model) {
   $db = get_db();
    // Set defaults
    $metrics = array_merge([
        'bullshit' => 999,
        'valid' => 999,
        'niveau' => 999
    ], $metrics);

    try {


        $sql = "INSERT INTO video (
            llm_provider,
            llm_model,
            video_id,
            video_title,
            video_description, 
            channel_id,
            channel_title, 
            transcript, 
            analysis_html, 
            bullshit_percent, 
            valid_percent, 
            niveau_percent
        ) VALUES (
            :llm_provider,
            :llm_model,
            :video_id,
            :video_title,
            :video_description,
            :channel_id,
            :channel_title,
            :transcript,
            :analysis_html,
            :bullshit,
            :valid,
            :niveau
        )";

        $stmt = $db->prepare($sql);
        
        $params = [
            ':llm_provider' => $llm_provider,
            ':llm_model' => $llm_model,
            ':video_id' => $videoId,
            ':video_title' => $vtitle,
            ':video_description' => $vdescription,
            ':channel_id' => $channelId,
            ':channel_title' => $channel_title,
            ':transcript' => $transcript,
            ':analysis_html' => $analysisHtml,
            ':bullshit' => $metrics['bullshit'],
            ':valid' => $metrics['valid'],
            ':niveau' => $metrics['niveau']
        ];

        $stmt->execute($params);
        return true;
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        return false;
    }
}