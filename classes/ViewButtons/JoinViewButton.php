<?php

namespace tobimori\Join\ViewButtons;

use I18n;
use Kirby\Panel\Ui\Buttons\ViewButton;
use tobimori\Join\Join;
use tobimori\Join\Models\JobPage;

class JoinViewButton extends ViewButton
{
	public function __construct(JobPage $model)
	{
		if (!$model instanceof JobPage) {
			parent::__construct(
				model: $model,
				disabled: true,
				style: 'display: none'
			);
			return;
		}

		parent::__construct(
			model: $model,
			icon: "join",
			text: I18n::translate('join.buttons.openInJoin'),
			theme: "blue-icon",
			link: "https://join.com/jobs/{$model->content()->id()->value()}/applications",
			responsive: true
		);
	}
}
