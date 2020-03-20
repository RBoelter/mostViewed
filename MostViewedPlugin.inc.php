<?php
import('lib.pkp.classes.plugins.GenericPlugin');

class MostViewedPlugin extends GenericPlugin
{
	public function register($category, $path, $mainContextId = NULL)
	{
		$success = parent::register($category, $path);
		HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		if ($success && $this->getEnabled()) {
			HookRegistry::register('Templates::Index::journal', array($this, 'mostViewedContent'));
		}
		return $success;
	}

	public function mostViewedContent($hookName, $args)
	{
		$smarty =& $args[1];
		$output =& $args[2];
		$contextId = Application::getRequest()->getContext()->getId();
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

	public function getDisplayName()
	{
		return __('plugins.generic.most.viewed.title');
	}

	public function getDescription()
	{
		return __('plugins.generic.most.viewed.desc');
	}


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
}
