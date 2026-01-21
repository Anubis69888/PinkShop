<?php
/**
 * Configuration file for AI services
 * IMPORTANT: Keep this file secure and never commit to version control
 */

// Gemini AI Configuration
define('GEMINI_API_KEY', 'AIzaSyCB9Qro98rAvhhKLzEXGmNC2TihVhmKzl0'); // Gemini API Key - KEEP SECURE!
define('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent');

// Verification Settings
define('PAYMENT_AMOUNT_TOLERANCE', 5); // Allow ±5 baht difference
define('PAYMENT_DATE_MAX_DAYS_OLD', 7); // Slip must not be older than 7 days
define('AI_VERIFICATION_ENABLED', true); // Set to false to disable AI verification

// API Timeout Settings
define('GEMINI_API_TIMEOUT', 30); // seconds
?>
