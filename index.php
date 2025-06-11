<?php
include_once('yt2text.php');
include_once('ytdata.php');
include_once('analyse.php');
include_once('utils.php');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['youtube_url'] ?? '';
    $provider = $_POST['provider'] ?? 'lm_studio';
    $model = $_POST['model'] ?? ''; // Default model
    $transcript = '';
    $analysis = null;
    $videoId = getYouTubeVideoId($url);
    if ($videoId == null) { die("incorrect link format"); }
    $db = get_db();
    $stmt = $db->prepare("SELECT transcript,channel_id,video_description,video_title,channel_title FROM video WHERE video_id = ?");
    $stmt->execute([$videoId]);
    $videodata = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log(print_r($videodata,true));
    if ($videodata) {
        error_log('transcript found. skip transcription');
        $details = [
            'channel_id'    => $videodata["channel_id"],
            'channel_title' => $videodata["channel_title"],
            'title'         => $videodata["video_title"],
            'description'   => $videodata["video_description"], // May be truncated!
        ];
        $analysis = analyzeTranscript($videodata["transcript"], $provider,$model,$videoId,$details);

    }else{
        error_log('no transcrpit found, try to make one..');
        $details = scrape_youtube_video_details($videoId);
    
        if(!$details){ die("no details found"); }
        // Process if valid URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // Get transcript
            $transcript = yt2text($url, $model);
            
            // Analyze transcript if valid
            if (!empty($transcript)) {
                $analysis = analyzeTranscript($transcript, $provider,$model,$videoId,$details);
            }
        }
    }

}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bullshitometer</title>
    <link rel="stylesheet" href="css.css">
</head>
<body>
    <div class="container">
        <header>
            
            <p>transcribe -> analyze -> bullshitometer</p>
        </header>
        
        <main>
            <div class="card">
                <form id="analysis-form" method="POST">
                    <div class="form-group">
                        <label for="youtube_url">YouTube URL:</label>
                        <input type="url" id="youtube_url" name="youtube_url" 
                               placeholder="https://www.youtube.com/watch?v=..." required
                               value="<?= htmlspecialchars($_POST['youtube_url'] ?? '') ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="provider">Analysis Provider:</label>
                            <select id="provider" name="provider" onchange="getModels(this.value);">
                                <option value="lm_studio" onselect="getModels('lm_studio');" <?= ($_POST['provider'] ?? 'lm_studio') === 'lm_studio' ? 'selected' : '' ?>>LM Studio</option>
                                <option value="openrouter" <?= ($_POST['provider'] ?? '') === 'openrouter' ? 'selected' : '' ?>>OpenRouter</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="model">Model:</label>
                            <select id="model" name="model">
                            <option value="qwen3-30b-a3b-128k" <?= ($_POST['model'] ?? '') === 'qwen3-30b-a3b-128k' ? 'selected' : '' ?>>qwen3-30b-a3b-128k</option>
                            <option value="qwen3-32b" <?= ($_POST['model'] ?? '') === 'qwen3-32b' ? 'selected' : '' ?>>qwen3-32b</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit">Analyze Transcript</button>
                </form>
            </div>
            
            <div class="loader" id="loader">
                <div class="spinner"></div>
                <p>Analyzing video content. This may take several minutes...</p>
            </div>
            
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="card result-section" id="result-section">
                    <div class="result-header">
                        <h2>Analysis Results</h2>
                        <button class="copy-btn" onclick="copyToClipboard()">Copy Results</button>
                    </div>
                    
     
                    
                    <?php if (!empty($analysis)): ?>
                        <div class="form-group">
                            <label>Analysis Output:</label>
                            <div id="analysis-output"><?= $analysis ?></div>
                        </div>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <p class="error">Error occurred during analysis. Please check server logs.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
function getModels(provider = 'lm_studio') {
    provider = provider == '' ? 'lm_studio' : provider;
  let url;
  let filterFn;

  if (provider === 'openrouter') {
    url = 'https://openrouter.ai/api/v1/models';
    // Filter for free models
    filterFn = model => Number(model.pricing?.completion) <= 0;
  } else if (provider === 'lm_studio') {
    url = 'http://localhost:1234/v1/models';
    // No filter for local models (or implement your own)
    filterFn = () => true;
  } else {
    console.error('Unknown provider:', provider);
    return;
  }

  fetch(url)
    .then(response => response.json())
    .then(models => {
      const modelList = {};
      var default_model = (provider ?? 'lm_studio') === 'openrouter' ? 'deepseek/deepseek-r1-0528:free' : 'qwen3-30b-a3b-128k';
      var options = '<option value="'+ default_model +'" selected>'+ default_model +'</option>';
      models.data.forEach(model => {
        if (filterFn(model)) {
            options += '<option value="'+ model.id +'">' + (model.name || model.id) + '</option>';
          modelList[model.id] = model.name || model.id;
        }
      });
      console.log(JSON.stringify(modelList, null, 2));
      document.getElementById('model').innerHTML = options;
      // Optionally, return modelList or process further here
      return modelList;
    })
    .catch(err => {
      console.error('Error fetching or parsing models:', err);
    });
}
getModels('<?= ($_POST['provider'] ?? 'lm_studio') === 'openrouter' ? 'openrouter' : '' ?>');
        document.getElementById('analysis-form').addEventListener('submit', function() {
            document.getElementById('loader').style.display = 'block';
            document.getElementById('result-section').style.display = 'none';
        });
        
        function copyToClipboard() {
            const output = document.getElementById('analysis-output');
            navigator.clipboard.writeText(output.innerText)
                .then(() => alert('Results copied to clipboard!'))
                .catch(err => console.error('Could not copy text: ', err));
        }
        
        // Show results if coming from POST submission
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            document.getElementById('loader').style.display = 'none';
            document.getElementById('result-section').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>