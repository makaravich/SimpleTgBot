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

require_once 'TelegramBot.php';

// Initialize the bot with your bot token
$bot = new TelegramBot('YOUR_BOT_TOKEN');

// Send a message to a user
$chatId = 'CHAT_ID';
$bot->sendMessage($chatId, "Hello, World!");

// Send a photo to a user
$photoPath = '/path/to/photo.jpg';
$bot->sendPhoto($chatId, $photoPath, 'Photo caption');

// Send a document to a user
$documentPath = '/path/to/file.pdf';
$bot->sendDocument($chatId, $documentPath, 'Document caption');

// Set a webhook
$webhookUrl = 'https://yourdomain.com/path/to/webhook';
$bot->setWebhook($webhookUrl);

// Delete the webhook
$bot->deleteWebhook();

// Get updates (polling mode)
$updates = $bot->getUpdates();
if (!empty($updates['result'])) {
    foreach ($updates['result'] as $update) {
        $chatId = $update['message']['chat']['id'];
        $message = $update['message']['text'];

        // Respond to the user's message
        $bot->sendMessage($chatId, "You wrote: $message");
    }
}
```
## Methods
`sendMessage($chatId, $message)`: Sends a text message to the specified chat.

`sendPhoto($chatId, $photoPath, $caption = null)`: Sends a photo from the specified path with an optional caption.

`sendDocument($chatId, $documentPath, $caption = null)`: Sends a document from the specified path with an optional caption.

`setWebhook($url)`: Sets the webhook URL for your bot.

`deleteWebhook()`: Deletes the currently set webhook, reverting to long polling mode.

`getUpdates()`: Retrieves messages and updates in long polling mode.

## Requirements

* PHP 5.5+ with the cURL extension enabled.
* Telegram Bot API token from BotFather.

## License
This project is licensed under the MIT License. See the LICENSE file for details.

Feel free to contribute or suggest improvements to the class. Pull requests are welcome!

