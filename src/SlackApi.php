<?php

namespace cjrasmussen\SlackApi;

use JsonException;
use RuntimeException;

/**
 * Class Slack
 */
class SlackApi
{
	private ?string $token = null;
	private ?string $api_url = 'https://slack.com/api/';
	private ?string $webhook_url = null;

	/**
	 * Slack constructor.
	 *
	 * @param string|null $mixed - Slack token or webhook URL
	 * @param string|null $team - Slack team URL part
	 * @param string|null $webhook_url - Slack webhook URL if not provided as $mixed
	 */
	public function __construct(?string $mixed = null, ?string $team = null, ?string $webhook_url = null)
	{
		if (($webhook_url === null) && (false !== strpos($mixed, 'hooks.slack'))) {
			$this->webhook_url = $mixed;
		} elseif ($mixed) {
			$this->token = $mixed;
			$this->webhook_url = $webhook_url;
		}

		if ($team) {
			$this->setTeam($team);
		}
	}

	/**
	 * Set the webhook URL for sending messages
	 *
	 * @param string $webhook_url
	 * @return self
	 */
	public function setWebhookUrl(string $webhook_url): self
	{
		$this->webhook_url = $webhook_url;
		return $this;
	}

	/**
	 * Set the team for executing API requests
	 *
	 * @param string $team
	 * @return self
	 */
	public function setTeam(string $team): self
	{
		$this->api_url = 'https://' . $team . '.slack.com/api/';
		return $this;
	}

	/**
	 * Set the token for executing API requests
	 *
	 * @param string $token
	 * @return self
	 */
	public function setToken(string $token): self
	{
		$this->token = $token;
		return $this;
	}

	/**
	 * Make a request to the Slack API
	 *
	 * @param string $type
	 * @param string $method
	 * @param array|null $args
	 * @return object
	 * @throws JsonException
	 */
	public function request(string $type, string $method, ?array $args = []): object
	{
		if (!$this->token) {
			$msg = 'Cannot execute Slack API request with no API token defined.';
			throw new RuntimeException($msg);
		}

		$url = $this->api_url . $method;

		if (($type === 'GET') && (count($args))) {
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

		$data = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
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
	 * @throws JsonException
	 */
	public function sendMessage($data)
	{
		if (!$this->webhook_url) {
			$msg = 'Cannot send message via webhook with no webhook defined.';
			throw new RuntimeException($msg);
		}

		$msg = [];
		if (is_string($data)) {
			$msg['text'] = $data;
		} else {
			$msg = $data;
		}

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $this->webhook_url);
		curl_setopt($c, CURLOPT_POST, 1);
		curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($msg, JSON_THROW_ON_ERROR));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_HEADER, 0);
		curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
		$success = curl_exec($c);
		curl_close($c);

		return $success;
	}
}
