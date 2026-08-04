<?php
session_start();
header('Content-Type: application/json');

include('../includes/dbconnection.php');

if (!isset($conn) || !$conn) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Configuration
$gemini_api_key = ""; // Gemini API Key
$gemini_api_url = "" . $gemini_api_key;

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['message'])) {
    echo json_encode(['error' => 'Message is required.']);
    exit;
}

$user_message = $input['message'];

// Get products from the database
$shop_context = "Here are some of our available products and their prices:\n";
$query = "SELECT productName, price, oldPrice FROM Product WHERE status = 'Active' LIMIT 100";
$result = mysqli_query($conn, $query);

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $shop_context .= "- " . $row['productName'] . " : LKR " . number_format($row['price'], 2) . "\n";
    }
}

// Brief, engaging System Instruction.
$system_instruction = "You are a highly skilled, persuasive, yet CONCISE Sales Assistant for Tech Shark Computer Shop. Your goal is to increase sales while keeping responses short, scannable, and engaging. Customers lose interest in long paragraphs.

IMPORTANT RULES:
1. BE SHORT & PUNCHY: NEVER write long paragraphs. Keep your explanations to 2-3 short sentences maximum. 
2. USE FORMATTING: Use bullet points when listing products or features to make them easy to read.
3. QUICK BENEFITS: Mention only 1 or 2 core benefits of a product. Do not over-explain technical details unless explicitly asked.
4. SMART ALTERNATIVES: If a product is not in the list, briefly say 'We can specially arrange that! Alternatively, check out this great option: [Product]' in just one sentence.
5. STRICT INVENTORY: Use ONLY the provided shop product list for prices and availability. Do not invent prices.

Shop Product List:\n\n" . $shop_context;

// Creating chat history
if (!isset($_SESSION['ai_chat_history'])) {
    $_SESSION['ai_chat_history'] = [];
}

// Add new message to History
$_SESSION['ai_chat_history'][] = [
    "role" => "user",
    "parts" => [["text" => $user_message]]
];

// Set Payload
$data = [
    "systemInstruction" => [
        "parts" => [["text" => $system_instruction]]
    ],
    "contents" => $_SESSION['ai_chat_history'], // Send entire history
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 800
    ]
];

$ch = curl_init($gemini_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_reply = $result['candidates'][0]['content']['parts'][0]['text'];

        // Saving the answers given by AI to history
        $_SESSION['ai_chat_history'][] = [
            "role" => "model",
            "parts" => [["text" => $ai_reply]]
        ];

        echo json_encode(['reply' => $ai_reply]);
    } else {
        echo json_encode(['error' => 'Invalid response structure from AI.']);
    }
} else {
    echo json_encode(['error' => 'Error communicating with AI service.', 'details' => $response]);
}
?>
