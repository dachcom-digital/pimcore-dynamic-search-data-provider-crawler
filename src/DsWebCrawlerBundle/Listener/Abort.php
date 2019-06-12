<?php

namespace DsWebCrawlerBundle\Listener;

use DsWebCrawlerBundle\Configuration\Configuration;
use DsWebCrawlerBundle\DsWebCrawlerEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;

class Abort
{
    /**
     * @var null
     */
    var $spider = null;

    /**
     * Abort constructor.
     *
     * @param $spider
     */
    public function __construct($spider)
    {
        $this->spider = $spider;
    }

    /**
     * @param Event $event
     */
    public function checkCrawlerState(Event $event)
    {
        return;

        if (!file_exists(Configuration::CRAWLER_PROCESS_FILE_PATH)) {
            $this->spider->getDispatcher()->dispatch(DsWebCrawlerEvents::DS_WEB_CRAWLER_INTERRUPTED,
                new GenericEvent($this, [
                    'uri'          => $event->getArgument('uri'),
                    'errorMessage' => 'crawling aborted by user (tmp file while crawling has suddenly gone.)'
                ]));
        }
    }

    /**
     * @param Event $event
     */
    public function stopCrawler(Event $event)
    {
        exit;
    }
}