<?php

namespace tobimori\Join;

use Kirby\Cms\App;
use Kirby\Content\PlainTextStorage;
use Kirby\Content\VersionId;
use Kirby\Cms\Language;
use Kirby\Data\Yaml;
use Kirby\Toolkit\A;

class Storage extends PlainTextStorage
{
	protected static array $joinFields =  [
		'title',
		'id',
		'description',
		'createdAt',
		'lastUpdatedAt',
		'workplaceType',
		'remoteType',
		'employmentTypeId',
		'categoryId',
		'seniorityId',
		'language',
		'uuid',
		'office',
		'salary',
		'contact'
	];


	protected array $data = [];
	protected string|null $joinId = null;

	public function setJoinId(string $joinId): Storage
	{
		$this->joinId = $joinId;
		return $this;
	}

	public function read(VersionId $versionId, Language $language): array
	{
		$content = parent::read($versionId, $language);

		return [
			...$this->readVirtual(),
			...$content,
		];
	}

	public function write(VersionId $versionId, Language $language, array $data): void
	{
		$fields = array_map('strtolower', static::$joinFields);
		ray($data);

		parent::write($versionId, $language, $data);
	}

	public function exists(VersionId $versionId, Language $language): bool
	{
		return parent::exists($versionId, $language) ||  $this->readVirtual() !== [];;
	}

	public function readVirtual(): array
	{
		if ($this->data) {
			return $this->data;
		}

		if (!$this->joinId) {
			return [];
		}

		$jobData = $this->fetchJobData($this->joinId);
		if (!$jobData) {
			return [];
		}

		return $this->data = $this->mapJobDataToContent($jobData);
	}

	protected function fetchJobData(string $joinId): ?array
	{
		$cacheKey = "job.{$joinId}";
		$jobData = Join::cacheGet($cacheKey);

		if ($jobData) {
			return $jobData;
		}

		try {
			$response = Join::get("/jobs/{$joinId}");

			if ($response->code() !== 200) {
				return null;
			}

			$jobData = $response->json();
			Join::cacheSet($cacheKey, $jobData, 60);

			return $jobData;
		} catch (\Exception $e) {
			return null;
		}
	}

	protected function mapJobDataToContent(array $jobData): array
	{
		$fields = [
			'title',
			'id',
			'createdAt',
			'lastUpdatedAt',
			'workplaceType',
			'remoteType',
			'employmentTypeId',
			'categoryId',
			'seniorityId',
			'language'
		];

		$content = array_intersect_key($jobData, array_flip($fields));

		if (isset($jobData['description'])) {
			$content['description'] = str_replace('\\n', '', $jobData['description']);
		}

		$structures = [
			'office' => ['name', 'streetName', 'streetNumber', 'postalCode', 'city', 'countryIso', 'isDefault'],
			'salary' => ['from', 'to', 'currency', 'frequency', 'isShownOnJobAd'],
			'contact' => ['name', 'email', 'position']
		];

		foreach ($structures as $field => $keys) {
			if (isset($jobData[$field])) {
				$content[$field] = Yaml::encode(
					array_intersect_key($jobData[$field], array_flip($keys))
				);
			}
		}

		foreach ($content as $key => $value) {
			$content[strtolower($key)] = (string) $value;
		}

		$content['uuid'] = (string) $jobData['id'];

		return $content;
	}
}
