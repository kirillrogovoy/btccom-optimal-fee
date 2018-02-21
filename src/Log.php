<?php
final class Log {
    private $emailAddress;
    private $logFilePath;

    public function __construct($emailAddress, $logFilePath) {
        $this->emailAddress = $emailAddress;
        $this->logFilePath = $logFilePath;
    }

    public function error($message) {
        $this->writeToFile($message);
        $this->sendEmail($message);
    }

    private function writeToFile($message) {
        $date = date('Y-m-d H:i:s');
        file_put_contents(
            $this->logFilePath,
            "[$date] $message\n\n",
            FILE_APPEND
        );
    }

    private function sendEmail($message) {
        $mailSent = mail(
            $this->emailAddress,
            'BTC Parser Error',
            $message
        );

        if (!$mailSent) {
            $this->writeToFile("Couldn't send Email to '{$this->emailAddress}'");
        }
    }
}
