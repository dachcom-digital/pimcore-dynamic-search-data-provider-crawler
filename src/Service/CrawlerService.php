<?php

namespace DsWebCrawlerBundle\Service;

use DsWebCrawlerBundle\DsWebCrawlerBundle;
use DsWebCrawlerBundle\EventSubscriber\EventSubscriberInterface;
use DsWebCrawlerBundle\Registry\EventSubscriberRegistryInterface;
use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Exception\UnresolvedContextConfigurationException;
use DynamicSearchBundle\Logger\LoggerInterface;
use GuzzleHttp\Client;
use DsWebCrawlerBundle\Configuration\Configuration;
use DsWebCrawlerBundle\DsWebCrawlerEvents;
use DsWebCrawlerBundle\Event\CrawlerRequestHeaderEvent;
use DsWebCrawlerBundle\Listener;
use DsWebCrawlerBundle\Filter\Discovery;
use DsWebCrawlerBundle\Filter\PostFetch;
use DsWebCrawlerBundle\PersistenceHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\Spider;
use VDB\Spider\Filter;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use GuzzleHttp\Middleware;

class CrawlerService implements CrawlerServiceInterface
{
    /**
     * @var EventSubscriberRegistryInterface
     */
    protected $eventSubscriberRegistry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var Spider
     */
    protected $spider;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ContextDataInterface
     */
    protected $contextData;

    /**
     * @param EventSubscriberRegistryInterface $eventSubscriberRegistry
     * @param EventDispatcherInterface         $eventDispatcher
     */
    public function __construct(
        EventSubscriberRegistryInterface $eventSubscriberRegistry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventSubscriberRegistry = $eventSubscriberRegistry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function init(LoggerInterface $logger, ContextDataInterface $contextOptions)
    {
        $this->logger = $logger;
        $this->contextData = $contextOptions;
    }

    /**
     * @param string $level
     * @param string $message
     */
    public function log($level, $message)
    {
        $this->logger->log($level, $message, DsWebCrawlerBundle::PROVIDER_NAME, $this->contextData->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        try {
            $this->initializeSpider();
        } catch (\Exception $e) {
            $this->log('error', $e->getMessage());
            return;
        }

        $queueManager = new InMemoryQueueManager();
        $queueManager->setTraversalAlgorithm(InMemoryQueueManager::ALGORITHM_DEPTH_FIRST);
        $this->spider->setQueueManager($queueManager);

        try {
            $this->attachSpiderEvents();
            $this->setupDiscoverySet();
        } catch (\Exception $e) {
            $this->log('error', $e->getMessage());
            return;
        }

        if ($this->getOption('max_download_limit') > 0) {
            $this->getSpiderDownloader()->setDownloadLimit($this->getOption('max_download_limit'));
        }

        if ($this->getOption('content_max_size') !== 0) {
            $this->getSpiderDownloader()->addPostFetchFilter(new PostFetch\MaxContentSizeFilter($this->getOption('content_max_size')));
        }

        $this->getSpiderDownloader()->addPostFetchFilter(new PostFetch\MimeTypeFilter($this->getOption('allowed_mime_types')));

        // we need to deep set spider downloader again,
        // since there is no way to dispatch a successfully resource fetch in downloader itself
        $persistenceHandler = new PersistenceHandler\FileSerializedResourcePersistenceHandler(Configuration::CRAWLER_PERSISTENCE_STORE_DIR_PATH);
        $persistenceHandler->setSpiderDownloader($this->getSpiderDownloader());

        $this->getSpiderDownloader()->setPersistenceHandler($persistenceHandler);

        $abortListener = new Listener\Abort($this->spider);
        $this->getSpiderDownloader()->getDispatcher()->addListener(
            SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
            [$abortListener, 'checkCrawlerState']
        );

        $this->spider->getDispatcher()->addListener(
            DsWebCrawlerEvents::DS_WEB_CRAWLER_INTERRUPTED,
            [$abortListener, 'stopCrawler']
        );

        $this->spider->getDispatcher()->addListener(
            SpiderEvents::SPIDER_CRAWL_USER_STOPPED,
            [$abortListener, 'stopCrawler']
        );

        $this->spider->getDispatcher()->dispatch(DsWebCrawlerEvents::DS_WEB_CRAWLER_START, new GenericEvent($this, ['spider' => $this->spider]));

        try {
            $this->spider->crawl();
        } catch (\Throwable $e) {
            $this->log('error', $e->getMessage());
        }

        $this->spider->getDispatcher()->dispatch(DsWebCrawlerEvents::DS_WEB_CRAWLER_FINISH, new GenericEvent($this, ['spider' => $this->spider]));

    }

    protected function initializeSpider()
    {
        $spider = new Spider($this->getOption('seed'));
        $guzzleClient = new Client(['allow_redirects' => false, 'debug' => false]);

        $this->spider = $spider;

        $this->getSpiderDownloaderRequestHandler()->setClient($guzzleClient);
        $this->addHeadersToRequest($guzzleClient->getConfig('handler'));
    }

    /**
     * @param HandlerStack $stack
     */
    protected function addHeadersToRequest(HandlerStack $stack)
    {
        $defaultHeaderElements = [
            [
                'name'       => 'DynamicSearchWebCrawler',
                'value'      => '1.0.0',
                'identifier' => 'ds-web-crawler'
            ]
        ];

        $event = new CrawlerRequestHeaderEvent();
        $this->eventDispatcher->dispatch(
            DsWebCrawlerEvents::DS_WEB_CRAWLER_REQUEST_HEADER,
            $event
        );

        $headerElements = array_merge($defaultHeaderElements, $event->getHeaders());

        foreach ($headerElements as $headerElement) {
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($headerElement) {
                return $request->withHeader($headerElement['name'], $headerElement['value']);
            }), $headerElement['identifier']);
        }
    }

    protected function attachSpiderEvents()
    {
        foreach ($this->eventSubscriberRegistry->all() as $dispatcherType => $eventSubscriber) {
            /** @var EventSubscriberInterface $typeEventSubscriber */
            foreach ($eventSubscriber as $typeEventSubscriber) {

                $typeEventSubscriber->setLogger($this->logger);
                $typeEventSubscriber->setContextData($this->contextData);

                switch ($dispatcherType) {
                    case 'spider':
                        $this->spider->getDispatcher()->addSubscriber($typeEventSubscriber);
                        break;
                    case 'queue':
                        $this->getSpiderQueueManager()->getDispatcher()->addSubscriber($typeEventSubscriber);
                        break;
                    case 'downloader':
                        $this->getSpiderDownloader()->getDispatcher()->addSubscriber($typeEventSubscriber);
                        break;

                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function setupDiscoverySet()
    {
        $discoverySet = $this->spider->getDiscovererSet();

        $discoverySet->maxDepth = $this->getOption('max_link_depth');

        $discoverySet->set(new XPathExpressionDiscoverer("//link[@hreflang]|//a[not(@rel='nofollow')]"));

        $discoverySet->addFilter(new Filter\Prefetch\AllowedSchemeFilter($this->getOption('allowed_schemes')));

        if ($this->getOption('own_host_only') === true) {
            $discoverySet->addFilter(new Filter\Prefetch\AllowedHostsFilter([$this->getOption('seed')], $this->getOption('allow_subdomains')));
        }

        if ($this->getOption('allow_hash_in_url') === false) {
            $discoverySet->addFilter(new Filter\Prefetch\UriWithHashFragmentFilter());
        }

        if ($this->getOption('allow_query_in_url') === false) {
            $discoverySet->addFilter(new Filter\Prefetch\UriWithQueryStringFilter());
        }

        $discoverySet->addFilter(new Discovery\UriFilter($this->getOption('invalid_links'), $this->spider->getDispatcher()));
        $discoverySet->addFilter(new Discovery\NegativeUriFilter($this->getOption('valid_links'), $this->spider->getDispatcher()));

    }

    /**
     * @return Downloader
     */
    protected function getSpiderDownloader()
    {
        /** @var Downloader $downloader */
        $downloader = $this->spider->getDownloader();

        return $downloader;
    }

    /**
     * @return GuzzleRequestHandler
     */
    protected function getSpiderDownloaderRequestHandler()
    {
        /** @var GuzzleRequestHandler $requestHandler */
        $requestHandler = $this->getSpiderDownloader()->getRequestHandler();

        return $requestHandler;
    }

    /**
     * @return InMemoryQueueManager
     */
    protected function getSpiderQueueManager()
    {
        /** @var InMemoryQueueManager $queueManager */
        $queueManager = $this->spider->getQueueManager();

        return $queueManager;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getOption($key)
    {
        $options = [];

        try {
            $options = $this->contextData->getDataProviderOptions();
        } catch (UnresolvedContextConfigurationException $e) {
            $this->log('ERROR', $e->getMessage());
        }

        if ($key === 'invalid_links') {
            return $this->getInvalidLinks();
        }

        return $options[$key];

    }

    /**
     * @return array
     */
    protected function getInvalidLinks()
    {
        $options = [];

        try {
            $options = $this->contextData->getDataProviderOptions();
        } catch (UnresolvedContextConfigurationException $e) {
            $this->log('ERROR', $e->getMessage());
        }

        $userInvalidLinks = $options['user_invalid_links'];
        $coreInvalidLinks = $options['core_invalid_links'];

        if (!empty($userInvalidLinks) && !empty($coreInvalidLinks)) {
            $invalidLinkRegex = array_merge($userInvalidLinks, [$coreInvalidLinks]);
        } elseif (!empty($userInvalidLinks)) {
            $invalidLinkRegex = $userInvalidLinks;
        } elseif (!empty($coreInvalidLinks)) {
            $invalidLinkRegex = [$coreInvalidLinks];
        } else {
            $invalidLinkRegex = [];
        }

        return $invalidLinkRegex;
    }
}