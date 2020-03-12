<?php
import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.mostViewed.MostViewedHandler');

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
		$articles = json_decode($this->getSetting($contextId, 'articles'), true);
		$smarty->assign('mostReadArticles', $articles);
		/*$handler = new MostViewedHandler();*/
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
}
