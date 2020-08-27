<?php

import('lib.pkp.classes.scheduledTask.ScheduledTask');

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
				if (intval($settings['days']) > 0) {
					$mostReadDays = intval($settings['days']);
				}
				if (intval($settings['amount']) > 0) {
					$amount = intval($settings['amount']);
				}
				if (intval($settings['years']) > 0) {
					$maxYearsBack = intval($settings['years']);
				} else {
					$maxYearsBack = null;
				}
			}
			$this->saveMetricsToPluginSettings($plugin, $context->getId(), $mostReadDays, $amount, $maxYearsBack);
		}

		return true;
	}

	public function saveMetricsToPluginSettings($plugin, $contextId, $mostReadDays = 30, $range = 5, $date = null)
	{
		if ($date != null && intval($date) > 0) {
			$dateTime = new DateTime('now');
			$date = $dateTime->modify('-'.$date.' year')->format('Y');
		}
		$versionString = DAORegistry::getDAO('VersionDAO')->getCurrentVersion()->getVersionString();
		$articles = null;
		if ($versionString && preg_match('/^3\.2/', $versionString) == 1) {
			$articles = $this->getMetrics_3_2($contextId, $mostReadDays, $range, $date);
		} else {
			if ($versionString && preg_match('/^3\.1/', $versionString) == 1) {
				$articles = $this->getMetrics_3_1($contextId, $mostReadDays, $range, $date);
			}
		}
		if ($articles != null) {
			$plugin->updateSetting($contextId, 'articles', json_encode($articles));
		}
	}

	private function getMetrics_3_2($contextId, $mostReadDays, $range, $date)
	{
		$dayString = "-".$mostReadDays." days";
		$daysAgo = date('Y-m-d', strtotime($dayString));
		$currentDate = date('Y-m-d');
		$topSubmissions = Services::get('stats')->getOrderedObjects(
			STATISTICS_DIMENSION_SUBMISSION_ID,
			STATISTICS_ORDER_DESC,
			[
				'contextIds' => [$contextId],
				'dateEnd' => $currentDate,
				'dateStart' => $daysAgo,
				'count' => $date ? null : $range,
				'offset' => 0,
			]
		);
		$articles = array();
		$cc = 0;
		foreach ($topSubmissions as $topSubmission) {
			$submissionId = $topSubmission['id'];
			$submissionService = Services::get('submission');
			$submission = $submissionService->get($submissionId);
			if ($submission) {
				if ($date && $submission->getDatePublished() < $date) {
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

	/**
	 * @param $contextId
	 * @param $mostReadDays
	 * @param $range
	 * @param $date
	 * @return array
	 */
	private function getMetrics_3_1($contextId, $mostReadDays, $range, $date)
	{
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$dayString = "-".$mostReadDays." days";
		$daysAgo = date('Ymd', strtotime($dayString));
		$currentDate = date('Ymd');
		$filter = array(
			STATISTICS_DIMENSION_CONTEXT_ID => $contextId,
		);
		$filter[STATISTICS_DIMENSION_DAY]['from'] = $daysAgo;
		$filter[STATISTICS_DIMENSION_DAY]['to'] = $currentDate;
		$orderBy = array(STATISTICS_METRIC => STATISTICS_ORDER_DESC);
		$column = array(STATISTICS_DIMENSION_SUBMISSION_ID);
		import('lib.pkp.classes.db.DBResultRange');
		$dbResultRange = new DBResultRange($date ? null : $range + 1);
		$metricsDao =& DAORegistry::getDAO('MetricsDAO');
		$result = $metricsDao->getMetrics(OJS_METRIC_TYPE_COUNTER, $column, $filter, $orderBy, $dbResultRange);
		$articles = array();
		$cc = 0;
		foreach ($result as $resultRecord) {
			$submissionId = $resultRecord[STATISTICS_DIMENSION_SUBMISSION_ID];
			$article = $publishedArticleDao->getById($submissionId);
			if ($article) {
				if ($date && $article->getDatePublished() < $date) {
					continue;
				}
				$articles[$submissionId]['articleId'] = $article->getBestArticleId();
				$articles[$submissionId]['articleTitle'] = $article->getLocalizedTitle($article->getLocale());
				$articles[$submissionId]['articleSubtitle'] = $article->getLocalizedSubtitle($article->getLocale());
				$articles[$submissionId]['articleAuthor'] = $article->getAuthorString();
				$articles[$submissionId]['metric'] = $resultRecord[STATISTICS_METRIC];
				if (++$cc >= $range) {
					break;
				}
			}
		}

		return $articles;
	}
}
