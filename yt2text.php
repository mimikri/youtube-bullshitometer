<?php

function yt2text($url,$model){
// --- CONFIG ---
$videoUrl = $url; // Replace with your YouTube URL
$tmpAudio = 'audio_tmp.m4a'; // Temporary file
$finalMp3 = 'audio_for_whisper.mp3'; // Output for Whisper
$whisperModel = 'large-v3-turbo'; // Whisper model
$language = 'de'; // Language code

// Step 0: Unload the LM Studio models before transcription to free gpu ram
exec("lms unload --all", $output, $returnCode);
if ($returnCode !== 0) {
    echo "Failed to unload model: " . implode("\n", $output) . "\n";
}

// Step 1: Download best audio from YouTube
exec("yt-dlp -f bestaudio -o \"$tmpAudio\" \"$videoUrl\"");

// Step 2: Convert to low-bitrate mono MP3
exec("ffmpeg -y -i \"$tmpAudio\" -ab 32k -ac 1 \"$finalMp3\"");

// Step 3: Delete temp file
unlink($tmpAudio);

// Step 4: Transcribe with Whisper (only text output, no timestamps)
$whisperCmd = "whisper \"$finalMp3\" --model $whisperModel --language $language --output_format txt 2>&1";
exec($whisperCmd, $output, $returnCode);

$transcript = '';
if ($returnCode === 0) {
    // Read the generated TXT file
    $txtFile = pathinfo($finalMp3, PATHINFO_FILENAME) . '.txt';
    if (file_exists($txtFile)) {
        $transcript = file_get_contents($txtFile);
        // Clean up files
        unlink($finalMp3);
        unlink($txtFile);
    } else {
        $transcript = "Error: Transcript file not found";
    }
} else {
    $transcript = "Whisper Error (Code $returnCode):\n" . implode("\n", $output);
}



// Step 5: Load the LM Studio model back after transcription
exec("lms load " . $model, $output, $returnCode);
if ($returnCode !== 0) {
    echo "Failed to load model: " . implode("\n", $output) . "\n";
}

echo "\nProcess completed!\n";
return $transcript;

}
?>
