<?php

namespace DsWebCrawlerBundle\Provider;

use DsWebCrawlerBundle\Service\CrawlerServiceInterface;
use DsWebCrawlerBundle\Service\FileWatcherServiceInterface;
use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Provider\DataProviderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CrawlerDataProvider implements DataProviderInterface
{
    /**
     * @var CrawlerServiceInterface
     */
    protected $crawlerService;

    /**
     * @var FileWatcherServiceInterface
     */
    protected $fileWatcherService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @param CrawlerServiceInterface     $crawlerService
     * @param FileWatcherServiceInterface $fileWatcherService
     */
    public function __construct(CrawlerServiceInterface $crawlerService, FileWatcherServiceInterface $fileWatcherService)
    {
        $this->crawlerService = $crawlerService;
        $this->fileWatcherService = $fileWatcherService;
    }

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(ContextDataInterface $contextData)
    {
        $this->fileWatcherService->resetPersistenceStore();
    }

    /**
     * {@inheritDoc}
     */
    public function coolDown(ContextDataInterface $contextData)
    {
        $this->fileWatcherService->resetPersistenceStore();
        $this->fileWatcherService->resetUriFilterPersistenceStore();
    }

    /**
     * {@inheritDoc}
     */
    public function cancelledShutdown(ContextDataInterface $contextData)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function emergencyShutdown(ContextDataInterface $contextData)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ContextDataInterface $contextData)
    {
        $this->crawlerService->init($this->logger, $contextData->getName(), $this->configuration);
        $this->crawlerService->process();
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'seed'               => null,
            'own_host_only'      => false,
            'allow_subdomains'   => false,
            'allow_query_in_url' => false,
            'allow_hash_in_url'  => false,
            'categories'         => null,
            'valid_links'        => [],
            'user_invalid_links' => [],
            'allowed_mime_types' => ['text/html', 'application/pdf'],
            'allowed_schemes'    => ['http'],
            'max_link_depth'     => 15,
            'max_download_limit' => 0,
            'content_max_size'   => 0,
            'core_invalid_links' => '@.*\.(js|JS|gif|GIF|jpg|JPG|png|PNG|ico|ICO|eps|jpeg|JPEG|bmp|BMP|css|CSS|sit|wmf|zip|ppt|mpg|xls|gz|rpm|tgz|mov|MOV|exe|mp3|MP3|kmz|gpx|kml|swf|SWF)$@'
        ];

        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));

        $resolver->setAllowedTypes('own_host_only', ['bool']);
        $resolver->setAllowedTypes('allow_subdomains', ['bool']);
        $resolver->setAllowedTypes('allow_query_in_url', ['bool']);
        $resolver->setAllowedTypes('allow_hash_in_url', ['bool']);
        $resolver->setAllowedTypes('categories', ['string', 'null']);
        $resolver->setAllowedTypes('valid_links', ['string[]']);
        $resolver->setAllowedTypes('user_invalid_links', ['string[]']);
        $resolver->setAllowedTypes('allowed_mime_types', ['string[]']);
        $resolver->setAllowedTypes('allowed_schemes', ['string[]']);
        $resolver->setAllowedTypes('max_link_depth', ['int']);
        $resolver->setAllowedTypes('max_download_limit', ['int']);
        $resolver->setAllowedTypes('content_max_size', ['int']);
        $resolver->setAllowedTypes('core_invalid_links', ['string']);
        $resolver->setAllowedTypes('seed', ['string']);
    }
}