<?php

class AITrackingVerifier {
    
    // Regex patterns for major logistics providers in Thailand
    // Tighter constraints to reduce false positives
    private static $providers = [
        'Thailand Post' => '/^([E|R][A-Z]\d{9}TH)$/', // Strict S10 format
        'Kerry Express' => '/^(KEX\d{10}|KER[A-Z0-9]{8,})$/', 
        'Flash Express' => '/^TH[A-Z0-9]{10,12}$/', // TH followed by 10-12 alphanumeric characters
        'J&T Express' => '/^(8[0-9]{11}|6[0-9]{11})$/', // Starts with 8 or 6 usually
        'Ninja Van' => '/^(TH)[A-Z0-9]{9,12}$/',
        'Shopee Xpress' => '/^TH\d{10,12}$/',
        'DHL' => '/^\d{10}$/'
    ];

    /**
     * Verify if a tracking number matches any known provider format.
     * 
     * @param string $trackingNumber
     * @return array ['valid' => bool, 'provider' => string|null]
     */
    public static function verify($trackingNumber) {
        $trackingNumber = strtoupper(trim($trackingNumber));
        
        if (strlen($trackingNumber) < 8 || strlen($trackingNumber) > 20) {
            return ['valid' => false, 'provider' => null];
        }

        foreach (self::$providers as $provider => $pattern) {
            if (preg_match($pattern, $trackingNumber)) {
                
                // Additional Deep Check for Thai Post (S10 Standard)
                if ($provider === 'Thailand Post') {
                    if (!self::validateS10($trackingNumber)) {
                        continue; // Matches pattern but failed checksum -> invalid (or fake)
                    }
                }

                return ['valid' => true, 'provider' => $provider];
            }
        }

        return ['valid' => false, 'provider' => null];
    }

    /**
     * Validate S10 Checksum (Used by Thai Post)
     * Format: XX123456789TH
     * Weights: 8 6 4 2 3 5 9 7
     */
    private static function validateS10($number) {
        // Extract 8 digits
        $digits = substr($number, 2, 8);
        $checkDigit = intval(substr($number, 10, 1));
        
        $weights = [8, 6, 4, 2, 3, 5, 9, 7];
        $sum = 0;
        
        for ($i = 0; $i < 8; $i++) {
            $sum += intval($digits[$i]) * $weights[$i];
        }
        
        $remainder = $sum % 11;
        $calculatedCheck = 0;
        
        if ($remainder == 0) $calculatedCheck = 5;
        elseif ($remainder == 1) $calculatedCheck = 0;
        else $calculatedCheck = 11 - $remainder;
        
        return $checkDigit === $calculatedCheck;
    }
}
?>
