<?php

namespace DsWebCrawlerBundle\Service;

use DsWebCrawlerBundle\DsWebCrawlerBundle;
use DsWebCrawlerBundle\EventSubscriber\EventSubscriberInterface;
use DsWebCrawlerBundle\Registry\EventSubscriberRegistryInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
use DynamicSearchBundle\Provider\DataProviderInterface;
use GuzzleHttp\Client;
use DsWebCrawlerBundle\Configuration\Configuration;
use DsWebCrawlerBundle\DsWebCrawlerEvents;
use DsWebCrawlerBundle\Event\CrawlerRequestHeaderEvent;
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
     * @var string
     */
    protected $crawlType;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ResourceMetaInterface
     */
    protected $resourceMeta;

    /**
     * @var string
     */
    protected $contextName;

    /**
     * @var string
     */
    protected $contextDispatchType;

    /**
     * @var array
     */
    protected $providerConfiguration;

    /**
     * @param LoggerInterface                  $logger
     * @param EventSubscriberRegistryInterface $eventSubscriberRegistry
     * @param EventDispatcherInterface         $eventDispatcher
     */
    public function __construct(
        LoggerInterface $logger,
        EventSubscriberRegistryInterface $eventSubscriberRegistry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->eventSubscriberRegistry = $eventSubscriberRegistry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function initFullCrawl(string $contextName, string $contextDispatchType, array $providerConfiguration)
    {
        $this->crawlType = DataProviderInterface::PROVIDER_BEHAVIOUR_FULL_DISPATCH;
        $this->contextName = $contextName;
        $this->contextDispatchType = $contextDispatchType;
        $this->providerConfiguration = $providerConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function initSingleCrawl(ResourceMetaInterface $resourceMeta, string $contextName, string $contextDispatchType, array $providerConfiguration)
    {
        $this->crawlType = DataProviderInterface::PROVIDER_BEHAVIOUR_SINGLE_DISPATCH;
        $this->resourceMeta = $resourceMeta;
        $this->contextName = $contextName;
        $this->contextDispatchType = $contextDispatchType;
        $this->providerConfiguration = $providerConfiguration;
    }

    /**
     * @param string $level
     * @param string $message
     */
    public function log($level, $message)
    {
        $this->logger->log($level, $message, DsWebCrawlerBundle::PROVIDER_NAME, $this->contextName);
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        try {
            $this->initializeSpider();
        } catch (\Exception $e) {
            $this->dispatchError(sprintf('Error while initializing spider. Error was: %s', $e->getMessage()), $e);

            return;
        }

        $queueManager = new InMemoryQueueManager();
        $queueManager->setTraversalAlgorithm(InMemoryQueueManager::ALGORITHM_DEPTH_FIRST);
        $this->spider->setQueueManager($queueManager);

        $this->attachSpiderEvents();

        try {
            $this->setupDiscoverySet();
        } catch (\Exception $e) {
            $this->dispatchError(sprintf('Error while adding discovery sets. Error was: %s', $e->getMessage()), $e);

            return;
        }

        if ($this->getSpecialOption('max_crawl_limit') > 0) {
            $this->getSpiderDownloader()->setDownloadLimit($this->getSpecialOption('max_crawl_limit'));
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

        $this->spider->getDispatcher()->dispatch(new GenericEvent($this, ['spider' => $this->spider]), DsWebCrawlerEvents::DS_WEB_CRAWLER_START);

        $this->spider->crawl();

        $this->spider->getDispatcher()->dispatch(new GenericEvent($this, ['spider' => $this->spider]), DsWebCrawlerEvents::DS_WEB_CRAWLER_FINISH);
    }

    protected function initializeSpider()
    {
        $spider = new Spider($this->getSpecialOption('seed'));
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
        $this->eventDispatcher->dispatch($event, DsWebCrawlerEvents::DS_WEB_CRAWLER_REQUEST_HEADER);

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
                $typeEventSubscriber->setContextName($this->contextName);
                $typeEventSubscriber->setContextDispatchType($this->contextDispatchType);
                $typeEventSubscriber->setCrawlType($this->crawlType);
                $typeEventSubscriber->setResourceMeta($this->resourceMeta);

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

        $discoverySet->maxDepth = $this->hasOption('max_link_depth') ? $this->getOption('max_link_depth') : 15;

        $discoverySet->set(new XPathExpressionDiscoverer("//link[@hreflang]|//a[not(@rel='nofollow')]"));

        $discoverySet->addFilter(new Filter\Prefetch\AllowedSchemeFilter($this->getOption('allowed_schemes')));

        if ($this->getOption('own_host_only') === true) {
            $discoverySet->addFilter(new Filter\Prefetch\AllowedHostsFilter([$this->getSpecialOption('seed')], $this->getOption('allow_subdomains')));
        }

        if ($this->getOption('allow_hash_in_url') === false) {
            $discoverySet->addFilter(new Filter\Prefetch\UriWithHashFragmentFilter());
        }

        if ($this->getOption('allow_query_in_url') === false) {
            $discoverySet->addFilter(new Filter\Prefetch\UriWithQueryStringFilter());
        }

        $discoverySet->addFilter(new Discovery\UriFilter($this->getSpecialOption('invalid_links'), $this->spider->getDispatcher()));

        if ($this->hasOption('valid_links')) {
            $discoverySet->addFilter(new Discovery\NegativeUriFilter($this->getOption('valid_links'), $this->spider->getDispatcher()));
        }
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
     * @return bool
     */
    protected function hasOption($key)
    {
        return isset($this->providerConfiguration[$key]);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getOption($key)
    {
        return $this->providerConfiguration[$key];
    }

    protected function getSpecialOption($key)
    {
        if ($key === 'invalid_links') {
            return $this->getInvalidLinks();
        } elseif ($key === 'max_crawl_limit') {
            return $this->getMaxCrawlLimit();
        } elseif ($key === 'seed') {
            return $this->getSeed();
        }

        return null;
    }

    /**
     * @return int
     */
    protected function getMaxCrawlLimit()
    {
        if ($this->crawlType === DataProviderInterface::PROVIDER_BEHAVIOUR_SINGLE_DISPATCH) {
            return 1;
        }

        return $this->hasOption('max_crawl_limit') ? $this->getOption('max_crawl_limit') : 1;
    }

    /**
     * @return string
     */
    protected function getSeed()
    {
        if ($this->crawlType === DataProviderInterface::PROVIDER_BEHAVIOUR_SINGLE_DISPATCH) {
            $host = $this->providerConfiguration['host'];
            $seedHost = parse_url($host, PHP_URL_HOST);
            $seedScheme = parse_url($host, PHP_URL_SCHEME);

            return sprintf(
                '%s://%s/%s',
                $seedScheme,
                rtrim($seedHost, '/'),
                ltrim($this->providerConfiguration['path'], '/')
            );
        }

        return $this->providerConfiguration['seed'];
    }

    /**
     * @return array|bool
     */
    protected function getInvalidLinks()
    {
        $userInvalidLinks = $this->hasOption('user_invalid_links') ? $this->getOption('user_invalid_links') : [];
        $coreInvalidLinks = $this->hasOption('core_invalid_links') ? $this->getOption('core_invalid_links') : [];

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

    /**
     * @param string     $message
     * @param \Exception $exception
     */
    protected function dispatchError(string $message, \Exception $exception)
    {
        $this->spider->getDispatcher()->dispatch(
            new GenericEvent($this, [
                'spider'    => $this->spider,
                'message'   => $message,
                'exception' => $exception
            ]),
            DsWebCrawlerEvents::DS_WEB_CRAWLER_ERROR
        );
    }
}
