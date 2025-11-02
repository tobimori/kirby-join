<?php

namespace tobimori\Join;

use Kirby\Cms\App;
use Kirby\Cms\Language;
use Kirby\Cms\Pages;
use Kirby\Cms\Page;
use Kirby\Content\VersionId;

trait MountJoinJobs
{
	protected Pages|null $subpages = null;
	protected array|null $jobIds = null;

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

	/**
	 * Returns all children of the page
	 */
	public function children(): Pages
	{
		if ($this->children instanceof Pages) {
			return $this->children;
		}

		$jobIds = $this->fetchJobIds();
		$pages = $this->subpages();
		$template = Join::option('template');

		foreach ($jobIds as $jobId) {
			$slug = "job-{$jobId}"; // TODO: allow customization of slug

			$page = Page::factory([
				'slug' => $slug,
				'template' => $template,
				'model' => $template,
				'parent' => $this,
				'num' => 0,
			]);

			$page->changeStorage(Storage::class);
			/** @var Storage $storage */
			$storage = $page->storage();
			$storage->setJoinId($jobId);

			$pages->add($page);
		}

		// auto-delete orphaned job pages if enabled
		if (Join::option('autoDelete')) {
			foreach ($pages as $page) {
				if ($page->intendedTemplate()->name() === $template && !$page->storage() instanceof Storage) {
					App::instance()->impersonate('kirby', fn() => $page->storage()->delete(VersionId::latest(), Language::single()));
					if ($jobId) {
						Join::cacheRemove("job.{$jobId}");
					}

					$pages->remove($page);
				}
			}

			$this->subpages = $pages;
		}

		return $this->children = $pages;
	}

	protected function fetchJobIds(): array
	{
		if ($this->jobIds) {
			return $this->jobIds;
		}

		return $this->jobIds = Join::fetchAndCacheAllJobs();
	}
}
