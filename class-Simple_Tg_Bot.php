<?php

/**
 *
 * This class allows you to interact with Telegram Bot API
 */
class Simple_Tg_Bot {
    private string $token;
    private string $apiUrl;

    public function __construct($token) {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot" . $this->token . "/";
    }

    /**
     * Sending a text message
     *
     * @param $chatId
     * @param $message
     * @return mixed
     */
    public function sendMessage($chatId, $message): mixed {
        $url = $this->apiUrl . "sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message
        ];

        return $this->sendRequest($url, $data);
    }

    /**
     * Sending a photo
     *
     * @param $chatId
     * @param $photoPath
     * @param $caption
     * @return mixed
     */
    public function sendPhoto($chatId, $photoPath, $caption = null): mixed {
        $url = $this->apiUrl . "sendPhoto";
        $data = [
            'chat_id' => $chatId,
            'photo' => new CURLFile(realpath($photoPath)),
            'caption' => $caption
        ];

        return $this->sendRequest($url, $data);
    }

    /**
     * Sending a document (file)
     *
     * @param $chatId
     * @param $documentPath
     * @param $caption
     * @return mixed
     */
    public function sendDocument($chatId, $documentPath, $caption = null): mixed {
        $url = $this->apiUrl . "sendDocument";
        $data = [
            'chat_id' => $chatId,
            'document' => new CURLFile(realpath($documentPath)),
            'caption' => $caption
        ];

        return $this->sendRequest($url, $data);
    }

    /**
     * Setting the webhook
     * @param $url
     * @return mixed
     */
    public function setWebhook($url): mixed {
        $webhookUrl = $this->apiUrl . "setWebhook";
        $data = ['url' => $url];

        return $this->sendRequest($webhookUrl, $data);
    }

    /**
     * Deleting the webhook
     *
     * @return mixed
     */
    public function deleteWebhook(): mixed {
        $url = $this->apiUrl . "deleteWebhook";

        return $this->sendRequest($url);
    }

    /**
     * Getting updates
     *
     * @return mixed
     */
    public function getUpdates(): mixed {
        $url = $this->apiUrl . "getUpdates";

        return $this->sendRequest($url);
    }

    /**
     * Returns object of the current request
     *
     * @return object
     */
    public function getRequest(): object {
        $input = file_get_contents('php://input');
        return json_decode($input);
    }

    /**
     * Helper method for sending requests
     *
     * @param $url
     * @param array $data
     * @return mixed
     */
    private function sendRequest($url, array $data = []): mixed {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}