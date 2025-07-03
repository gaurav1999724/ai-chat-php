<?php
// Test the image generation API endpoint
header('Content-Type: application/json');

$url = 'http://localhost/ai-chat-php/api/generate_image.php';
$data = json_encode([
    'text' => 'a beautiful sunset over mountains',
    'width' => 512,
    'height' => 512,
    'steps' => 1,
    'session_id' => 'test123'
]);

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => $data
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Image Generation API Response:\n";
echo $result . "\n";

// Parse the response to test the format
$response = json_decode($result, true);
if ($response && isset($response['image_data']['generated_image'])) {
    echo "\n✅ SUCCESS: Image URL found: " . $response['image_data']['generated_image'] . "\n";
} else {
    echo "\n❌ ERROR: Expected 'generated_image' field not found in response\n";
    echo "Response structure: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
}
?> 