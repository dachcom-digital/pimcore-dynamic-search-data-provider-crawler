<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DsWebCrawlerBundle\DsWebCrawlerBundle;
use DsWebCrawlerBundle\DsWebCrawlerEvents;
use DynamicSearchBundle\Context\ContextDefinitionInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\SpiderEvents;

class LogEventSubscriber implements EventSubscriberInterface
{
    protected string|float $startedTime;
    protected int $persisted = 0;
    protected int $queued = 0;
    protected int $filtered = 0;
    protected int $failed = 0;
    protected string $contextName;
    protected string $contextDispatchType;
    protected string $crawlType;
    protected ?ResourceMetaInterface $resourceMeta = null;
    protected LoggerInterface $logger;

    public function setContextName(string $contextName): void
    {
        $this->contextName = $contextName;
    }

    public function setContextDispatchType(string $contextDispatchType): void
    {
        $this->contextDispatchType = $contextDispatchType;
    }

    public function setCrawlType(string $crawlType): void
    {
        $this->crawlType = $crawlType;
    }

    public function setResourceMeta(?ResourceMetaInterface $resourceMeta): void
    {
        $this->resourceMeta = $resourceMeta;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH   => 'logFiltered',
            SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH    => 'logFiltered',
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE       => 'logQueued',
            SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED => 'logPersisted',
            SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST      => 'logFailed',
            SpiderEvents::SPIDER_CRAWL_POST_REQUEST       => 'logCrawled',
            SpiderEvents::SPIDER_CRAWL_USER_STOPPED       => 'logStoppedBySignal',
            DsWebCrawlerEvents::DS_WEB_CRAWLER_START      => 'logStarted',
            DsWebCrawlerEvents::DS_WEB_CRAWLER_FINISH     => 'logFinished'
        ];
    }

    public function logStarted(GenericEvent $event): void
    {
        $this->queued = 0;
        $this->filtered = 0;
        $this->failed = 0;
        $this->persisted = 0;

        $this->startedTime = microtime(true);
    }

    public function logFinished(GenericEvent $event): void
    {
        $totalTime = microtime(true) - $this->startedTime;
        $totalTime = number_format((float) $totalTime, 3, '.', '');
        $minutes = str_pad(floor($totalTime / 60), 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($totalTime % 60, 2, '0', STR_PAD_LEFT);
        $peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        if ($this->queued > 0) {
            $this->logEvent('finished', $event, 'debug', 'enqueued links: ' . $this->queued);
        }
        if ($this->filtered > 0) {
            $this->logEvent('finished', $event, 'debug', 'skipped links: ' . $this->filtered);
        }
        if ($this->failed > 0) {
            $this->logEvent('finished', $event, 'debug', 'failed links: ' . $this->failed);
        }
        if ($this->persisted > 0) {
            $this->logEvent('finished', $event, 'debug', 'persisted links: ' . $this->persisted);
        }

        if ($this->contextDispatchType === ContextDefinitionInterface::CONTEXT_DISPATCH_TYPE_INDEX) {
            $this->logEvent('finished', $event, 'debug', 'memory peak usage: ' . $peakMem . 'MB');
            $this->logEvent('finished', $event, 'debug', 'total time: ' . $minutes . ':' . $seconds);
        }
    }

    public function logQueued(GenericEvent $event): void
    {
        $this->queued++;

        $this->logEvent('queued', $event);
    }

    public function logPersisted(GenericEvent $event): void
    {
        $this->persisted++;

        $this->logEvent('persisted', $event);
    }

    /**
     * @param GenericEvent $event
     */
    public function logFiltered(GenericEvent $event): void
    {
        $this->queued++;

        $filterType = $event->hasArgument('filterType') ? $event->getArgument('filterType') . '.' : '';
        $name = $filterType . 'filtered';
        $this->logEvent($name, $event);
    }

    public function logFailed(GenericEvent $event): void
    {
        $this->failed++;

        $message = preg_replace('/\s+/S', ' ', $event->getArgument('message'));
        $this->logEvent('failed', $event, 'critical', $message);
    }

    public function logStoppedBySignal(Event $event): void
    {
        $logEvent = new GenericEvent($this, ['errorMessage' => 'crawling canceled']);
        $this->logEvent('stopped', $logEvent, 'debug', $logEvent->getArgument('errorMessage'));
    }

    public function logCrawled(GenericEvent $event): void
    {
        $this->logEvent('uri.crawled', $event, 'debug');
    }

    protected function logEvent(string $name, GenericEvent $event, string $debugLevel = 'debug', string $additionalMessage = ''): void
    {
        $triggerLog = in_array($name, [
            'uri.crawled',
            'uri.match.invalid.filtered',
            'uri.match.forbidden.filtered',
            'filtered',
            'failed',
            'stopped',
            'started',
            'finished',
        ]);

        if ($triggerLog) {
            $prefix = '[spider.' . $name . '] ';

            $message = $prefix;
            if (!empty($additionalMessage)) {
                $message .= $additionalMessage . ' ';
            }

            $message .= $event->hasArgument('uri') ? $event->getArgument('uri')->toString() : '';

            $this->logger->log($debugLevel, $message, DsWebCrawlerBundle::PROVIDER_NAME, $this->contextName);
        }
    }
}
