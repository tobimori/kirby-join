<?php

// we get a numerical ID from the api and then must track the categories manually
// this request can be cached forever (until manual deletion)
// @see - https://docs.join.com/reference/getcategories

return function () {
	// fetch smth
	return [
		'label' => t('join.fields.category'),
		'type' => 'select'
	];
};
