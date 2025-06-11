<?php
function scrape_youtube_video_details($video_id) {
    
    $url = "https://www.youtube.com/watch?v=" . urlencode($video_id);
    $html = @file_get_contents($url);

    if ($html === false) {
        return false; // Could not fetch page
    }

    // Try to extract data from ytInitialPlayerResponse JSON
    if (preg_match('/ytInitialPlayerResponse\s*=\s*({.+?});/', $html, $matches)) {
        $json = json_decode($matches[1], true);
        if (!$json) return false;

        $videoDetails = $json['videoDetails'] ?? null;
        if (!$videoDetails) return false;

        return [
            'channel_id'    => $videoDetails['channelId'] ?? null,
            'channel_title' => $videoDetails['author'] ?? null,
            'title'         => $videoDetails['title'] ?? null,
            'description'   => $videoDetails['shortDescription'] ?? null, // This is the text under the video!
        ];
    }

    // Fallback: Try to extract from <meta> tags if JSON not found
    preg_match('/<meta itemprop="channelId" content="([^"]+)"/', $html, $cMatch);
    preg_match('/<meta itemprop="name" content="([^"]+)"/', $html, $ctMatch);
    preg_match('/<meta name="title" content="([^"]+)"/', $html, $tMatch);
    preg_match('/<meta name="description" content="([^"]+)"/', $html, $dMatch);

    if (!empty($cMatch[1]) && !empty($ctMatch[1]) && !empty($tMatch[1]) && !empty($dMatch[1])) {
        return [
            'channel_id'    => $cMatch[1],
            'channel_title' => $ctMatch[1],
            'title'         => $tMatch[1],
            'description'   => $dMatch[1], // May be truncated!
        ];
    }

    return false; // Could not extract details
}
