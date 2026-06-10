<?php

require_once __DIR__ . '/Message.php';
class SmsService {
    /**
     * Sends an SMS message.
     *
     * In a real application, this method would integrate with an SMS gateway API
     * like Twilio, Africa's Talking, etc. For this example, we will simulate
     * the action by logging the message to a file.
     *
     * @param int $sender_id The ID of the user sending the message.
     * @param int $recipient_id The ID of the user receiving the message.
     * @param string $recipient_contact The recipient's phone number.
     * @param string $message The message content.
     * @return bool True on success, false on failure.
     */
    public function sendSms(int $sender_id, int $recipient_id, string $recipient_contact, string $message): bool {
        // --- Real Implementation Would Go Here ---
        // Example with a hypothetical API:
        // $apiKey = 'YOUR_API_KEY';
        // $client = new SmsGatewayClient($apiKey);
        // try {
        //     $response = $client->send($to, $message, 'AgriConnect');
        //     return $response->isSuccessful();
        // } catch (Exception $e) {
        //     // Log the error
        //     error_log("SMS sending failed for number $to: " . $e->getMessage());
        //     return false;
        // }

        // --- Save to our database ---
        $messageModel = new Message();
        $dbSuccess = $messageModel->create($sender_id, $recipient_id, $message);

        if (!$dbSuccess) {
            error_log("Failed to save SMS to database. Sender: $sender_id, Recipient: $recipient_id");
            // We might still want to try sending the SMS even if DB logging fails
        }

        // --- Simulation for this project ---
        // We will log the SMS to a file in the project root.
        if (empty($recipient_contact)) {
            error_log("SMS sending failed: No recipient phone number provided.");
            return false;
        }

        $logMessage = sprintf(
            "[%s] SMS SENT TO: %s | MESSAGE: %s\n",
            date('Y-m-d H:i:s'),
            $recipient_contact,
            trim($message)
        );

        // Log to a file named 'sms_log.txt' in the same directory
        $logFilePath = __DIR__ . '/sms_log.txt';
        
        // Use file_put_contents with FILE_APPEND to add to the log
        // We use @ to suppress warnings if permission is denied, to avoid breaking the JSON response
        if (@file_put_contents($logFilePath, $logMessage, FILE_APPEND | LOCK_EX) === false) {
            // Log an error if we can't write to the file
            error_log("Could not write to SMS log file: " . $logFilePath);
            return false;
        }

        return true;
    }
}