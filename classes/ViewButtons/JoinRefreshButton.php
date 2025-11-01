<?php

namespace tobimori\Join\ViewButtons;

use I18n;
use Kirby\Cms\Page;
use Kirby\Panel\Ui\Buttons\ViewButton;
use tobimori\Join\Join;
use tobimori\Join\Models\JobPage;

class JoinRefreshButton extends ViewButton
{
	public function __construct(Page $model)
	{
		$isGlobal = !$model instanceof JobPage;

		parent::__construct(
			component: 'k-join-refresh-button',
			model: $model,
			icon: "refresh",
			text: I18n::translate($isGlobal ? 'join.buttons.refresh.all' : 'join.buttons.refresh'),
			theme: "green-icon",
			responsive: true,
			jobId: $isGlobal ? null : $model->content()->id()->value()
		);
	}
}
