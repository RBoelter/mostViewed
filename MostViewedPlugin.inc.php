<?php
import('lib.pkp.classes.plugins.GenericPlugin');

class MostViewedPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = NULL)
    {
        $success = parent::register($category, $path);
        if ($success && $this->getEnabled()) {
            HookRegistry::register('Plugin::Most:Viewed', array($this, 'mostViewedContent'));
        }
        return $success;
    }


    public function mostViewedContent($hookName, $args)
    {
        /* TODO YEARS and RANGE in Plugin-Settings!!!! */
        $smarty =& $args[1];
        $output =& $args[2];
        $time = new DateTime('now');
        $new_time = $time->modify('-5 year')->format('Y');
        $articles = $this->getFileStats(30, 5, $new_time);
        $smarty->assign('mostReadArticles', $articles);
        $output .= $smarty->fetch($this->getTemplateResource('mostViewed.tpl'));
    }

    function getFileStats($mostReadDays = 30, $range = 5, $date = null)
    {
        $context = Application::getRequest()->getContext();
        $publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $dayString = "-" . $mostReadDays . " days";
        $daysAgo = date('Ymd', strtotime($dayString));
        $currentDate = date('Ymd');
        $filter = array(
            STATISTICS_DIMENSION_CONTEXT_ID => $context->getId(),
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
        return $articles;
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
