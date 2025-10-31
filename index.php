<?php

use Kirby\Cms\App;

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
			'ttl' => 3600,
			'apiKey' => null,
		],
		'permissions' => [],
		'blueprints' => [
			'join-job' => __DIR__ . '/blueprints/job.yml',
		],
		'models' => [
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
