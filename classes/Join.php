<?php

namespace tobimori\Join;

use Kirby\Data\Json;
use Kirby\Cms\App;
use Kirby\Exception\Exception;
use Kirby\Http\Remote;

final class Join
{
	protected static const string BASE_API_URL = 'https://api.join.com/v2/';

	/**
	 * Returns the user agent string for the plugin
	 */
	public static function userAgent(): string
	{
		return "Kirby JOIN/" . App::plugin('tobimori/join')->version() . " (+https://plugins.andkindness.com/join)";
	}

	/**
	 * Returns a plugin option
	 */
	public static function option(string $key, mixed $default = null): mixed
	{
		$option = App::instance()->option("tobimori.join.{$key}", $default);
		if (is_callable($option)) {
			$option = $option();
		}

		return $option;
	}

	/**
	 * Send a request to the JOIN API
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, string> $query
	 */
	public static function request(string $method = "GET", string $endpoint, array $data = [], array $query = [])
	{

		ray($endpoint);

		$apiKey = self::option('apiKey');
		if (!$apiKey) {
			throw new Exception('[JOIN for Kirby] API key not set, please set tobimori.join.apiKey');
		}


		$url = self::BASE_API_URL . trim($endpoint, '/');

		ray($url);

		// add query parameters to url
		if (!empty($query)) {
			$url .= '?' . http_build_query($query);
		}

		$options = [
			'method' => $method,
			'agent' => self::userAgent(),
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => self::option('apiKey')
			]
		];

		// only stringify and add data for non-GET requests
		if ($method !== 'GET' && !empty($data)) {
			$options['data'] = Json::encode($data);
		}

		return Remote::request($url, $options);
	}

	/**
	 * Send a GET request to the JOIN API
	 *
	 * @param array<string, string> $query
	 */
	public static function get(string $endpoint, array $query = [])
	{
		return self::request('GET', $endpoint, [], $query);
	}

	/**
	 * Send a POST request to the JOIN API
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, string> $query
	 */
	public static function post(string $endpoint, array $data = [], array $query = [])
	{
		return self::request('POST', $endpoint, $data, $query);
	}

	/**
	 * Send a PATCH request to the JOIN API
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, string> $query
	 */
	public static function patch(string $endpoint, array $data = [], array $query = [])
	{
		return self::request('PATCH', $endpoint, $data, $query);
	}

	/**
	 * Send a DELETE request to the JOIN API
	 *
	 * @param array<string, string> $query
	 */
	public static function delete(string $endpoint, array $query = [])
	{
		return self::request('DELETE', $endpoint, [], $query);
	}
}
