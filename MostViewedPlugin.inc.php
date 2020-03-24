<?php
import('lib.pkp.classes.plugins.GenericPlugin');

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
	public function register($category, $path, $mainContextId = NULL)
	{
		$success = parent::register($category, $path);
		HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		if ($success && $this->getEnabled()) {
			$request = Application::getRequest();
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addStyleSheet(
				'mostViewedArticles',
				$request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/mostViewed.css'
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
		$request = Application::getRequest();
		$contextId = $request->getContext()->getId();
		$smarty->assign('mostReadArticles', json_decode($this->getSetting($contextId, 'articles'), true));
		$settings = json_decode($this->getSetting($contextId, 'settings'), true);
		if ($settings) {
			if ($settings['title'])
				$smarty->assign('mostReadHeadline', $settings['title']);
			if ($settings['position'])
				$smarty->assign('mostReadPosition', $settings['position']);
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
				$this->import('MostViewedSettingsForm');
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
			$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasksAutoStage.xml';
		}
		return false;
	}
}
