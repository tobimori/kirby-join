# JOIN for Kirby CMS

JOIN is an advanced recruiting software & applicant tracking system provided as SaaS. This plugin provides a sophisticated integration for Kirby CMS: **View job postings from the panel, display them on your site and send applications to JOIN with the custom DreamForm Action.**

This plugin requires a [paid Advanced or Enterprise subscription to JOIN](https://join.com/pricing); it is not included in the free plan.

### Limitations

Customizing the application workflow, e.g. using screening questions is not supported because of limitations in the JOIN API. Submitting additional details with an application is only possible by adding a Note to the applicant. 

Updating job postings is not supported because the API offers a vastly limited feature set. For example, it is not possible to update the parts of a job posting (e.g. Intro, Requirements, Benefits) per part, but just as a whole. However, when doing so, JOIN downgrades the job posting to a single text box 'description-only' mode, which limits the editability in the JOIN backend. This and a few other edge cases is why we decided to not support updating or creating job postings for now. If this is a requirement for your project, please contact us for a custom quote.

Pagination is currently not implemented, which means the maximum number of active jobs being synchronized is 50. Status that are not 'online' are ignored (or deleted, if active). 

## Get Started

TODO

## License

JOIN for Kirby CMS is not free software. In order to run it on a public server, you'll have to purchase a valid Kirby license & a valid JOIN for Kirby CMS license.

Copyright 2025 © Tobias Möritz - Love & Kindness GmbH
