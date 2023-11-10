<?php

/**
 * @file MostViewedPlugin.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Copyright (c) 2020 Ronny Bölter, Leibniz Institute for Psychology (ZPID)
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class MostViewedPlugin
 *
 * @ingroup plugins_generic_mostViewed
 *
 * @brief Class for plugin and handler registration
 */

namespace APP\plugins\generic\mostViewed;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class MostViewedPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.most.viewed.title');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.most.viewed.desc');
    }

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        if (!parent::register($category, $path)) {
            return false;
        }

        if (Application::isUnderMaintenance() || !$this->getEnabled($mainContextId)) {
            return true;
        }

        Hook::add('AcronPlugin::parseCronTab', [$this, 'callbackParseCronTab']);
        Hook::add('Templates::Index::journal', [$this, 'mostViewedContent']);

        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->addStyleSheet(
            'mostViewedArticles',
            "{$request->getBaseUrl()}/{$this->getPluginPath()}/css/mostViewed.css"
        );

        return true;
    }

    /**
     * Append most viewed Content to indexJournal.tpl
     */
    public function mostViewedContent(string $hookName, array $args): bool
    {
        /** @var TemplateManager $smarty */
        [, $smarty, &$output] = $args;
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = ($context && $context->getId()) ? $context->getId() : Application::CONTEXT_SITE;
        $settings = json_decode($this->getSetting($contextId, 'settings'), true);
        $smarty->assign([
            'mostReadArticles' => json_decode($this->getSetting($contextId, 'articles'), true),
            'mostReadHeadline' => $settings['title'] ?? null,
            'mostReadPosition' => $settings['position'] ?? null
        ]);
        $output .= $smarty->fetch($this->getTemplateResource('mostViewed.tpl'));
        return Hook::CONTINUE;
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $verb): array
    {
        $actions = parent::getActions($request, $verb);
        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();
        $url = $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']);
        array_unshift($actions, new LinkAction('settings', new AjaxModal($url, $this->getDisplayName()), __('manager.plugins.settings')));
        return $actions;
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request): JSONMessage
    {
        if ($request->getUserVar('verb') !== 'settings') {
            return parent::manage($args, $request);
        }

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

        return parent::manage($args, $request);
    }

    /**
     * Acron Plugin Auto Stage: It is needed if server has no configured cron jobs.
     */
    public function callbackParseCronTab(string $hookName, array $args): bool
    {
        $taskFilesPath = & $args[0]; // Reference needed.
        $taskFilesPath[] = "{$this->getPluginPath()}/scheduledTasksAutoStage.xml";

        return Hook::CONTINUE;
    }
}
