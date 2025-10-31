<?php

// we get a numerical ID from the api and then must track the employment types manually
// this request can be cached forever (until manual deletion)
// @see - https://docs.join.com/reference/getemploymenttypes

return function () {
	// fetch smth
	return [
		'label' => t('join.fields.employmentType'),
		'type' => 'select'
	];
};
