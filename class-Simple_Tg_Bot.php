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
	public object $request_respond;

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
	private string $help_message = 'Default help message';

	/**
	 * @var array
	 */
	protected array $map = [];

	private bool $auto_exec = true;

	public function __construct( $token, $do_get_request = true, $bot_map = [] ) {
		$this->token   = $token;
		$this->api_url = "https://api.telegram.org/bot" . $this->token . "/";

		$this->map = $bot_map;

		if ( $this->map['auto_exec'] === false ) {
			$this->auto_exec = false;
		}

		if ( isset( $this->map['help_message'] ) ) {
			$this->help_message = $this->map['help_message'];
		}

		if ( $do_get_request && ! isset( $this->map['request_respond'] ) ) {
			$this->get_request();
		} elseif ( isset( $this->map['request_respond'] ) ) {
			error_log( '{DEBUG BOT} Run set_existing_request_respond' );
			$this->set_existing_request_respond( $this->map['request_respond'] );
		}
	}

	public function get_last_received_text(): string {
		return $this->last_received_text;
	}

	private function set_last_received_text( $text ): void {
		if ( ! empty ( $text ) && ! str_starts_with( $text, "/" ) ) {
			$this->last_received_text = $text;
		} else {
			$this->last_received_text = '';
			if ( ! empty ( $text ) && $this->auto_exec ) {
				$this->run_command( $text );
			} elseif ( ! $this->auto_exec ) {
				$this->last_received_text = $text; // Save the text of command if it was not run
			}
		}
	}

	public function run_command( $command ): void {
		$command = ltrim( $command, '/' );
		if ( strlen( $command ) > 100 ) {
			$this->send_message( __( 'Too long command' ) );
		} else {
			if ( method_exists( $this, 'command_' . $command ) ) {
				call_user_func( [ $this, 'command_' . $command ] );
			} else {
				$this->send_message( 'Unknown command: ' . $command );
			}
		}
	}

	/**
	 * Processing of the bot command /start
	 * @return bool
	 */
	public function command_start(): bool {
		$this->send_message( 'Hi!' );
		$this->send_message( $this->help_message );
		$this->send_message( 'Use command /help to get this tip again' );

		return true;
	}

	/**
	 * Processing of the bot command /help
	 * @return mixed
	 */
	public function command_help(): mixed {
		return $this->send_message( $this->help_message );
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
	public function send_message( $message, string $chat_id = '', $reply_markup = null ): mixed {
		if ( $chat_id === '' ) {
			$chat_id = $this->chat_id;
		}

		$url  = $this->api_url . "sendMessage";
		$data = [
			'chat_id'    => $chat_id,
			'text'       => $message,
			'parse_mode' => 'HTML'
		];

		if ( $reply_markup ) {
			$data['reply_markup'] = json_encode( $reply_markup );
		}

		return $this->send_request( $url, $data );
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
	public function send_photo( $photo_path, $caption = null, string $chat_id = '' ): mixed {
		if ( $chat_id === '' ) {
			$chat_id = $this->chat_id;
		}

		$url  = $this->api_url . "sendPhoto";
		$data = [
			'chat_id' => $chat_id,
			'photo'   => new CURLFile( realpath( $photo_path ) ),
			'caption' => $caption
		];

		return $this->send_request( $url, $data );
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
	public function send_document( string $document_path, string $caption = null, string $chat_id = '' ): mixed {
		if ( $chat_id === '' ) {
			$chat_id = $this->chat_id;
		}

		$url  = $this->api_url . "sendDocument";
		$data = [
			'chat_id'  => $chat_id,
			'document' => new CURLFile( realpath( $document_path ) ),
			'caption'  => $caption
		];

		return $this->send_request( $url, $data );
	}

	/**
	 * Setting the webhook
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	public function set_webhook( $url ): mixed {
		$webhook_url = $this->api_url . "setWebhook";
		$data        = [ 'url' => $url ];

		return $this->send_request( $webhook_url, $data );
	}

	/**
	 * Deleting the webhook
	 *
	 * @return mixed
	 */
	public function delete_webhook(): mixed {
		$url = $this->api_url . "deleteWebhook";

		return $this->send_request( $url );
	}

	/**
	 * Getting updates
	 *
	 * @return mixed
	 */
	public function get_updates(): mixed {
		$url = $this->api_url . "getUpdates";

		return $this->send_request( $url );
	}

	/**
	 * Returns object of the current request
	 *
	 * @return object|false|string
	 */
	public function get_request(): object|false|string {
		$input = file_get_contents( 'php://input' );

		if ( empty( $input ) ) {
			return false;
		}

		$this->request_respond = json_decode( $input );

		$this->update_chat_id();

		$this->set_last_received_text( $this->request_respond->message->text ?? '' );

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
	private function set_existing_request_respond( $request_respond ): void {
		$this->request_respond = $request_respond;

		$this->update_chat_id();

		$this->set_last_received_text( $this->request_respond->message->text ?? '' );
	}

	/**
	 * Update Chat_id based on request_respond
	 *
	 * @return void
	 */
	private function update_chat_id(): void {
		$chat_id = $this->request_respond->message->chat->id;

		if ( ! $chat_id ) {
			$chat_id = $this->request_respond->callback_query->from->id;
		}

		if ( ! $chat_id ) {
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
	private function send_request( $url, array $data = [] ): mixed {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		$response = curl_exec( $ch );
		curl_close( $ch );

		return json_decode( $response, true );
	}
}