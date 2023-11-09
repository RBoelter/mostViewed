<?php

/**
 * @file MostViewedPlugin.inc.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Copyright (c) 2020 Ronny Bölter, Leibniz Institute for Psychology (ZPID)
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class MostViewedPlugin
 * @ingroup plugins_generic_mostViewed
 *
 * @brief Class for plugin and handler registration
 */

namespace APP\plugins\generic\mostViewed;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class MostViewedPlugin extends GenericPlugin
{
	/**
	 * @return string plugin name
	 */
	public function getDisplayName()
	{
		return __('plugins.generic.most.viewed.title');
	}

	/**
	 * @return string plugin description
	 */
	public function getDescription()
	{
		return __('plugins.generic.most.viewed.desc');
	}

	/**
	 * Register the plugin
	 *
	 * @param String $category
	 * @param String $path
	 * @param null $mainContextId
	 * @return bool
	 */
	public function register($category, $path, $mainContextId = null)
	{
		$success = parent::register($category, $path);
		HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		if ($success && $this->getEnabled()) {
			$request = Application::get()->getRequest();
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addStyleSheet(
				'mostViewedArticles',
				$request->getBaseUrl().'/'.$this->getPluginPath().'/css/mostViewed.css'
			);
			HookRegistry::register('Templates::Index::journal', array($this, 'mostViewedContent'));
		}

		return $success;
	}

	/**
	 * Append most viewed Content to indexJournal.tpl
	 * @param $hookName
	 * @param $args
	 */
	public function mostViewedContent($hookName, $args)
	{
		$smarty =& $args[1];
		$output =& $args[2];
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$contextId = ($context && $context->getId()) ? $context->getId() : Application::CONTEXT_SITE;
		$smarty->assign('mostReadArticles', json_decode($this->getSetting($contextId, 'articles'), true));
		$settings = json_decode($this->getSetting($contextId, 'settings'), true);
		if ($settings) {
			if ($settings['title']) {
				$smarty->assign('mostReadHeadline', $settings['title']);
			}
			if ($settings['position']) {
				$smarty->assign('mostReadPosition', $settings['position']);
			}
		}
		$output .= $smarty->fetch($this->getTemplateResource('mostViewed.tpl'));
	}

	/**
	 * Add settings button to plugin
	 * @param $request
	 * @param array $verb
	 * @return array
	 */
	public function getActions($request, $verb)
	{
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		return array_merge(
			$this->getEnabled() ? array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			) : array(),
			parent::getActions($request, $verb)
		);
	}

	/**
	 * Manage Settings
	 * @param array $args
	 * @param PKPRequest $request
	 * @return JSONMessage
	 */
	public function manage($args, $request)
	{
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$form = new MostViewedSettingsForm($this);
				if (!$request->getUserVar('save')) {
					$form->initData();

					return new JSONMessage(true, $form->fetch($request));
				}
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();

					return new JSONMessage(true);
				}
		}

		return parent::manage($args, $request);
	}

	/**
	 * Acron Plugin Auto Stage: It is needed if server has no configured cron jobs.
	 * @param $hookName
	 * @param $args
	 * @return bool
	 */
	function callbackParseCronTab($hookName, $args)
	{
		if ($this->getEnabled() || !Config::getVar('general', 'installed')) {
			$taskFilesPath =& $args[0]; // Reference needed.
			$taskFilesPath[] = $this->getPluginPath().DIRECTORY_SEPARATOR.'scheduledTasksAutoStage.xml';
		}

		return false;
	}
}
