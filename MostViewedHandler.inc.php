<?php

import('lib.pkp.classes.scheduledTask.ScheduledTask');

/**
 * @file plugins/generic/mostViewed/MostViewedHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2020 Ronny Bölter, Leibniz Institute for Psychology (ZPID)
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class MostViewedHandler
 * @ingroup plugins_generic_mostViewed
 *
 * @brief Class for cron job functions.
 */
class MostViewedHandler extends ScheduledTask
{
	/**
	 * @copydoc ScheduledTask::getName()
	 */
	public function getName()
	{
		return __('admin.scheduledTask.mostViewed');
	}


	/**
	 * This function is called via cron job or acron plugin.
	 *
	 * @copydoc ScheduledTask::executeActions()
	 */
	public function executeActions()
	{
		$plugin = PluginRegistry::getPlugin('generic', 'mostviewedplugin');
		if (!$plugin->getEnabled()) {
			return false;
		}
		$contextDao = Application::getContextDAO();
		for ($contexts = $contextDao->getAll(true); $context = $contexts->next();) {
			if (!$plugin->getEnabled($context->getId())) {
				continue;
			}
			// Default Settings for each Journal
			$mostReadDays = 30;
			$amount = 5;
			$maxYearsBack = 5;
			// Overwrite Settings if Journal has Settings
			$settings = $plugin->getSetting($context->getId(), 'settings');
			if ($settings) {
				$settings = json_decode($settings, true);
				if (key_exists('days', $settings) && is_numeric($settings['days']) && intval($settings['days']) > 0) {
					$mostReadDays = intval($settings['days']);
				}
				if (key_exists('amount', $settings) && is_numeric($settings['amount']) && intval($settings['amount']) > 0) {
					$amount = intval($settings['amount']);
				}
				if (key_exists('years', $settings) && is_numeric($settings['years']) && intval($settings['years']) > 0) {
					$maxYearsBack = intval($settings['years']);
				} else {
					$maxYearsBack = null;
				}
			}
			$this->saveMetricsToPluginSettings($plugin, $context->getId(), $mostReadDays, $amount, $maxYearsBack);
		}

		return true;
	}

	/**
	 * Saves the metrics to plugin settings to keep the page load as fast as possible.
	 * This function is called when saving the plugin settings and daily via cron job/acron plugin.
	 *
	 * @param $plugin
	 * @param $contextId
	 * @param int $mostReadDays
	 * @param int $range
	 * @param null $maxYearsBack
	 */
	public function saveMetricsToPluginSettings($plugin, $contextId, $mostReadDays = 30, $range = 5, $maxYearsBack = null)
	{
		if ($maxYearsBack != null && intval($maxYearsBack) > 0) {
			$dateTime = new DateTime('now');
			$maxYearsBack = $dateTime->modify('-'.$maxYearsBack.' year')->format('Y');
		} else {
			$maxYearsBack = null;
		}
		$articles = $this->getMetrics($contextId, $mostReadDays, $range, $maxYearsBack);
		if ($articles != null) {
			$plugin->updateSetting($contextId, 'articles', json_encode($articles));
		}
	}

	/**
	 * This function gets the current metrics from the metrics table and sorts them depending on the settings of the plugin
	 *
	 * @param $contextId
	 * @param $mostReadDays
	 * @param $range
	 * @param $maxYearsBack
	 * @return array
	 */
	private function getMetrics($contextId, $mostReadDays, $range, $maxYearsBack)
	{
		$dayString = "-".$mostReadDays." days";
		$range = $range + 1;
		$dateStart = date('Y-m-d', strtotime($dayString));
		$currentDate = date('Y-m-d');
		$topSubmissions = Services::get('stats')->getOrderedObjects(
			STATISTICS_DIMENSION_SUBMISSION_ID,
			STATISTICS_ORDER_DESC,
			[
				'contextIds' => [$contextId],
				'dateEnd' => $currentDate,
				'dateStart' => $dateStart,
				'count' => $maxYearsBack ? null : $range,
				'offset' => 0,
			]
		);
		$articles = array();
		$cc = 1;
		foreach ($topSubmissions as $topSubmission) {
			$submissionId = $topSubmission['id'];
			$submissionService = Services::get('submission');
			$submission = $submissionService->get($submissionId);
			if ($submission) {
				if (isset($maxYearsBack) && $submission->getDatePublished() < $maxYearsBack) {
					continue;
				}
				$articles[$submissionId]['articleId'] = $submissionId;
				$articles[$submissionId]['articleTitle'] = $submission->getCurrentPublication()->getLocalizedTitle();
				$articles[$submissionId]['articleSubtitle'] = $submission->getCurrentPublication()->getLocalizedData('subtitle', $submission->getLocale());
				$articles[$submissionId]['articleAuthor'] = $submission->getCurrentPublication()->getShortAuthorString();
				$articles[$submissionId]['metric'] = $topSubmission['total'];
				if (++$cc >= $range) {
					break;
				}
			}
		}

		return $articles;
	}

}
