<?php

/**
 * @file MostViewedHandler.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Copyright (c) 2020 Ronny Bölter, Leibniz Institute for Psychology (ZPID)
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class MostViewedHandler
 * @ingroup plugins_generic_mostViewed
 *
 * @brief Class for cron job functions.
 */

namespace APP\plugins\generic\mostViewed;

use APP\core\Application;
use APP\core\Services;
use APP\statistics\StatisticsHelper;
use DateTime;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;

class MostViewedHandler extends ScheduledTask
{
	public const MOST_READ_DAYS = 30;
	public const DEFAULT_AMOUNT = 5;
	public const MAX_YEARS_BACK = 5;

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	public function getName(): string
	{
		return __('admin.scheduledTask.mostViewed');
	}


	/**
	 * This function is called via cron job or acron plugin.
	 *
	 * @copydoc ScheduledTask::executeActions()
	 */
	public function executeActions(): bool
	{
		/** @var MostViewedPlugin $plugin */
		$plugin = PluginRegistry::getPlugin('generic', 'mostviewedplugin');
		if (!$plugin->getEnabled()) {
			return false;
		}
		$contextDao = Application::getContextDAO();
		foreach ($contextDao->getAll()->toIterator() as $context) {
			if (!$plugin->getEnabled($context->getId())) {
				continue;
			}
			// Default Settings for each Journal
			$mostReadDays = static::MOST_READ_DAYS;
			$amount = static::DEFAULT_AMOUNT;
			$maxYearsBack = static::MAX_YEARS_BACK;
			// Overwrite Settings if Journal has Settings
			$settings = $plugin->getSetting($context->getId(), 'settings');
			if ($settings) {
				$settings = json_decode($settings, true);
				if (is_int($settings['days'] ?? null) && intval($settings['days']) > 0) {
					$mostReadDays = intval($settings['days']);
				}
				if (is_int($settings['amount'] ?? null) && intval($settings['amount']) > 0) {
					$amount = intval($settings['amount']);
				}
				$maxYearsBack = is_int($settings['years'] ?? null) && intval($settings['years']) > 0
					? intval($settings['years'])
					: null;
			}
			$this->saveMetricsToPluginSettings($plugin, $context->getId(), $mostReadDays, $amount, $maxYearsBack);
		}

		return true;
	}

	/**
	 * Saves the metrics to plugin settings to keep the page load as fast as possible.
	 * This function is called when saving the plugin settings and daily via cron job/acron plugin.
	 */
	public function saveMetricsToPluginSettings(MostViewedPlugin $plugin, int $contextId, int $mostReadDays, int $range, ?int $maxYearsBack): void
	{
		if (intval($maxYearsBack) > 0) {
			$dateTime = new DateTime('now');
			$maxYearsBack = $dateTime->modify('-'.$maxYearsBack.' year')->format('Y');
		} else {
			$maxYearsBack = null;
		}
		$articles = $this->getMetrics($contextId, $mostReadDays, $range, $maxYearsBack);
		$plugin->updateSetting($contextId, 'articles', json_encode($articles));
	}

	/**
	 * This function gets the current metrics from the metrics table and sorts them depending on the settings of the plugin
	 */
	private function getMetrics(int $contextId, int $mostReadDays, int $range, ?int $maxYearsBack): array
	{
		$dayString = "-{$mostReadDays} days";
		$range = $range + 1;
		$dateStart = date('Y-m-d', strtotime($dayString));
		$currentDate = date('Y-m-d');
		$statsService = Services::get('stats');
		$topSubmissions = $statsService->getOrderedObjects(
			StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID,
			StatisticsHelper::STATISTICS_ORDER_DESC,
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
		$submissionService = Services::get('submission');
		foreach ($topSubmissions as $topSubmission) {
			$submissionId = $topSubmission['id'];
			$submission = $submissionService->get($submissionId);
			if (isset($maxYearsBack) && $submission->getDatePublished() < $maxYearsBack) {
				continue;
			}
			$publication = $submission->getCurrentPublication();
			$articles[$submissionId] = [
				'articleId' => $submissionId,
				'articleTitle' => $publication->getLocalizedTitle(),
				'articleSubtitle' => $publication->getLocalizedData('subtitle', $submission->getLocale()),
				'articleAuthor' => $publication->getShortAuthorString(),
				'metric' => $topSubmission['total']
			];
			if (++$cc >= $range) {
				break;
			}
		}

		return $articles;
	}
}
