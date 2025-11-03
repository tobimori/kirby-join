<?php

namespace tobimori\Join\Actions;

use tobimori\DreamForm\Actions\Action;
use tobimori\DreamForm\Models\FormPage;
use tobimori\DreamForm\Exceptions\PerformerException;
use tobimori\Join\Join;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;
use Kirby\Toolkit\V;
use Kirby\Data\Json;

if (!class_exists('tobimori\DreamForm\Actions\Action')) {
	return;
}

class ApplyAction extends Action
{
	public const string TYPE = 'join-apply';

	/**
	 * Returns the Blocks fieldset blueprint for the actions' settings
	 */
	public static function blueprint(): array
	{
		return [
			'name' => t('join.actions.apply.name'),
			'preview' => 'fields',
			'wysiwyg' => true,
			'icon' => 'join',
			'fields' => [
				'jobInfo' => [
					'label' => t('join.actions.apply.jobInfo.label'),
					'type' => 'info',
					'width' => '1/2',
					'text' => t('join.actions.apply.jobInfo.text'),
					'theme' => 'info'
				],
				'source' => [
					'label' => t('join.actions.apply.source.label'),
					'type' => 'select',
					'width' => '1/2',
					'options' => [
						'ORGANIC' => t('join.actions.apply.source.organic'),
						'REFERRALS' => t('join.actions.apply.source.referrals'),
						'JOB_BOARDS' => t('join.actions.apply.source.jobBoards'),
						'SOCIAL_MEDIA' => t('join.actions.apply.source.socialMedia'),
						'AGENCY' => t('join.actions.apply.source.agency'),
						'SOURCING_TOOLS' => t('join.actions.apply.source.sourcingTools'),
						'EVENTS' => t('join.actions.apply.source.events'),
						'INTERNAL_APPLICATION' => t('join.actions.apply.source.internal'),
						'OTHER' => t('join.actions.apply.source.other')
					],
					'default' => 'ORGANIC'
				],
				'candidate' => [
					'label' => t('join.actions.apply.candidate'),
					'type' => 'object',
					'width' => '1/2',
					'required' => true,
					'help' => t('join.actions.apply.candidate.help'),
					'fields' => [
						'firstName' => [
							'label' => t('join.actions.apply.firstName.label'),
							'type' => 'dreamform-dynamic-field',
							'required' => true,
							'width' => '1/2'
						],
						'lastName' => [
							'label' => t('join.actions.apply.lastName.label'),
							'type' => 'dreamform-dynamic-field',
							'required' => true,
							'width' => '1/2'
						],
						'email' => [
							'label' => t('join.actions.apply.email.label'),
							'type' => 'dreamform-dynamic-field',
							'limitType' => 'email',
							'required' => true,
							'width' => '1/2'
						],
						'phone' => [
							'label' => t('join.actions.apply.phone.label'),
							'type' => 'dreamform-dynamic-field',
							'width' => '1/2'
						],
					]
				],
				'documentsMapping' => [
					'label' => t('join.actions.apply.documentsMapping.label'),
					'type' => 'structure',
					'width' => '1/2',
					'help' => t('join.actions.apply.documentsMapping.help'),
					'fields' => [
						'type' => [
							'label' => t('join.actions.apply.documentType.label'),
							'type' => 'select',
							'options' => [
								'CV' => t('join.actions.apply.documentType.cv'),
								'COVER_LETTER' => t('join.actions.apply.documentType.coverLetter'),
								'PORTFOLIO' => t('join.actions.apply.documentType.portfolio'),
								'CERTIFICATES' => t('join.actions.apply.documentType.certificates'),
								'OTHER' => t('join.actions.apply.documentType.other'),
								'REFERENCES' => t('join.actions.apply.documentType.references'),
								'TRANSCRIPT' => t('join.actions.apply.documentType.transcript'),
								'WORK_SAMPLES' => t('join.actions.apply.documentType.workSamples')
							],
							'required' => true,
							'width' => '1/2'
						],
						'field' => [
							'label' => t('join.actions.apply.documentField.label'),
							'type' => 'select',
							'options' => FormPage::getFields('file-upload'),
							'required' => true,
							'width' => '1/2'
						]
					]
				],
				'noteTemplate' => [
					'label' => t('join.actions.apply.noteTemplate.label'),
					'extends' => 'dreamform/fields/writer-with-fields'
				]
			]
		];
	}

	/**
	 * Returns the actions' blueprint group
	 */
	public static function group(): string
	{
		return 'integrations';
	}

	/**
	 * Execute the Join application
	 */
	public function run(): void
	{
		$jobId = $this->getJobId();
		if (!$jobId) {
			$this->cancel('Invalid job ID', public: true);
		}

		$candidate = $this->getCandidateData();
		if (!$candidate) {
			$this->cancel('Missing required candidate information', public: true);
		}

		$documents = $this->getDocuments();

		$applicationData = [
			'jobId' => $jobId,
			'candidate' => $candidate,
			'source' => $this->block()->source()->value() ?: 'ORGANIC',
			'integrationExternalId' => $this->submission()->uuid()->toString()
		];

		if (!empty($documents)) {
			$applicationData['documents'] = $documents;
		}

		try {
			$response = Join::post('/applications', $applicationData);

			if ($response->code() > 299) {
				$error = $response->json();
				$errorMessage = $error['error']['message'] ?? $error['message'] ?? 'Failed to create application';

				$isPublic = false;
				$userMessage = t('join.actions.apply.error.unknown');

				if (Str::contains($errorMessage, 'already exists')) {
					$isPublic = true;
					$userMessage = t('join.actions.apply.error.alreadyExists');
				}

				$this->cancel(
					$isPublic ? $userMessage : $errorMessage,
					public: $isPublic,
					log: [
						'icon' => 'join',
						'title' => 'join.actions.apply.error',
						'type' => 'error',
						'data' => [
							'error' => $errorMessage,
							'response_code' => $response->code()
						]
					]
				);
			}

			$result = $response->json();
			$candidateId = $result['candidateId'] ?? null;

			$this->log(
				[
					'jobId' => $jobId,
					'candidateId' => $candidateId,
					'email' => $candidate['email']
				],
				type: 'none',
				icon: 'join',
				title: 'join.actions.apply.success'
			);

			if ($candidateId && $this->block()->noteTemplate()->isNotEmpty()) {
				$this->createCandidateNote($candidateId);
			}
		} catch (\Exception $e) {
			if (!$e instanceof \tobimori\DreamForm\Exceptions\PerformerException) {
				$this->cancel(
					'Application failed: ' . $e->getMessage(),
					public: false,
					log: [
						'icon' => 'join',
						'title' => 'join.actions.apply.error',
						'type' => 'error'
					]
				);
			} else {
				throw $e;
			}
		}
	}

	/**
	 * Get the job ID from the referer page
	 */
	protected function getJobId(): int|null
	{
		$refererPage = $this->submission()->findRefererPage();

		if (!$refererPage) {
			return null;
		}

		if ($refererPage->intendedTemplate()->name() !==  Join::option('template')) {
			return null;
		}

		$jobId = $refererPage->content()->get('id')->value();

		return $jobId ? intval($jobId) : null;
	}

	/**
	 * Get candidate data from form submission
	 */
	protected function getCandidateData(): array|null
	{
		$candidateMapping = $this->block()->candidate()->toObject();

		$email = $this->submission()->valueForDynamicField($candidateMapping->email())?->value();
		$firstName = $this->submission()->valueForDynamicField($candidateMapping->firstName())?->value();
		$lastName = $this->submission()->valueForDynamicField($candidateMapping->lastName())?->value();

		if (!$email || !$firstName || !$lastName) {
			return null;
		}

		if (!V::email($email)) {
			$this->cancel('Invalid email address', public: true);
		}

		$candidate = [
			'email' => $email,
			'firstName' => $firstName,
			'lastName' => $lastName
		];

		$phone = $this->submission()->valueForDynamicField($candidateMapping->phone())?->value();
		if (!empty($phone)) {
			$phone = trim($phone);
			if (!Str::startsWith($phone, '+')) {
				$phone = '+' . $phone;
			}
			$candidate['phone'] = $phone;
		}

		return $candidate;
	}

	/**
	 * Get documents from file upload fields
	 */
	protected function getDocuments(): array
	{
		$documents = [];
		$documentsMappings = $this->block()->documentsMapping()->toStructure();

		foreach ($documentsMappings as $mapping) {
			$fieldId = $mapping->content()->field()->value();

			if (empty($fieldId)) {
				continue;
			}

			$value = $this->submission()->valueForId($fieldId);

			if (!$value) {
				continue;
			}

			$filesToProcess = [];

			if (is_string($value->value())) {
				$files = $value->toFiles();
				foreach ($files as $file) {
					$filesToProcess[] = ['file' => $file];
				}
			} elseif (is_array($value->value())) {
				$uploads = array_values(A::filter($value->value(), fn ($file) => $file['error'] === UPLOAD_ERR_OK));
				foreach ($uploads as $upload) {
					$filesToProcess[] = ['upload' => $upload];
				}
			}

			foreach ($filesToProcess as $fileData) {
				if (isset($fileData['file'])) {
					$file = $fileData['file'];
					$content = $file->read();
					$filename = $file->filename();
				} elseif (isset($fileData['upload'])) {
					$upload = $fileData['upload'];
					$content = file_get_contents($upload['tmp_name']);
					$filename = basename($upload['name']);
				} else {
					continue;
				}

				if (!$content) {
					continue;
				}

				$documents[] = [
					'type' => $mapping->content()->type()->value(),
					'name' => $filename,
					'data' => base64_encode($content)
				];
			}
		}

		return $documents;
	}

	/**
	 * Create a note with submission details
	 */
	protected function createCandidateNote(int $candidateId): void
	{
		$noteContent = $this->submission()->toString(
			$this->block()->noteTemplate()->value(),
			$this->getTemplateValues()
		);

		try {
			$response = Join::post("/candidates/{$candidateId}/notes", [
				'content' => $noteContent
			]);

			if ($response->code() > 299) {
				$this->log(
					['error' => 'Failed to create candidate note'],
					type: 'warning',
					icon: 'alert',
					title: 'join.actions.apply.note.error'
				);
			}
		} catch (\Exception $e) {
			$this->log(
				['error' => $e->getMessage()],
				type: 'warning',
				icon: 'alert',
				title: 'join.actions.apply.note.error'
			);
		}
	}

	/**
	 * Get template values for note processing
	 */
	protected function getTemplateValues(): array
	{
		return A::merge(
			$this->submission()->values()->toArray(),
			[
				'page' => $this->submission()->findRefererPage(),
				'submission' => $this->submission(),
				'form' => $this->submission()->form(),
			]
		);
	}
}
