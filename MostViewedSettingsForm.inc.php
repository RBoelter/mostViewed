<?php

import('lib.pkp.classes.form.Form');


class MostViewedSettingsForm extends Form
{
	public $plugin;

	public function __construct($plugin)
	{
		parent::__construct($plugin->getTemplateResource('settings.tpl'));
		$this->plugin = $plugin;
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

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

	public function readInputData()
	{
		$this->readUserVars(['mostViewedTitle', 'mostViewedDays', 'mostViewedAmount', 'mostViewedYears', 'mostViewedPosition']);
		parent::readInputData();
	}

	public function fetch($request, $template = null, $display = false)
	{
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());

		return parent::fetch($request, $template, $display);
	}

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
