<?php

namespace tobimori\Join\Models;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use tobimori\Join\Join;

class JobPage extends Page
{
	public static function getSeniorityLevels(): array
	{
		try {
			$lang = App::instance()->user()?->language();
			if (!$lang) {
				return [];
			}
			ray(
				Join::userAgent()
			);

			return array_map(
				fn($item) => ['text' => $item['name'], 'value' => $item['id']],
				Join::get("/seniorityLevels", ['language' => $lang])->json()
			);
		} catch (\Exception $e) {
			ray($e);
			return [];
		}

		ray(
			Join::userAgent()
		);

		return array_map(
			fn($item) => ['text' => $item['name'], 'value' => $item['id']],
			Join::get("/seniorityLevels", ['language' => $lang])->json()
		);
	}
}
