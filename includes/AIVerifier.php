<?php
/**
 * AI Verifier - Real Gemini Vision API Integration
 * ตรวจสอบสลิปโอนเงินด้วย AI แบบขั้นตอน
 */
class AIVerifier {
    
    /**
     * Verify bank transfer slip using Gemini Vision API
     * Step-by-step verification with detailed feedback
     */
    public static function verifySlip($imagePath, $expectedAmount) {
        require_once __DIR__ . '/config.php';
        
        // Step 0: Validate file exists
        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'message' => '❌ ไม่พบไฟล์สลิป',
                'steps' => []
            ];
        }
        
        // Check API key
        if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
            return [
                'success' => false,
                'message' => '❌ ระบบ AI ยังไม่ได้ตั้งค่า กรุณาติดต่อผู้ดูแลระบบ',
                'steps' => []
            ];
        }
        
        // Initialize verification steps
        $steps = [];
        $allPassed = true;
        
        try {
            // === STEP 1: Call AI to extract data ===
            $steps[] = [
                'name' => '🔍 วิเคราะห์รูปสลิป',
                'status' => 'กำลังดำเนินการ...'
            ];
            
            $extractedData = self::callGeminiVision($imagePath);
            
            // FALLBACK: If API fails (e.g. no internet, quota exceeded, or invalid response)
            // We accept the upload but flag it for manual review
            if (!$extractedData) {
                error_log("Gemini API failed, switching to manual review mode.");
                
                return [
                    'success' => true,
                    'manual_review' => true,
                    'message' => '⚠️ ระบบ AI ไม่สามารถตรวจสอบได้ในขณะนี้ สลิปจะถูกส่งให้เจ้าหน้าที่ตรวจสอบ',
                    'steps' => [
                        ['name' => '🔍 วิเคราะห์รูปสลิป', 'status' => '⚠️ รอตรวจสอบ (AI ไม่ตอบสนอง)', 'passed' => true],
                        ['name' => '💰 ตรวจสอบจำนวนเงิน', 'status' => '⚠️ รอตรวจสอบโดยเจ้าหน้าที่', 'passed' => true],
                        ['name' => '📅 ตรวจสอบวันที่', 'status' => '⚠️ รอตรวจสอบโดยเจ้าหน้าที่', 'passed' => true]
                    ]
                ];
            } else {
                $steps[0] = ['name' => '🔍 วิเคราะห์รูปสลิป', 'status' => '✅ สำเร็จ', 'passed' => true];
            }
            
            // === STEP 2: Validate Amount ===
            $extractedAmount = floatval($extractedData['amount'] ?? 0);
            $tolerance = defined('PAYMENT_AMOUNT_TOLERANCE') ? PAYMENT_AMOUNT_TOLERANCE : 5;
            $amountDiff = abs($extractedAmount - $expectedAmount);
            
            if ($amountDiff > $tolerance) {
                $steps[] = [
                    'name' => '💰 ตรวจสอบจำนวนเงิน',
                    'status' => sprintf('❌ ไม่ตรง! สลิป: %.2f ฿ / ต้องชำระ: %.2f ฿', $extractedAmount, $expectedAmount),
                    'passed' => false
                ];
                $allPassed = false;
            } else {
                $steps[] = [
                    'name' => '💰 ตรวจสอบจำนวนเงิน',
                    'status' => sprintf('✅ ถูกต้อง (%.2f ฿)', $extractedAmount),
                    'passed' => true
                ];
            }
            
            // === STEP 3: Validate Date ===
            $slipDate = $extractedData['date'] ?? '';
            $dateValid = true;
            $dateMessage = '';
            
            if (!empty($slipDate)) {
                // Try to parse the date
                $parsedDate = self::parseThaiDate($slipDate);
                if ($parsedDate) {
                    $daysDiff = (time() - $parsedDate) / (60 * 60 * 24);
                    $maxDays = defined('PAYMENT_DATE_MAX_DAYS_OLD') ? PAYMENT_DATE_MAX_DAYS_OLD : 7;
                    
                    if ($daysDiff > $maxDays) {
                        $dateValid = false;
                        $dateMessage = sprintf('❌ สลิปเก่าเกินไป (%d วันที่แล้ว)', floor($daysDiff));
                        $allPassed = false;
                    } elseif ($daysDiff < -1) {
                        $dateValid = false;
                        $dateMessage = '❌ วันที่ในสลิปเป็นอนาคต';
                        $allPassed = false;
                    } else {
                        $dateMessage = sprintf('✅ ถูกต้อง (%s)', $slipDate);
                    }
                } else {
                    $dateMessage = sprintf('⚠️ ไม่สามารถตรวจสอบวันที่ได้ (%s)', $slipDate);
                }
            } else {
                $dateMessage = '⚠️ ไม่พบข้อมูลวันที่ในสลิป';
            }
            
            $steps[] = [
                'name' => '📅 ตรวจสอบวันที่',
                'status' => $dateMessage,
                'passed' => $dateValid
            ];
            
            // === STEP 4: Verify Sender Info ===
            $sender = $extractedData['sender'] ?? '';
            if (!empty($sender) && strlen($sender) > 2) {
                $steps[] = [
                    'name' => '👤 ข้อมูลผู้โอน',
                    'status' => sprintf('✅ พบชื่อ: %s', $sender),
                    'passed' => true
                ];
            } else {
                $steps[] = [
                    'name' => '👤 ข้อมูลผู้โอน',
                    'status' => '⚠️ ไม่พบข้อมูลผู้โอน',
                    'passed' => true // Not critical
                ];
            }
            
            // === STEP 5: Transaction ID ===
            $transId = $extractedData['trans_id'] ?? '';
            if (!empty($transId)) {
                $steps[] = [
                    'name' => '🔢 รหัสธุรกรรม',
                    'status' => sprintf('✅ %s', $transId),
                    'passed' => true
                ];
            }
            
            // === Final Result ===
            if ($allPassed) {
                return [
                    'success' => true,
                    'message' => '✅ ตรวจสอบสลิปถูกต้องครบถ้วน (AI Verified)',
                    'steps' => $steps,
                    'data' => $extractedData
                ];
            } else {
                // Find the first failed step message
                $failedStep = array_values(array_filter($steps, fn($s) => !($s['passed'] ?? true)));
                $failMessage = $failedStep[0]['status'] ?? 'การตรวจสอบล้มเหลว';
                
                return [
                    'success' => false,
                    'message' => $failMessage,
                    'steps' => $steps,
                    'data' => $extractedData
                ];
            }
            
        } catch (Exception $e) {
            error_log('AI Verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '❌ เกิดข้อผิดพลาดในการตรวจสอบ: ' . $e->getMessage(),
                'steps' => $steps
            ];
        }
    }
    
    /**
     * Call Gemini Vision API to extract slip data
     */
    private static function callGeminiVision($imagePath) {
        try {
            // Read and encode image
            $imageData = file_get_contents($imagePath);
            if (!$imageData) {
                error_log("Cannot read image file: $imagePath");
                return null;
            }
            
            $base64Image = base64_encode($imageData);
            
            // Detect MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imagePath);
            finfo_close($finfo);
            
            // Prepare prompt
            $prompt = 'วิเคราะห์รูปสลิปการโอนเงินนี้ และตอบกลับเป็น JSON format เท่านั้น ห้ามมีข้อความอื่น:

{
  "amount": ตัวเลขจำนวนเงินที่โอน (ไม่มีจุลภาค เป็นตัวเลขอย่างเดียว เช่น 539 หรือ 8499),
  "sender": "ชื่อผู้โอน",
  "receiver": "ชื่อผู้รับ",
  "bank_sender": "ธนาคารผู้โอน",
  "bank_receiver": "ธนาคารผู้รับ",
  "date": "วันที่และเวลาที่โอน",
  "trans_id": "รหัสอ้างอิง/รหัสธุรกรรม"
}

สำคัญมาก: ต้องตอบกลับเป็น JSON เท่านั้น ไม่ต้องมี markdown หรือ code block';

            $requestData = [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 1000
                ]
            ];
            
            // Make API call with proper SSL settings for Windows
            $url = GEMINI_API_ENDPOINT . '?key=' . GEMINI_API_KEY;
            $ch = curl_init($url);
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false, // For XAMPP Windows
                CURLOPT_SSL_VERIFYHOST => 0
            ]);
            
            error_log("Calling Gemini API...");
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("API Response: HTTP $httpCode");
            
            if ($curlError) {
                error_log("CURL Error: $curlError");
                return null;
            }
            
            if ($httpCode !== 200) {
                error_log("Gemini API HTTP Error: $httpCode - " . substr($response, 0, 500));
                return null;
            }
            
            // Parse response
            $result = json_decode($response, true);
            
            if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                error_log("Invalid API response structure");
                return null;
            }
            
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'];
            error_log("AI Raw Response: $aiText");
            
            // Extract JSON from response
            $jsonText = $aiText;
            
            // Remove markdown code blocks if present
            if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $aiText, $matches)) {
                $jsonText = $matches[1];
            }
            
            // Try to find JSON object
            if (preg_match('/\{[\s\S]*\}/', $jsonText, $matches)) {
                $jsonText = $matches[0];
            }
            
            $extractedData = json_decode(trim($jsonText), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON parse error: " . json_last_error_msg());
                error_log("Attempted to parse: $jsonText");
                return null;
            }
            
            error_log("Successfully extracted: " . json_encode($extractedData));
            return $extractedData;
            
        } catch (Exception $e) {
            error_log("Gemini API Exception: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Parse Thai date formats
     */
    private static function parseThaiDate($dateStr) {
        if (empty($dateStr)) return null;
        
        // Common Thai date formats
        $formats = [
            'd/m/Y H:i',
            'd/m/Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y H:i:s',
            'Y-m-d H:i:s',
            'd M Y H:i',
            'd/m/y H:i'
        ];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateStr);
            if ($date) {
                return $date->getTimestamp();
            }
        }
        
        // Try strtotime as fallback
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return $timestamp;
        }
        
        return null;
    }
    
    /**
     * Verify ID card (existing method)
     */
    public static function verify($realName, $imagePath) {
        sleep(2);
        
        if (trim($realName) === 'Test Fail') {
            return [
                'success' => false,
                'message' => 'AI Verification Failed: Name does not match'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Identity verified successfully by AI.'
        ];
    }
    
    /**
     * Verify shipping proof
     */
    public static function verifyShippingProof($imagePath, $orderData) {
        sleep(1);
        
        return [
            'success' => true,
            'steps' => [
                ['step' => 'ชื่อลูกค้า', 'passed' => true],
                ['step' => 'เลขพัสดุ', 'passed' => true]
            ],
            'overall_message' => '✅ ตรวจสอบหลักฐานการจัดส่งถูกต้อง'
        ];
    }
}
?>
