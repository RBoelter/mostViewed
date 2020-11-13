<?php

import('lib.pkp.classes.form.Form');

/**
 * @file plugins/generic/mostViewed/MostViewedSettingsForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2020 Ronny Bölter, Leibniz Institute for Psychology (ZPID)
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class MostViewedSettingsForm
 * @ingroup plugins_generic_mostViewed
 *
 * @brief Class for MostViewed Plugin settings implementation
 */
class MostViewedSettingsForm extends Form
{
	public $plugin;

	/**
	 * @copydoc Form::__construct
	 */
	public function __construct($plugin)
	{
		parent::__construct($plugin->getTemplateResource('settings.tpl'));
		$this->plugin = $plugin;
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * @copydoc Form::initData
	 */
	public function initData()
	{
		$contextId = Application::get()->getRequest()->getContext()->getId();
		$data = $this->plugin->getSetting($contextId, 'settings');
		if ($data != null && $data != '') {
			$data = json_decode($data, true);
			$this->setData('mostViewedTitle', $data['title']);
			$this->setData('mostViewedDays', $data['days']);
			$this->setData('mostViewedAmount', $data['amount']);
			$this->setData('mostViewedYears', $data['years']);
			$this->setData('mostViewedPosition', $data['position']);
		}
		parent::initData();
	}

	/**
	 * @copydoc Form::readInputData
	 */
	public function readInputData()
	{
		$this->readUserVars(['mostViewedTitle', 'mostViewedDays', 'mostViewedAmount', 'mostViewedYears', 'mostViewedPosition']);
		parent::readInputData();
	}

	/**
	 * @copydoc Form::fetch
	 */
	public function fetch($request, $template = null, $display = false)
	{
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute
	 */
	public function execute(...$functionArgs)
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
		import('plugins.generic.mostViewed.MostViewedHandler');
		$handler = new MostViewedHandler();
		if (!is_nan($data['days']) && !is_nan($data['amount']) && ($data['years'] == '' || !is_nan($data['years']))) {
			$handler->saveMetricsToPluginSettings($this->plugin, $contextId, intval($data['days']), intval($data['amount']), intval($data['years']));
		}
		import('classes.notification.NotificationManager');
		$notificationMgr = new NotificationManager();
		$notificationMgr->createTrivialNotification(
			Application::get()->getRequest()->getUser()->getId(),
			NOTIFICATION_TYPE_SUCCESS,
			['contents' => __('common.changesSaved')]
		);

		return parent::execute();
	}
}
