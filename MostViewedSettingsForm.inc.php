<?php

/**
 * @file MostViewedSettingsForm.inc.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Copyright (c) 2020 Ronny Bölter, Leibniz Institute for Psychology (ZPID)
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class MostViewedSettingsForm
 * @ingroup plugins_generic_mostViewed
 *
 * @brief Class for MostViewed Plugin settings implementation
 */

namespace APP\plugins\generic\mostViewed;

use APP\core\Application;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\template\TemplateManager;
use Exception;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class MostViewedSettingsForm extends Form
{
	/**
	 * @copydoc Form::__construct
	 */
	public function __construct(public MostViewedPlugin $plugin)
	{
		parent::__construct($plugin->getTemplateResource('settings.tpl'));
		$this->plugin = $plugin;
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * @copydoc Form::initData
	 */
	public function initData(): void
	{
		$contextId = Application::get()->getRequest()->getContext()->getId();
		$data = $this->plugin->getSetting($contextId, 'settings');
		if (strlen($data ?? '')) {
			try {
				$data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
				$this->setData('mostViewedTitle', $data['title']);
				$this->setData('mostViewedDays', $data['days']);
				$this->setData('mostViewedAmount', $data['amount']);
				$this->setData('mostViewedYears', $data['years']);
				$this->setData('mostViewedPosition', $data['position']);
			} catch (Exception $e) {
				error_log($e);
			}
		}
		parent::initData();
	}

	/**
	 * @copydoc Form::readInputData
	 */
	public function readInputData(): void
	{
		$this->readUserVars(['mostViewedTitle', 'mostViewedDays', 'mostViewedAmount', 'mostViewedYears', 'mostViewedPosition']);
		parent::readInputData();
	}

	/**
	 * @copydoc Form::fetch
	 */
	public function fetch($request, $template = null, $display = false): string
	{
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute
	 */
	public function execute(...$functionArgs): mixed
	{
		$contextId = Application::get()->getRequest()->getContext()->getId();
		$data = [
			"title" => $this->getData('mostViewedTitle'),
			"days" => $this->getData('mostViewedDays'),
			"amount" => $this->getData('mostViewedAmount'),
			"years" => $this->getData('mostViewedYears'),
			"position" => $this->getData('mostViewedPosition'),
		];
		$this->plugin->updateSetting($contextId, 'settings', json_encode($data));
		$handler = new MostViewedHandler();
		if (ctype_digit($data['days']) && ctype_digit($data['amount']) && ($data['years'] == '' || ctype_digit($data['years']))) {
			$handler->saveMetricsToPluginSettings($this->plugin, $contextId, intval($data['days']), intval($data['amount']), intval($data['years']));
		}
		$notificationMgr = new NotificationManager();
		$notificationMgr->createTrivialNotification(
			Application::get()->getRequest()->getUser()->getId(),
			Notification::NOTIFICATION_TYPE_SUCCESS,
			['contents' => __('common.changesSaved')]
		);

		return parent::execute(...$functionArgs);
	}
}
