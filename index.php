<?php

@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use Kirby\Data\Json;
use Kirby\Toolkit\A;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;

if (
	version_compare(App::version() ?? '0.0.0', '5.0.0', '<=') === true ||
	version_compare(App::version() ?? '0.0.0', '6.0.0', '>') === true
) {
	throw new Exception('JOIN for Kirby CMS requires Kirby 5');
}

App::plugin(
	name: 'tobimori/join',
	extends: [
		'options' => [
			'cache' => true,
			'template' => 'join-job',
			'ttl' => 3600,
			'apiKey' => null,
		],
		'permissions' => [],
		'blueprints' => [
			'pages/join-job' => __DIR__ . '/blueprints/job.yml',
			'join/pages/job' => __DIR__ . '/blueprints/job.yml',
			'join/fields/writer' => __DIR__ . '/blueprints/fields/writer.yml',
			'join/fields/office' => __DIR__ . '/blueprints/fields/office.yml',
			'join/fields/country' => __DIR__ . '/blueprints/fields/country.yml',
			'join/fields/seniority-level' =>  __DIR__ . '/blueprints/fields/seniority-level.yml',
			'join/fields/employment-type' => __DIR__ . '/blueprints/fields/employment-type.yml',
			'join/fields/salary' => __DIR__ . '/blueprints/fields/salary.yml',
			'join/fields/category' => __DIR__ . '/blueprints/fields/category.yml',
			'join/fields/language' => __DIR__ . '/blueprints/fields/language.yml',
			'join/fields/contact' => __DIR__ . '/blueprints/fields/contact.yml',
		],
		'pageModels' => [
			'join-job' =>  \tobimori\Join\Models\JobPage::class
		],
		'translations' => A::keyBy(
			A::map(
				Dir::files(__DIR__ . '/translations'),
				function ($file) {
					$translations = [];
					foreach (Json::read(__DIR__ . '/translations/' . $file) as $key => $value) {
						$translations["join.{$key}"] = $value;
					}

					return A::merge(
						['lang' => F::name($file)],
						$translations
					);
				}
			),
			'lang'
		)
	],
	version: '1.0.0'
);
