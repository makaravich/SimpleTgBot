<?php

/**
 *
 * This class allows you to interact with Telegram Bot API
 */
class Simple_Tg_Bot {
    /**
     * @var string Your token ID
     */
    private string $token;

    /**
     * @var string Telegram API URL
     */
    private string $api_url;

    /**
     * @var string|mixed|object Respond of API request
     */
    private object $request_respond;

    /**
     * @var string Chat ID
     */
    public string $chat_id = '';

    /**
     * @var string Text from last requested message
     */
    protected string $last_received_text = '';

    /**
     * @var string Message to send to users as a help
     */
    private string $help_message =
        "Want to turn a simple text conversation into a visual chat image? This bot does exactly that! Whether you need a mock-up of a conversation or want to share a creative dialogue, just forward your text, and it will create an image that mimics a chat interface. Your messages remain as-is, while your partner's lines should start with an asterisk (*) for clear distinction. 
Example: 
<pre>Hey, are you coming to the party tonight?
*Yeah
*I'll be there around 8 PM.
Great! See you then.
*See you!</pre>";

    public function __construct($token, $do_get_request = true) {
        $this->token = $token;
        $this->api_url = "https://api.telegram.org/bot" . $this->token . "/";

        if ($do_get_request) {
            $this->get_request();
        }
    }

    public function get_last_received_text(): string {
        return $this->last_received_text;
    }

    public function set_last_received_text($text): void {
        if (!str_starts_with($text, "/")) {
            $this->last_received_text = $text;
        } else {
            $this->last_received_text = '';
            $this->run_command($text);
        }
    }

    private function run_command($command) {
        $command = ltrim($command, '/');
        if (strlen($command) > 100) {
            return false;
        } else {
            if (method_exists($this, 'command_' . $command)) {
                return call_user_func([$this, 'command_' . $command]);
            } else {
                $this->send_message('Unknown command: ' . $command);
                return false;
            }
        }
    }

    /**
     * Processing of the bot command /start
     * @return bool
     */
    private function command_start(): bool {
        $this->send_message('Hi!');
        $this->send_message($this->help_message);
        $this->send_message('Use command /help to get this tip again');
        return true;
    }

    /**
     * Processing of the bot command /help
     * @return mixed
     */
    private function command_help(): mixed {
        return $this->send_message($this->help_message);
    }

    /**
     * Sending a text message
     *
     * @param string $chat_id
     * @param $message
     * @return mixed
     */
    public function send_message($message, string $chat_id = ''): mixed {
        if ($chat_id === '') {
            $chat_id = $this->chat_id;
        }

        $url = $this->api_url . "sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        return $this->send_request($url, $data);
    }

    /**
     * Sending a photo
     *
     * @param string $chat_id
     * @param $photo_path
     * @param $caption
     * @return mixed
     */
    public function send_photo($photo_path, $caption = null, string $chat_id = ''): mixed {
        if ($chat_id === '') {
            $chat_id = $this->chat_id;
        }

        $url = $this->api_url . "sendPhoto";
        $data = [
            'chat_id' => $chat_id,
            'photo' => new CURLFile(realpath($photo_path)),
            'caption' => $caption
        ];

        return $this->send_request($url, $data);
    }

    /**
     * Sending a document (file)
     *
     * @param string $chat_id
     * @param string $document_path
     * @param string|null $caption
     * @return mixed
     */
    public function send_document(string $document_path, string $caption = null, string $chat_id = ''): mixed {
        if ($chat_id === '') {
            $chat_id = $this->chat_id;
        }

        $url = $this->api_url . "sendDocument";
        $data = [
            'chat_id' => $chat_id,
            'document' => new CURLFile(realpath($document_path)),
            'caption' => $caption
        ];

        return $this->send_request($url, $data);
    }

    /**
     * Setting the webhook
     * @param $url
     * @return mixed
     */
    public function set_webhook($url): mixed {
        $webhook_url = $this->api_url . "setWebhook";
        $data = ['url' => $url];

        return $this->send_request($webhook_url, $data);
    }

    /**
     * Deleting the webhook
     *
     * @return mixed
     */
    public function delete_webhook(): mixed {
        $url = $this->api_url . "deleteWebhook";

        return $this->send_request($url);
    }

    /**
     * Getting updates
     *
     * @return mixed
     */
    public function get_updates(): mixed {
        $url = $this->api_url . "getUpdates";

        return $this->send_request($url);
    }

    /**
     * Returns object of the current request
     *
     * @return object
     */
    public function get_request(): object {
        $input = file_get_contents('php://input');

        $this->request_respond = json_decode($input);

        $this->chat_id = $this->request_respond->message->chat->id;
        $this->set_last_received_text($this->request_respond->message->text);

        return $this->request_respond;
    }

    /**
     * Helper method for sending requests
     *
     * @param $url
     * @param array $data
     * @return mixed
     */
    private function send_request($url, array $data = []): mixed {
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