<?php

namespace tobimori\Join;

use Kirby\Cms\App;

final class Join
{
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
}
