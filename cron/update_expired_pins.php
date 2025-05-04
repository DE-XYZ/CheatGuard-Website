<?php
// cron/update_expired_pins.php
// This file should be set up as a cron job to run every hour

// Include necessary files
require_once dirname(__DIR__) . '/php/config.php';
require_once dirname(__DIR__) . '/php/db_functions.php';

// Update all expired pins across the system
$updated_count = updateExpiredPins();

// Log the result
$log_message = date('Y-m-d H:i:s') . " - Updated $updated_count expired PINs\n";
file_put_contents(dirname(__DIR__) . '/logs/cron.log', $log_message, FILE_APPEND);

echo "Updated $updated_count expired PINs";