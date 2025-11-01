<?php

namespace tobimori\Join\Models;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use tobimori\Join\Join;

class JobPage extends Page
{
	public static function getSeniorityLevels(): array
	{
		$lang = App::instance()->user()?->language();
		if (!$lang) {
			return [];
		}

		return Join::cacheRemember('seniority-levels', fn() => array_map(
			fn($item) => ['text' => $item['name'], 'value' => $item['id']],
			Join::get("/seniorityLevels", ['language' => $lang])->json()
		));
	}

	public static function getEmploymentTypes(): array
	{
		$lang = App::instance()->user()?->language();
		if (!$lang) {
			return [];
		}

		return Join::cacheRemember('employment-types',  fn() => array_map(
			fn($item) => ['text' => $item['name'], 'value' => $item['id']],
			Join::get("/employmentTypes", ['language' => $lang])->json()
		));
	}

	public static function getCategories(): array
	{
		$lang = App::instance()->user()?->language();
		if (!$lang) {
			return [];
		}

		return Join::cacheRemember('categories', fn() => array_reduce(
			Join::get("/categories", ['language' => $lang])->json(),
			fn($options, $category) => array_merge(
				$options,
				array_map(
					fn($subCategory) => [
						'text' => $category['name'] === $subCategory['name']
							? $category['name']
							: $category['name'] . ' › ' . $subCategory['name'],
						'value' => $subCategory['id']
					],
					$category['subCategories'] ?? []
				)
			),
			[]
		));
	}
}
