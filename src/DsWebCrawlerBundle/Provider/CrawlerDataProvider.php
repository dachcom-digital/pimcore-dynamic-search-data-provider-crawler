<?php

namespace DsWebCrawlerBundle\Provider;

use DsWebCrawlerBundle\DsWebCrawlerBundle;
use DsWebCrawlerBundle\Service\CrawlerServiceInterface;
use DsWebCrawlerBundle\Service\FileWatcherServiceInterface;
use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Exception\ProviderException;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
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
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->configuration = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp(ContextDataInterface $contextData)
    {
        $this->fileWatcherService->resetPersistenceStore();
    }

    /**
     * {@inheritdoc}
     */
    public function coolDown(ContextDataInterface $contextData)
    {
        $this->fileWatcherService->resetPersistenceStore();
        $this->fileWatcherService->resetUriFilterPersistenceStore();
    }

    /**
     * {@inheritdoc}
     */
    public function cancelledShutdown(ContextDataInterface $contextData)
    {
        $this->fileWatcherService->resetPersistenceStore();
        $this->fileWatcherService->resetUriFilterPersistenceStore();
    }

    /**
     * {@inheritdoc}
     */
    public function emergencyShutdown(ContextDataInterface $contextData)
    {
        $this->fileWatcherService->resetPersistenceStore();
        $this->fileWatcherService->resetUriFilterPersistenceStore();
    }

    /**
     * {@inheritdoc}
     */
    public function provideAll(ContextDataInterface $contextData)
    {
        $this->crawlerService->initFullCrawl($contextData->getName(), $contextData->getContextDispatchType(), $this->configuration);
        $this->crawlerService->process();
    }

    /**
     * {@inheritdoc}
     */
    public function provideSingle(ContextDataInterface $contextData, ResourceMetaInterface $resourceMeta)
    {
        $options = $resourceMeta->getResourceOptions();

        if (!is_string($options['path'])) {
            throw new ProviderException('resource option "path" must be set to provide single data.', DsWebCrawlerBundle::PROVIDER_NAME);
        }

        $this->configuration['path'] = $options['path'];

        $this->crawlerService->initSingleCrawl($resourceMeta, $contextData->getName(), $contextData->getContextDispatchType(), $this->configuration);
        $this->crawlerService->process();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver, string $providerBehaviour)
    {
        $this->configureAlwaysOptions($resolver);

        if ($providerBehaviour === self::PROVIDER_BEHAVIOUR_FULL_DISPATCH) {
            $this->configureFullDispatchOptions($resolver);
        } elseif ($providerBehaviour === self::PROVIDER_BEHAVIOUR_SINGLE_DISPATCH) {
            $this->configureSingleDispatchOptions($resolver);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureAlwaysOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'own_host_only'      => false,
            'allow_subdomains'   => false,
            'allow_query_in_url' => false,
            'allow_hash_in_url'  => false,
            'allowed_mime_types' => ['text/html', 'application/pdf'],
            'allowed_schemes'    => ['http'],
            'content_max_size'   => 0,
            'core_invalid_links' => '@.*\.(js|JS|gif|GIF|jpg|JPG|png|PNG|ico|ICO|eps|jpeg|JPEG|bmp|BMP|css|CSS|sit|wmf|zip|ppt|mpg|xls|gz|rpm|tgz|mov|MOV|exe|mp3|MP3|kmz|gpx|kml|swf|SWF)$@'
        ];

        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));

        $resolver->setAllowedTypes('own_host_only', ['bool']);
        $resolver->setAllowedTypes('allow_subdomains', ['bool']);
        $resolver->setAllowedTypes('allow_query_in_url', ['bool']);
        $resolver->setAllowedTypes('allow_hash_in_url', ['bool']);
        $resolver->setAllowedTypes('allowed_mime_types', ['string[]']);
        $resolver->setAllowedTypes('allowed_schemes', ['string[]']);
        $resolver->setAllowedTypes('content_max_size', ['int']);
        $resolver->setAllowedTypes('core_invalid_links', ['string']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureFullDispatchOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'seed'               => null,
            'valid_links'        => [],
            'user_invalid_links' => [],
            'max_link_depth'     => 15,
            'max_crawl_limit'    => 0,
        ];

        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));

        $resolver->setAllowedTypes('seed', ['string']);
        $resolver->setAllowedTypes('valid_links', ['string[]']);
        $resolver->setAllowedTypes('user_invalid_links', ['string[]']);
        $resolver->setAllowedTypes('max_link_depth', ['int']);
        $resolver->setAllowedTypes('max_crawl_limit', ['int']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureSingleDispatchOptions(OptionsResolver $resolver)
    {
        $defaults = [];

        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_merge(['host'], array_keys($defaults)));

        $resolver->setAllowedTypes('host', ['string']);
    }
}
