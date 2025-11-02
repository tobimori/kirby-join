<?php

namespace tobimori\Join;

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
		'uuid',
		'description',
		'createdAt',
		'lastUpdatedAt',
		'workplaceType',
		'remoteType',
		'employmentTypeId',
		'categoryId',
		'seniorityId',
		'language',
		'office',
		'salary',
		'contact'
	];

	protected array $data = [];
	protected string|null $joinId = null;

	public function joinId(): string|null
	{
		return $this->joinId;
	}

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
		$saveData = A::without($data, $fields);
		parent::write($versionId, $language, $saveData);
	}

	public function readVirtual(): array
	{
		if (!empty($this->data)) {
			return $this->data;
		}

		if (!$this->joinId) {
			return [];
		}

		$jobData = $this->fetchJobData($this->joinId);
		return $this->data = $this->mapJobDataToContent($jobData);
	}

	protected function fetchJobData(string $joinId): ?array
	{
		return static::fetchAndCacheJobData($joinId);
	}

	/**
	 * Fetch job data from JOIN API and cache it
	 */
	public static function fetchAndCacheJobData(string $joinId, bool $forceFresh = false): ?array
	{
		if ($forceFresh) {
			Join::cacheRemove("job.{$joinId}");
		}

		return Join::cacheRemember("job.{$joinId}", function () use ($joinId) {
			try {
				$response = Join::get("/jobs/{$joinId}");

				if ($response->code() !== 200) {
					return [];
				}

				return $response->json();
			} catch (\Exception $e) {
				return [];
			}
		});
	}

	protected function mapJobDataToContent(array $jobData): array
	{
		$content = array_intersect_key($jobData, array_flip(static::$joinFields));

		$content['description'] = str_replace('\\n', '', $jobData['description'] ?? '');

		$structures = [
			'office' => ['name', 'streetName', 'streetNumber', 'postalCode', 'city', 'countryIso', 'isDefault'],
			'salary' => ['from', 'to', 'currency', 'frequency', 'isShownOnJobAd'],
			'contact' => ['name', 'email', 'position']
		];

		foreach ($structures as $field => $keys) {
			$content[$field] = isset($jobData[$field])
				? Yaml::encode(array_intersect_key($jobData[$field], array_flip($keys)))
				: '';
		}

		$content['uuid'] = (string) $jobData['id'];

		return array_combine(
			array_map('strtolower', array_keys($content)),
			array_map('strval', $content)
		);
	}
}
