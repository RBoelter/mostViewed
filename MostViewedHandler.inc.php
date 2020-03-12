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
	 * @return bool
	 * @throws Exception
	 */
	public function executeActions()
	{
		$plugin = PluginRegistry::getPlugin('generic', 'mostviewedplugin');
		if (!$plugin->getEnabled()) {
			return false;
		}
		$contextDao = Application::getApplication()->getContextDAO();
		for ($contexts = $contextDao->getAll(true); $context = $contexts->next();) {
			/* TODO Plugin Settings for each journal*/
			$mostReadDays = 30;
			$range = 5;
			$dateTime = new DateTime('now');
			$maxYearsBack = $dateTime->modify('-5 year')->format('Y');


			$this->saveMetricsToPluginSettings($plugin, $context->getId(), $mostReadDays, $range, $maxYearsBack);
		}
		return true;
	}

	/**
	 * @param $plugin
	 * @param $contextId
	 * @param int $mostReadDays
	 * @param int $range
	 * @param null $date
	 * @throws Exception
	 */
	public function saveMetricsToPluginSettings($plugin, $contextId, $mostReadDays = 30, $range = 5, $date = null)
	{
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$dayString = "-" . $mostReadDays . " days";
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
				if ($date && $article->getDatePublished() < $date)
					continue;
				$journal = $journalDao->getById($article->getJournalId());
				$articles[$submissionId]['journalPath'] = $journal->getPath();
				$articles[$submissionId]['articleId'] = $article->getBestArticleId();
				$articles[$submissionId]['articleTitle'] = $article->getLocalizedTitle($article->getLocale());
				$articles[$submissionId]['articleSubtitle'] = $article->getLocalizedSubtitle($article->getLocale());
				$articles[$submissionId]['articleAuthor'] = $article->getAuthorString();
				$articles[$submissionId]['metric'] = $resultRecord[STATISTICS_METRIC];
				if (++$cc >= $range)
					break;
			}
		}
		$dateTime = new Datetime();
		$plugin->updateSetting($contextId, 'articles', json_encode($articles));
		$plugin->updateSetting($contextId, 'datetime', $dateTime->format("Y-m-d"));
	}
}