<?php
// LLM Analysis Integration



/**
 * Perform analysis using the selected LLM service.
 *
 * @param string $transcript The YouTube video transcript.
 * @param string $service The selected service ('lm_studio' or 'openrouter').
 * @return string|null Content of the analysis, or null if error occurs.
 */
function analyzeTranscript($transcript, $service = 'lm_studio',$model,$videoId,$details) {
  
$systemPrompt = '
you are a professional youtube transcribt content fact checker and analyst.
Today is ' . date("l, F j, Y") . '.
Output: Single HTML(no css) div (German) containing:
1. Video Summary
2. Claims Table (ALL assertions) with columns:
   <th> Claim </th><th> Source </th><th> Importance </th><th> Rating </th><th> Reasoning </th>
3. Evaluation Metrics
Structure:
<div>
  <h1>Summary</h1>
  <p>[Core summary]</p>
  <ul>[Bullet-Points]</ul>
  
  <h1>Claims</h1>
  <table>
    <tr><th>Claim</th><th>Importance</th><th>Rating</th><th>Reasoning</th></tr>
    <tr><td>[Claim]</td><td>[Importance]</td><td>[Rating]</td><td>[Reasoning]</td></tr>
    ...
    <tr><td>[Claim]</td><td>[Importance]</td><td>[Rating]</td><td>[Reasoning]</td></tr>
  </table>
  
  <h1>Overall Evaluation</h1>
  <p>[...]</p>
  
  <h1>Metrics</h1>
  <div>
  <label for="file">Bullshitometer:</label><progress id="bullshitometer" max="100" value="[bullshitpercent]">[bullshitpercent]%</progress>[bullshitpercent]%
<label for="file">Validometer:</label><progress id="validometer" max="100" value="[validpercent]">[validpercent]%</progress>[validpercent]%
<label for="file">Level:</label><progress id="niveau" max="100" value="[niveaupercent]">[niveaupercent]%</progress>[niveaupercent]%
   </div>
   <div>[characterization]</div>
</div>

Importance scale: 
  10/10 = Core argument (High) 
  3-7/10 = Supporting claim (Medium) 
  0-2/10 = Minor aspect (Low)

Rating scale:
 ✔️ = 10/10 = Verified
 ✔️🤔 = 5-9/10 = Plausible speculation
 ⚠️🤔 3-5/10 = Moderate speculation
 ❌🤔 1-2/10 = Implausible speculation
 ❌ = 0/10 = Refuted

Level rating (max 100)
 level = [ 
  1. **Methodic Quality** (30% weight):  
   - Structure: Logical flow, coherent arguments, clear thesis.  
   - Rigor: Systematic analysis, avoids oversimplification.  
2. **Terminology Usage** (25% weight):  
   - Precision: Domain-specific terms used correctly.  
   - Sophistication: Avoids repetitive/vague language.  
3. **Scientific Reasoning** (30% weight):  
   - Evidence: Claims supported by data/examples.  
   - Logic: No fallacies (e.g., ad hominem, strawman).  
4. **Accuracy & Depth** (15% weight):  
   - Factual correctness.  
   - Conceptual depth (avoids superficiality).  

**Penalize**:  
- **-20 points**: Significant factual errors.  
- **-15 points**: Logical flaws/fallacies.  
- **-10 points**: Overly simplistic vocabulary/content
  ]';

  $userPrompt = "Analyze the video transcript:
  1. Summary: 
     - 1 paragraph of core theses
     - Bullet points of all main arguments
     
  2. Extract EVERY claim as a table row:
     - 'Claim': Literal wording
     - 'Source': [Channel position | Quote | Film content]
     - 'Importance': x/10
     - 'Rating': Only for channel positions x/10
     - 'Reasoning': Max. 1 sentence
  
  3. Overall Evaluation: 
     - Thesis strength, source quality, logical consistency
     - Bullshitometer: % of questionable channel claims (weighted average)
     - Validometer: % of verifiable statements
     - Level: % rating of argumentative quality (0-100)
  
4. Analyze the overall style of the video, for example classify it as manipulative, objective, scientific, propaganda, or hobbyist.

  Video title: ". $details['title'] ."
  Channel title: ". $details['channel_title'] ."
  Video description: ". $details['description'] ."
  Transcript: " . $transcript;

    if ($service == 'openrouter') {
        $endpoint = "https://openrouter.ai/api/v1/chat/completions";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . OPENROUTER_API_KEY,
        ];
        $model = !empty($model) ? $model : "deepseek/deepseek-r1-0528:free";
    } else { // Default to LM Studio
        $endpoint = LM_STUDIO_ENDPOINT . "/chat/completions";
        $headers = ["Content-Type: application/json"];
        $model = !empty($model) ? $model : "qwen3-30b-a3b-128k";
    }

    // Construct the payload
    $payload = json_encode([
        'model' => $model,
        "temperature" => 0.4,
        "seed" => 42,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
    ]);

    // Log the API request details for debugging
    error_log("API Request: " . print_r([
        'endpoint' => $endpoint,
        'headers' => $headers,
        'payload' => $payload
    ], true));

    // Make the API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("cURL Error: " . curl_error($ch));
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Handle successful response
    if ($httpCode >= 200 && $httpCode < 300) {
        $responseDecoded = json_decode($response, true);
        
        // Check if we have valid content in the expected structure
        if (isset($responseDecoded['choices'][0]['message']['content'])) {
            $analysisHtml = processText($responseDecoded['choices'][0]['message']['content']);
            error_log("API Request: " . print_r($analysisHtml, true));
            //metrics
            
            saveAnalysis($videoId,$details['title'], $details['description'] ,$details['channel_id'],$details['channel_title'], $transcript, $analysisHtml, extractMetricsFromHtml($analysisHtml),$service,$model);

            return $analysisHtml;
        } else {
            error_log("Invalid response structure: " . print_r($responseDecoded, true));
            return null;
        }
    }

    // Handle API errors
    error_log("API Error {$httpCode}: {$response}");
    return null;
}

function processText($text) {
    // Remove <think>...</think> tags and their content
    $processed = preg_replace('/<think>.*?<\/think>/s', '', $text);
    
    // Convert newlines to <br> tags
    #$processed = nl2br($processed);
    
    return $processed;
}

function extractMetricsFromHtml($html) {
    $metrics = [
        'bullshit' => 999,
        'valid' => 999,
        'niveau' => 999
    ];

// Bullshitometer-Wert extrahieren (auch Floats)
preg_match('/<progress\s+id="bullshitometer"[^>]+value="([0-9]+(?:\.[0-9]+)?)/i', $html, $bsMatches);
if ($bsMatches) $metrics['bullshit'] = (float)$bsMatches[1];

// Validometer-Wert extrahieren (auch Floats)
preg_match('/<progress\s+id="validometer"[^>]+value="([0-9]+(?:\.[0-9]+)?)/i', $html, $vMatches);
if ($vMatches) $metrics['valid'] = (float)$vMatches[1];

// Niveau-Wert extrahieren (auch Floats)
preg_match('/<progress\s+id="niveau"[^>]+value="([0-9]+(?:\.[0-9]+)?)/i', $html, $lMatches);
if ($lMatches) $metrics['niveau'] = (float)$lMatches[1];

    return $metrics;
}