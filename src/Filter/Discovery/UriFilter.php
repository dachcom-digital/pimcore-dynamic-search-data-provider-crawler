<?php

namespace DsWebCrawlerBundle\Filter\Discovery;

use DsWebCrawlerBundle\Filter\LogDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

class UriFilter implements PreFetchFilterInterface
{
    use LogDispatcher;

    protected array $regexBag;

    public function __construct(array $regexBag, EventDispatcherInterface $dispatcher)
    {
        $this->regexBag = $regexBag;
        $this->setDispatcher($dispatcher);
    }

    /**
     * @throws \Exception
     */
    public function match(UriInterface $uri): bool
    {
        foreach ($this->regexBag as $regex) {
            if (preg_match($regex, $uri->toString())) {
                $this->notifyDispatcher($uri, 'uri.match.forbidden');

                return true;
            }
        }

        return false;
    }
}
