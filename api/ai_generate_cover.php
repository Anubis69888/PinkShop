<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_seller'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

try {
    // 1. Receive Data
    $name = $_POST['name'] ?? 'Product';
    $category = $_POST['category'] ?? 'General';
    $description = $_POST['description'] ?? '';
    $material = $_POST['material'] ?? '';
    $style = $_POST['style'] ?? 'pastel, cute, high quality';

    // 2. Validate essential data
    if (empty($name)) {
        throw new Exception("กรุณากรอกชื่อสินค้าก่อนเริ่มสร้างรูปภาพ");
    }

    // 3. Generative AI Logic - "The World-Class Advertising Designer" Data
    
    // Constructing the "Masterpiece" Prompt
    $adjectives = "masterpiece, best quality, ultra-detailed, 8k, award winning photography, commercial advertisement";
    $lighting = "soft studio lighting, angelic rim light, dreamlike, cinematic lighting, volumetric";
    $composition = "centered, dynamic angle, golden ratio, clean background, bokeh";
    $style_keywords = "pastel palette, glassmorphism vibe, cute aesthetic, soft textures";
    
    // Core Subject Description from User Input
    $subject = "A beautiful product shot of {$name}, {$category}, made of {$material}. {$description}";
    
    // Combine into a powerful prompt
    // Structure: [Subject] + [Environment/Context] + [Lighting] + [Style/Quality]
    $prompt = "({$subject}), placed on a soft pastel pedestal, floating flower petals, {$style_keywords}, {$lighting}, {$composition}, {$adjectives}, {$style}";
    
    // Enhance for specific categories
    if ($category === 'dolls') {
        $prompt .= ", cute face, fluffy texture, hugging a heart";
    } elseif ($category === 'figures') {
        $prompt .= ", sharp focus, plastic texture, dynamic pose";
    }

    $encodedPrompt = urlencode($prompt);
    
    // Add a random seed
    $seed = rand(1, 999999);
    
    // Use Pollinations AI
    // Note: Pollinations is primarily specific text-to-image. 
    // Even if we received an image ($reference_image), current public free endpoints typically ignore image inputs or require specific complex workflows.
    // For this implementation, we rely on the *Super Prompt* to replicate the feel of the user's request.
    
    $imageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?seed={$seed}&width=1024&height=1024&nologo=true&enhance=true";
    
    echo json_encode([
        'success' => true,
        'image_url' => $imageUrl,
        'message' => 'สร้างรูปภาพสำเร็จ! (Powered by Pollinations AI)'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
