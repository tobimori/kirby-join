<?php

namespace tobimori\Join;

use Kirby\Data\Json;
use Kirby\Cms\App;
use Kirby\Cache\Cache;
use Kirby\Exception\Exception;
use Kirby\Http\Remote;

final class Join
{
	protected const string BASE_API_URL = 'https://api.join.com/v2/';

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
	public static function request(string $method = "GET", string $endpoint = '/', array $data = [], array $query = [])
	{
		$apiKey = self::option('apiKey');
		if (!$apiKey) {
			throw new Exception('[JOIN for Kirby] API key not set, please set tobimori.join.apiKey');
		}

		$url = self::BASE_API_URL . trim($endpoint, '/');
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

	/**
	 * Get the cache instance
	 */
	public static function cache(): Cache
	{
		return App::instance()->cache('tobimori.join');
	}

	/**
	 * Get a value from cache
	 */
	public static function cacheGet(string $key, mixed $default = null): mixed
	{
		return self::cache()->get($key, $default);
	}

	/**
	 * Set a value in cache
	 */
	public static function cacheSet(string $key, mixed $value, int|null $minutes = null): bool
	{
		$minutes ??= self::option('ttl');
		return self::cache()->set($key, $value, $minutes);
	}

	/**
	 * Remove a value from cache
	 */
	public static function cacheRemove(string $key): bool
	{
		return self::cache()->remove($key);
	}

	/**
	 * Clear all cache entries
	 */
	public static function cacheFlush(): bool
	{
		return self::cache()->flush();
	}

	/**
	 * Flush project caches after JOIN data changes
	 *
	 * @param array<string, mixed> $context
	 */
	public static function flushCache(array $context = []): mixed
	{
		$callback = App::instance()->option('tobimori.join.flushCache');

		if (is_callable($callback)) {
			return $callback($context);
		}

		return $callback;
	}

	/**
	 * Check if a cache key exists
	 */
	public static function cacheExists(string $key): bool
	{
		return self::cache()->exists($key);
	}

	/**
	 * Get or set a cache value with a callback
	 */
	public static function cacheRemember(string $key, callable $callback, int|null $minutes = null): mixed
	{
		if (self::cacheExists($key)) {
			return self::cacheGet($key);
		}

		$value = $callback();
		self::cacheSet($key, $value, $minutes);
		return $value;
	}

	/**
	 * Fetch all job IDs and cache individual job data
	 *
	 * Returns null if the request fails. Errors and empty lists are not cached,
	 * so a failed refresh keeps the cached list
	 */
	public static function fetchAndCacheAllJobs(bool $forceFresh = false): array|null
	{
		if (!$forceFresh && $jobIds = self::cacheGet('job-ids')) {
			return $jobIds;
		}

		try {
			$response = self::get('/jobs', ['content' => 'true']);
		} catch (Exception $e) {
			// configuration errors like a missing API key must not look like a failed request
			throw $e;
		} catch (\Exception $e) {
			return null;
		}

		$jobs = $response->code() === 200 ? $response->json() : null;
		if (!is_array($jobs)) {
			return null;
		}

		$jobIds = [];
		foreach ($jobs as $job) {
			$jobIds[] = $job['id'];
			self::cacheSet("job.{$job['id']}", $job);
		}

		if ($jobIds === []) {
			self::cacheRemove('job-ids');
		} else {
			self::cacheSet('job-ids', $jobIds);
		}

		return $jobIds;
	}
}
