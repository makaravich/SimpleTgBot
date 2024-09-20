# Simple_Tg_Bot PHP Class

A simple PHP class for interacting with the [Telegram Bot API](https://core.telegram.org/bots/api). This class makes it easy to send and receive messages, photos, and documents, as well as manage webhooks.

## Features

- Send text messages to users.
- Send photos with optional captions.
- Send documents (files) with optional captions.
- Set or delete webhooks.
- Retrieve updates (messages) from users.

## Installation

1. Clone or download this repository.
2. Ensure that you have PHP 5.5+ installed with the cURL extension enabled.

## Usage

To start using the `TelegramBot` class, you need to obtain a bot token from [BotFather](https://t.me/botfather) on Telegram. Replace `YOUR_BOT_TOKEN` in the examples below with your actual token.

### Example Code

```php
<?php

require_once 'class-Simple_Tg_Bot.php';

// Initialize the bot with your bot token
$bot = new Simple_Tg_Bot('YOUR_BOT_TOKEN');

// Send a message to a user
$chat_id = 'CHAT_ID';
$bot->send_message($chat_id, "Hello, World!");

// Send a photo to a user
$photo_path = '/path/to/photo.jpg';
$bot->send_photo($photo_path, 'Photo caption');

// Send a document to a user
$document_path = '/path/to/file.pdf';
$bot->send_document($document_path, 'Document caption');

// Set a webhook
$webhook_url = 'https://yourdomain.com/path/to/webhook';
$bot->set_webhook($webhook_url);

// Delete the webhook
$bot->delete_webhook();

// Get updates (polling mode)
$updates = $bot->get_updates();
if (!empty($updates['result'])) {
    foreach ($updates['result'] as $update) {
        $chat_id = $update['message']['chat']['id'];
        $message = $update['message']['text'];

        // Respond to the user's message
        $bot->send_message("You wrote: $message");
    }
}
```
## Methods
- `send_message($message, $chat_id = '')`: Sends a text message to the specified chat.
- `send_photo($photo_path, $caption = null, $chat_id = '')`: Sends a photo from the specified path with an optional caption.
- `send_document($document_path, $caption = null, $chat_id = '')`: Sends a document from the specified path with an optional caption.
- `set_webhook($url)`: Sets the webhook URL for your bot.
- `delete_webhook()`: Deletes the currently set webhook, reverting to long polling mode.
- `get_updates()`: Retrieves messages and updates in long polling mode.


## Requirements

* PHP 5.5+ with the cURL extension enabled.
* Telegram Bot API token from BotFather.

## License
This project is licensed under the MIT License. See the LICENSE file for details.

Feel free to contribute or suggest improvements to the class. Pull requests are welcome!

