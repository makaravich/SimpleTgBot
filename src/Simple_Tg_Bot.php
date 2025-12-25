<?php

/**
 * This class allows you to interact with Telegram Bot API
 *
 * V. 0.1.18
 */
class Simple_Tg_Bot
{
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
    public object $request_respond;

    /**
     * @var string Chat ID
     */
    public string $chat_id = '';

    /**
     * @var object
     */
    private object $last_request_response;

    /**
     * @var string Text from last requested message
     */
    protected string $last_received_text = '';

    /**
     * @var string Message to send to users as a help
     */
    private string $help_message = 'Default help message';

    /**
     * @var array
     */
    protected array $map = [];

    /**
     * @var bool
     */
    private bool $auto_exec = true;


    public function __construct($token, $do_get_request = true, $bot_map = [])
    {
        $this->token = $token;
        $this->api_url = "https://api.telegram.org/bot" . $this->token . "/";

        $this->set_map($bot_map);

        if ($this->map['auto_exec'] === false) {
            $this->auto_exec = false;
        }

        if (isset($this->map['help_message'])) {
            $this->help_message = $this->map['help_message'];
        }

        if ($do_get_request && !isset($this->map['request_respond'])) {
            $this->get_request();
        } elseif (isset($this->map['request_respond'])) {
            //error_log('{DEBUG BOT} Run set_existing_request_respond');
            $this->set_existing_request_respond($this->map['request_respond']);
        }
    }

    private function set_map($map): void
    {
        $this->map = $map;
    }

    public function get_map(): array
    {
        return $this->map;
    }

    public function get_last_received_text(): string
    {
        return $this->last_received_text;
    }

    private function set_last_received_text($text): void
    {
        if (!empty ($text) && !str_starts_with($text, "/")) {
            $this->last_received_text = $text;
        } else {
            $this->last_received_text = '';
            if (!empty ($text) && $this->auto_exec) {
                $this->run_command($text);
            } elseif (!$this->auto_exec) {
                $this->last_received_text = $text; // Save the text of command if it was not run
            }
        }
    }

    public function run_command($command): void
    {
        $command = ltrim($command, '/');
        if (strlen($command) > 100) {
            $this->send_message(__('Too long command'));
        } else {
            if (method_exists($this, 'command_' . $command)) {
                call_user_func([$this, 'command_' . $command]);
            } else {
                $this->send_message('Unknown command: ' . $command);
            }
        }
    }

    /**
     * Processing of the bot command /start
     * @return bool
     */
    public function command_start(): bool
    {
        $this->send_message('Hi!');
        $this->send_message($this->help_message);
        $this->send_message('Use command /help to get this tip again');

        return true;
    }

    /**
     * Processing of the bot command /help
     * @return mixed
     */
    public function command_help(): mixed
    {
        return $this->send_message($this->help_message);
    }

    /**
     * Sending a text message
     *
     * @param $message
     *
     * @param string $chat_id
     * @param null $reply_markup
     *
     * @return mixed
     */
    public function send_message($message, string $chat_id = '', $reply_markup = null): mixed
    {
        if ($chat_id === '') {
            $chat_id = $this->chat_id;
        }

        $url = $this->api_url . "sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }

        return $this->send_request($url, $data);
    }

    /**
     * Sending a Markdown message
     *
     * @param $message
     *
     * @param string $chat_id
     * @param null $reply_markup
     *
     * @return mixed
     */
    public function send_markdown_message($message, string $chat_id = '', $reply_markup = null): mixed
    {
        if ($chat_id === '') {
            $chat_id = $this->chat_id;
        }

        $url = $this->api_url . "sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $this->escape_markdown_v2($message),
            'parse_mode' => 'MarkdownV2'
        ];

        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }

        //error_log('{DEBUG MARKDOWN} ' . print_r($data, true));

        return $this->send_request($url, $data);
    }

    /**
     * Escapes special characters for Telegram MarkdownV2 format
     * Handles paired symbols (_*~`), brackets, links and other special characters
     * according to MarkdownV2 specification
     *
     * @param string $text Text to be escaped
     *
     * @return string Escaped text ready for MarkdownV2 formatting
     */
    private function escape_markdown_v2(string $text): string
    {
        $result = '';
        $length = mb_strlen($text);

        // States for paired symbols
        $states = [
            '_' => false,
            '*' => false,
            '~' => false,
            '`' => false
        ];

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            $remaining = mb_substr($text, $i + 1);

            switch ($char) {
                case '_':
                case '*':
                case '~':
                case '`':
                    // If symbol is already opened, this is a closing symbol
                    if ($states[$char]) {
                        $result .= $char;
                        $states[$char] = false;
                    } else {
                        // Check if there's a closing symbol in the remaining text
                        if (mb_strpos($remaining, $char) !== false) {
                            $result .= $char;
                            $states[$char] = true;
                        } else {
                            // No pair - escape it
                            $result .= '\\' . $char;
                        }
                    }
                    break;

                case '[':
                    // Escape if there's no corresponding closing bracket ]
                    if (mb_strpos($remaining, ']') === false) {
                        $result .= '\\' . $char;
                    } else {
                        $result .= $char;
                    }
                    break;

                case ']':
                    // Escape if there's no corresponding opening bracket [
                    $before = mb_substr($text, 0, $i);
                    if (mb_strrpos($before, '[') === false) {
                        $result .= '\\' . $char;
                    } else {
                        $result .= $char;
                    }
                    break;

                case '(':
                    // Check if this is part of a link [text](url)
                    $before = mb_substr($text, 0, $i);
                    $is_link = false;

                    // Look for the last closing square bracket before current position
                    $last_bracket_pos = mb_strrpos($before, ']');
                    if ($last_bracket_pos !== false) {
                        // Check that there are no other characters between ] and ( (except spaces)
                        $between = mb_substr($before, $last_bracket_pos + 1);
                        if (preg_match('/^\s*$/', $between)) {
                            // Check that there's a corresponding opening square bracket
                            $text_before_bracket = mb_substr($before, 0, $last_bracket_pos);
                            if (mb_strrpos($text_before_bracket, '[') !== false) {
                                $is_link = true;
                            }
                        }
                    }

                    if ($is_link) {
                        $result .= $char;
                    } else {
                        $result .= '\\' . $char;
                    }
                    break;

                case ')':
                    // Always escape except when it's part of a link
                    $before = mb_substr($text, 0, $i);
                    $is_link_end = false;

                    // Look for the last opening parenthesis
                    $last_paren_pos = mb_strrpos($before, '(');
                    if ($last_paren_pos !== false) {
                        // Check that there was a closing square bracket before (
                        $text_before_paren = mb_substr($before, 0, $last_paren_pos);
                        $last_bracket_pos = mb_strrpos($text_before_paren, ']');
                        if ($last_bracket_pos !== false) {
                            $between = mb_substr($text_before_paren, $last_bracket_pos + 1);
                            if (preg_match('/^\s*$/', $between)) {
                                // Check for opening square bracket
                                $text_before_bracket = mb_substr($text_before_paren, 0, $last_bracket_pos);
                                if (mb_strrpos($text_before_bracket, '[') !== false) {
                                    $is_link_end = true;
                                }
                            }
                        }
                    }

                    if ($is_link_end) {
                        $result .= $char;
                    } else {
                        $result .= '\\' . $char;
                    }
                    break;

                case '>':
                    // Don't escape if it's at the beginning of a line (quote)
                    $before = mb_substr($text, 0, $i);
                    $is_line_start = ($i === 0) || (mb_substr($before, -1) === "\n");

                    if ($is_line_start) {
                        $result .= $char;
                    } else {
                        $result .= '\\' . $char;
                    }
                    break;

                case '#':
                case '+':
                case '-':
                case '=':
                case '|':
                case '{':
                case '}':
                case '.':
                case '!':
                    // These symbols are always escaped
                    $result .= '\\' . $char;
                    break;

                default:
                    $result .= $char;
                    break;
            }
        }

        return $result;
    }

    /**
     * Sending a photo
     *
     * @param string $chat_id
     * @param $photo_path
     * @param $caption
     *
     * @return mixed
     */
    public function send_photo($photo_path, $caption = null, string $chat_id = ''): mixed
    {
        if ($chat_id === '') {
            $chat_id = $this->chat_id;
        }

        $url = $this->api_url . "sendPhoto";
        $data = [
            'chat_id' => $chat_id,
            'photo' => new CURLFile(realpath($photo_path)),
            'caption' => $caption
        ];

        //error_log('{DEBUG IMAGES} ' . print_r($data, true));

        return $this->send_request($url, $data);
    }

    /**
     * Sending a document (file)
     *
     * @param string $chat_id
     * @param string $document_path
     * @param string|null $caption
     *
     * @return mixed
     */
    public function send_document(string $document_path, string $caption = null, string $chat_id = ''): mixed
    {
        if ($chat_id === '') {
            $chat_id = $this->chat_id;
        }

        $url = $this->api_url . "sendDocument";
        $data = [
            'chat_id' => $chat_id,
            'document' => new CURLFile($document_path),
            'caption' => $caption
        ];

        return $this->send_request($url, $data);
    }

    /**
     * Setting the webhook
     *
     * @param $url
     *
     * @return mixed
     */
    public function set_webhook($url): mixed
    {
        $webhook_url = $this->api_url . "setWebhook";
        $data = ['url' => $url];

        return $this->send_request($webhook_url, $data);
    }

    /**
     * Deleting the webhook
     *
     * @return mixed
     */
    public function delete_webhook(): mixed
    {
        $url = $this->api_url . "deleteWebhook";

        return $this->send_request($url);
    }

    /**
     * Getting updates
     *
     * @return mixed
     */
    public function get_updates(): mixed
    {
        $url = $this->api_url . "getUpdates";

        return $this->send_request($url);
    }

    /**
     * Returns object of the current request
     *
     * @return object|false|string
     */
    public function get_request(): object|false|string
    {
        $input = file_get_contents('php://input');

        if (empty($input)) {
            return false;
        }

        $this->request_respond = json_decode($input);

        $this->update_chat_id();

        $this->set_last_received_text($this->request_respond->message->text ?? $this->request_respond->message->caption ?? '');

        return $this->request_respond;
    }

    /**
     * Set request respond from existing data
     * Use to re-create the bot without get data from Telegram
     *
     * @param $request_respond
     *
     * @return void
     */
    private function set_existing_request_respond($request_respond): void
    {
        $this->request_respond = $request_respond;

        $this->update_chat_id();

        $this->set_last_received_text($this->request_respond->message->text ?? $this->request_respond->message->caption ?? '');
    }

    /**
     * Update Chat_id based on request_respond
     *
     * @return void
     */
    private function update_chat_id(): void
    {
        $chat_id = $this->request_respond->message->chat->id;

        if (!$chat_id) {
            $chat_id = $this->request_respond->callback_query->from->id;
        }

        if (!$chat_id) {
            return;
        } else {
            $this->chat_id = $chat_id;
        }
    }

    /**
     * Helper method for sending requests
     *
     * @param $url
     * @param array $data
     *
     * @return mixed
     */
    private function send_request($url, array $data = []): mixed
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        //error_log('{DEBUG CURL} ' . $response);

        curl_close($ch);

        $this->last_request_response = json_decode($response);

        //error_log('{DEBUG RESPONSE} ' . print_r($this->last_request_response, true));

        if (!$this->last_request_response->ok) {
            error_log('[ERROR WITH THE REQUEST]. Dump: ' . print_r($this->last_request_response, true));
            $this->send_message('There was an error with the request. Please try again later.');
        }

        return $this->last_request_response;
    }

    /**
     * Update text and (or) markup (buttons) in the existing message
     *
     * @param $message_id
     * @param string $text
     * @param null $reply_markup
     * @param string $parse_mode
     *
     * @return void
     */
    public function edit_message($message_id, string $text = '', $reply_markup = null, string $parse_mode = 'HTML'): void
    {
        $url = $this->api_url . "editMessageText";

        $request = [
            'chat_id' => $this->chat_id,
            'message_id' => $message_id,
            'parse_mode' => $parse_mode,
        ];

        if ($reply_markup) {
            $request['reply_markup'] = json_encode($reply_markup);
        }

        if ($text) {
            if ($parse_mode == 'MarkdownV2') {
                $text = $this->escape_markdown_v2($text);
            }
            $request['text'] = $text;
        }

        $this->send_request($url, $request);
    }


    /**
     * Deletes a message from Telegram chat
     *
     * @param int $message_id ID of the message to be deleted
     *
     * @return void
     */
    public function delete_message(int $message_id): void
    {
        $url = $this->api_url . "deleteMessage";
        $request = [
            'chat_id' => $this->chat_id,
            'message_id' => $message_id,
        ];
        $this->send_request($url, $request);
    }


    /**
     * Update markup (buttons) in the existing message
     *
     * @param $message_id
     * @param null $reply_markup
     *
     * @return void
     */
    public function edit_message_markup($message_id, $reply_markup): void
    {

        $url = $this->api_url . "editMessageReplyMarkup";

        $request = [
            'chat_id' => $this->chat_id,
            'message_id' => $message_id,
            'reply_markup' => json_encode($reply_markup)
        ];

        $this->send_request($url, $request);
    }

    /**
     * Returns last request response
     *
     * @return object
     */
    public function get_last_request_response(): object
    {
        return $this->last_request_response;
    }

    /**
     * Gets the URL of the maximum resolution image from Telegram Bot API response
     *
     * @param object $message Message from Telegram Bot API
     *
     * @return string|null Image URL or null if photo is not found
     */
    public function get_document_url(object $message): ?string
    {
        $message = $message->message ?? $message;

        // Check if the message contains photo
        if (is_array($message->photo) && !empty($message->photo)) {
            return $this->get_photo_url($message);
        }

        $fileId = '';

        // Check different file types and extract file_id

        // Documents
        if (isset($message->document)) {
            $fileId = $message->document->file_id;
        } // Videos
        elseif (isset($message->video)) {
            $fileId = $message->video->file_id;
        } // Audio files
        elseif (isset($message->audio)) {
            $fileId = $message->audio->file_id;
        } // Voice messages
        elseif (isset($message->voice)) {
            $fileId = $message->voice->file_id;
        } // Video notes (circle videos)
        elseif (isset($message->video_note)) {
            $fileId = $message->video_note->file_id;
        } // Stickers
        elseif (isset($message->sticker)) {
            $fileId = $message->sticker->file_id;
        }

        //error_log('[Get File debug]' . print_r($message, true));
        //error_log('[Get File debug file ID]' . $fileId);

        // If no file found
        if (!$fileId) {
            return '';
        }

        $file_info = $this->get_file_info($fileId);

        // Check if request was successful
        if (!$file_info || !isset($file_info['file_path'])) {
            return '';
        }

        // Return downloadable URL
        return "https://api.telegram.org/file/bot{$this->token}/" . $file_info['file_path'];
    }


    /**
     * Gets the URL of the maximum resolution image from Telegram Bot API response
     *
     * @param object $message Message from Telegram Bot API
     *
     * @return string|null Image URL or null if photo is not found
     */
    public function get_photo_url(object $message): ?string
    {

        $message = $message->message ?? $message;

        // Check if the message contains photo
        if (!is_array($message->photo) || empty($message->photo)) {
            return null;
        }

        // Find photo with maximum size
        $max_photo = $this->get_max_resolution_photo($message->photo);

        if (!$max_photo || !isset($max_photo->file_id)) {
            return null;
        }

        // Get file information via getFile API
        $file_info = $this->get_file_info($max_photo->file_id, $this->token);

        if (!$file_info || !isset($file_info['file_path'])) {
            return null;
        }

        // Form URL for file download
        return "https://api.telegram.org/file/bot{$this->token}/{$file_info['file_path']}";
    }

    /**
     * Finds photo with maximum resolution from PhotoSize array
     *
     * @param array $photos Array of PhotoSize objects
     *
     * @return object|null PhotoSize object with maximum resolution
     */
    function get_max_resolution_photo(array $photos): ?object
    {
        if (empty($photos)) {
            return null;
        }

        $max_photo = null;
        $max_size = 0;

        foreach ($photos as $photo) {
            // Calc image size (width * height)
            $current_size = ($photo->width ?? 0) * ($photo->height ?? 0);

            if ($current_size > $max_size) {
                $max_size = $current_size;
                $max_photo = $photo;
            }
        }

        return $max_photo;
    }

    /**
     * Gets file information via Telegram Bot API
     *
     * @param string $fileId File ID
     *
     * @return array|null File information or null in case of error
     */
    function get_file_info(string $fileId): ?array
    {
        $url = "https://api.telegram.org/bot{$this->token}/getFile?file_id=" . urlencode($fileId);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // request timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // verify SSL certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);

            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !$data['ok'] || !isset($data['result'])) {
            return null;
        }

        return $data['result'];
    }

}