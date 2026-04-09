<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Threat Detection Thresholds
    |--------------------------------------------------------------------------
    |
    | These values control the sensitivity of the ThreatDetector service.
    | Adjust via environment variables without code changes.
    |
    */

    // Number of failed login attempts from a single IP before alerting
    'login_fail_threshold' => (int) env('SECURITY_LOGIN_FAIL_THRESHOLD', 5),

    // Time window (seconds) for counting login failures (default: 10 minutes)
    'login_fail_window' => (int) env('SECURITY_LOGIN_FAIL_WINDOW', 600),

    // Requests per minute from a single IP before flagging as automated attack
    'rapid_request_threshold' => (int) env('SECURITY_RAPID_REQUEST_THRESHOLD', 60),

    // Off-hours monitoring window (24h format, Malaysia time)
    'off_hours_start' => (int) env('SECURITY_OFF_HOURS_START', 22),
    'off_hours_end'   => (int) env('SECURITY_OFF_HOURS_END', 6),

    /*
    |--------------------------------------------------------------------------
    | Upload Rate Limiting
    |--------------------------------------------------------------------------
    */

    'upload_rate_limit' => (int) env('SECURITY_UPLOAD_RATE_LIMIT', 10),

];
