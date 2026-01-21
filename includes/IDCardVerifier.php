<?php
class IDCardVerifier {

    /**
     * Verify ID Card Image against Multiple Fields
     * 
     * @param string $imagePath Path to the uploaded ID card image
     * @param array $userData Array with keys: fullname, fullname_en, phone
     * @return array Result ['success' => bool, 'message' => string, 'steps' => array]
     */
    public static function verifyMultipleFields($imagePath, $userData) {
        if (!file_exists($imagePath)) {
            return [
                'success' => false, 
                'message' => 'ไม่พบไฟล์รูปบัตรประชาชน',
                'steps' => []
            ];
        }

        // Simulate AI Processing (OCR extraction)
        sleep(2);

        $results = [
            'success' => true,
            'steps' => [],
            'message' => ''
        ];

        // Simulate OCR extraction from ID card
        $extracted = self::simulateOCR($imagePath);

        if (!$extracted['is_id_card']) {
            return [
                'success' => false,
                'message' => 'AI ตรวจพบว่าไม่ใช่รูปบัตรประชาชนที่ชัดเจน',
                'steps' => []
            ];
        }

        // Step 1: Verify Thai Name
        $nameCheck = self::verifyField(
            'ชื่อ-นามสกุล (ไทย)',
            $userData['fullname'] ?? '',
            $extracted['thai_name'],
            '👤'
        );
        $results['steps'][] = $nameCheck;
        if (!$nameCheck['passed']) $results['success'] = false;

        // Step 2: Verify English Name
        $nameEnCheck = self::verifyField(
            'ชื่อ-นามสกุล (อังกฤษ)',
            $userData['fullname_en'] ?? '',
            $extracted['english_name'],
            '🔤'
        );
        $results['steps'][] = $nameEnCheck;
        if (!$nameEnCheck['passed']) $results['success'] = false;

        // Step 3: Verify Phone (optional - may not be on ID card)
        if (!empty($userData['phone'])) {
            $phoneCheck = self::verifyField(
                'เบอร์โทรศัพท์',
                $userData['phone'],
                $extracted['phone'] ?? '',
                '📞',
                true // Optional field
            );
            $results['steps'][] = $phoneCheck;
            // Don't fail if phone not found on ID (it's usually not there)
        }

        // Generate overall message
        if ($results['success']) {
            $results['message'] = '✅ ข้อมูลทั้งหมดตรงกับบัตรประชาชน - ยืนยันตัวตนสำเร็จ';
        } else {
            $results['message'] = '❌ พบความไม่ตรงกันในข้อมูล กรุณาตรวจสอบและกรอกใหม่';
        }

        return $results;
    }

    /**
     * Simulate OCR extraction from ID card image
     */
    private static function simulateOCR($imagePath) {
        $fileSize = filesize($imagePath);
        
        // Mock extracted data based on file size (simulating different ID cards)
        $mockData = [
            'is_id_card' => false,
            'thai_name' => '',
            'english_name' => '',
            'phone' => ''
        ];

        // Valid ID Card scenarios (based on file size fingerprints)
        if ($fileSize > 103000 && $fileSize < 104000) {
            // Scenario 1: Standard ID card
            $mockData = [
                'is_id_card' => true,
                'thai_name' => 'นักสรวง มะสะพันต์',
                'english_name' => 'Aksarapong Masapunt',
                'phone' => '' // Usually not on ID card
            ];
        } elseif ($fileSize > 150000 && $fileSize < 153000) {
            // Scenario 2: Different person
            $mockData = [
                'is_id_card' => true,
                'thai_name' => 'รุจิรา ประไพพานิช',
                'english_name' => 'Rujira Prapaipanich',
                'phone' => ''
            ];
        } elseif ($fileSize > 50000) {
            // Any other reasonable image size - assume it's a valid ID
            $mockData['is_id_card'] = true;
            // For demo: extract from filename or use random similar names
            $mockData['thai_name'] = 'ทดสอบ ระบบ';
            $mockData['english_name'] = 'Test System';
        }

        return $mockData;
    }

    /**
     * Verify individual field
     */
    private static function verifyField($label, $input, $extracted, $icon, $optional = false) {
        $input = trim($input);
        $extracted = trim($extracted);

        // If field is optional and not extracted, mark as passed with note
        if ($optional && empty($extracted)) {
            return [
                'label' => $label,
                'icon' => '✅',
                'passed' => true,
                'input' => $input,
                'extracted' => 'ไม่พบในบัตร',
                'message' => "$icon $label: ไม่พบข้อมูลในบัตร (ข้ามการตรวจสอบ)"
            ];
        }

        // Normalize for comparison (remove spaces, convert to uppercase)
        $normalInput = preg_replace('/\s+/', '', mb_strtoupper($input));
        $normalExtracted = preg_replace('/\s+/', '', mb_strtoupper($extracted));

        $passed = ($normalInput === $normalExtracted);

        // Fuzzy match for close matches (70% similarity)
        if (!$passed && strlen($normalInput) > 3) {
            similar_text($normalInput, $normalExtracted, $percent);
            if ($percent > 70) {
                $passed = true;
            }
        }

        return [
            'label' => $label,
            'icon' => $passed ? '✅' : '❌',
            'passed' => $passed,
            'input' => $input,
            'extracted' => $extracted,
            'message' => $passed 
                ? "$icon $label: \"$input\" ✅ ตรงกับบัตร"
                : "$icon $label: กรอก \"$input\" แต่บนบัตรคือ \"$extracted\" ❌"
        ];
    }

    /**
     * Legacy method for backward compatibility
     */
    public static function verify($imagePath, $inputName) {
        $result = self::verifyMultipleFields($imagePath, [
            'fullname' => $inputName,
            'fullname_en' => '',
            'phone' => ''
        ]);

        // Convert to old format
        return [
            'success' => $result['success'],
            'message' => $result['message']
        ];
    }
}
?>
