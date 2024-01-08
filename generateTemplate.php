<?php

require 'vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Check if the OpenAI API Key is set
if (!isset($_ENV['OPENAI_API_KEY'])) {
    error_log('Error: OpenAI API key not set in .env file');
    exit('API key not configured');
}

// Check if the script received a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Input validation
    // $recipientName = filter_input(INPUT_POST, 'recipientName', FILTER_SANITIZE_STRING);
    // $emailSubject = filter_input(INPUT_POST, 'emailSubject', FILTER_SANITIZE_STRING);
    $keyPoints = filter_input(INPUT_POST, 'keyPoints', FILTER_SANITIZE_STRING);
    $tone = filter_input(INPUT_POST, 'tone', FILTER_SANITIZE_STRING);
    $additionalInstructions = filter_input(INPUT_POST, 'additionalInstructions', FILTER_SANITIZE_STRING);

    // Get your OpenAI API Key from .env file
    $openai_api_key = $_ENV['OPENAI_API_KEY'];

    // Creating the prompt
    $prompt = createPrompt($keyPoints, $tone, $additionalInstructions);

    // Sending the request to OpenAI API
    $response = openaiRequest($prompt, $openai_api_key);

    $responseJson = json_encode($response);

    // Log the response to a file
    logToFile($responseJson);

    // Check for errors in the API response
    if (isset($response['error'])) {
        error_log('OpenAI API Error: ' . $response['error']);
        exit('Error generating template');
    }

    // Extracting the email template from the response
    $emailTemplate = $response['choices'][0]['message']['content'] ?? '';

    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode(['generatedTemplate' => $emailTemplate]);
} else {
    // Handle non-POST requests
    exit('Invalid request method');
}

// Function to create the prompt
function createPrompt($keyPoints, $tone, $additionalInstructions) {
    return json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are a helpful assistant asked to generate an email template based on specific instructions."
            ],
            [
                "role" => "user",
                "content" => "Create an email template addressing \{\{recipientName\}\}. Generate the subject line on your own. The key points to include are: {$keyPoints}. The tone should be {$tone}. Additional instructions: {$additionalInstructions}. The email should be professional and suitable for a business context."
            ]
        ]
    ]);
}

// Function to send a request to OpenAI API
function openaiRequest($data, $apiKey) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        error_log('cURL Error: ' . $err);
        return ['error' => $err];
    } else {
        return json_decode($response, true);
    }
}

function logToFile($data) {
    $logFile = 'log.txt';
    $currentData = file_get_contents($logFile);
    $currentData .= $data . "\n";
    file_put_contents($logFile, $currentData, LOCK_EX);
}