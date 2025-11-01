<?php

namespace tobimori\Join;

use Kirby\Cms\App;
use Kirby\Cms\Pages;
use Kirby\Cms\Page;

trait MountJoinJobs
{
	protected Pages|null $subpages = null;

	/**
	 * Returns the physical subpages of the page
	 */
	public function subpages()
	{
		if ($this->subpages) {
			return $this->subpages;
		}

		return $this->subpages = Pages::factory($this->inventory()['children'], $this);
	}

	public function children(): Pages
	{
		if ($this->children instanceof Pages) {
			return $this->children;
		}

		$jobIds = $this->fetchJobIds();
		$pages = $this->subpages();

		foreach ($jobIds as $jobId) {
			$slug = "job-{$jobId}"; // TODO: allow customization of slug

			$page = Page::factory([
				'slug' => $slug,
				'template' => $tpl = Join::option('template'),
				'model' => $tpl,
				'parent' => $this,
				'num' => 0,
			]);

			$page->changeStorage(Storage::class);
			/** @var Storage $storage */
			$storage = $page->storage();
			$storage->setJoinId($jobId);

			$pages->add($page);
		}

		return $this->children = $pages;
	}

	protected array|null $jobIds = null;

	protected function fetchJobIds(): array
	{
		if ($this->jobIds) {
			return $this->jobIds;
		}

		return $this->jobIds = Join::cacheRemember('job-ids', function () {
			$response = Join::get('/jobs', ['content' => 'true']);
			if ($response->code() !== 200) {
				return [];
			}

			$jobs = $response->json();
			foreach ($jobs as $job) {
				$jobIds[] = $job['id'];
				Join::cacheSet("job.{$job['id']}", $job);
			}

			return $jobIds;
		});
	}
}
