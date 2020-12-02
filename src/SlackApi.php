<?php
namespace cjrasmussen\SlackApi;

use RuntimeException;

/**
 * Class Slack
 */
class SlackApi
{
	private $token;
	private $api_url = 'https://slack.com/api/';
	private $webhook_url;

	/**
	 * Slack constructor.
	 *
	 * @param string $mixed - Slack token or webhook URL
	 * @param string|null $team - Slack team URL part
	 * @param string|null $webhook_url - Slack webhook URL if not provided as $mixed
	 */
	public function __construct($mixed, $team = null, $webhook_url = null)
	{
		if ((false !== strpos($mixed, 'hooks.slack')) AND ($webhook_url === null)) {
			$this->token = null;
			$this->webhook_url = $mixed;
		} else {
			$this->token = $mixed;
			$this->webhook_url = $webhook_url;
		}

		if ($team) {
			$this->api_url = 'https://' . $team . '.slack.com/api/';
		}
	}

	/**
	 * Make a request to the Slack API
	 *
	 * @param string $type
	 * @param string $method
	 * @param array|null $args
	 * @return mixed
	 */
	public function request($type, $method, ?array $args = [])
	{
		$url = $this->api_url . $method;

		if (($type === 'GET') AND (count($args))) {
			$url .= '?' . http_build_query($args);
		}

		$header = ['Authorization: Bearer ' . $this->token];

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPHEADER, $header);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

		switch ($type) {
			case 'POST':
				curl_setopt($c, CURLOPT_POST, 1);
				curl_setopt($c, CURLOPT_POSTFIELDS, $args);
				break;
			case 'GET':
				curl_setopt($c, CURLOPT_HTTPGET, 1);
				break;
			default:
				curl_setopt($c, CURLOPT_CUSTOMREQUEST, $type);
		}

		$response = curl_exec($c);
		curl_close($c);

		$data = json_decode($response);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException('API response was not valid JSON');
		}

		if ($data->error) {
			throw new RuntimeException($data->error);
		}

		return $data;
	}

	/**
	 * Send a message to Slack via a webhook
	 *
	 * @param string|array $data
	 * @return bool|string
	 */
	public function sendMessage($data)
	{
		$msg = [];
		if (is_string($data)) {
			$msg['text'] = $data;
		} else {
			$msg = $data;
		}

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $this->webhook_url);
		curl_setopt($c, CURLOPT_POST, 1);
		curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($msg));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_HEADER, 0);
		curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
		$success = curl_exec($c);
		curl_close($c);

		return $success;
	}
}
