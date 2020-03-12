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
		$mostReadDays = 30;
		$range = 5;
		$date = null;
		$contextId = Application::getRequest()->getContext()->getId();
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
		return true;
	}
}