# Dynamic Search | Data Provider: Web Crawler

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/dynamic-search-data-provider-crawler.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/dynamic-search-data-provider-crawler)
[![Tests](https://img.shields.io/github/workflow/status/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/Codeception/master?style=flat-square&logo=github&label=codeception)](https://github.com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/actions?query=workflow%3ACodeception+branch%3Amaster)
[![PhpStan](https://img.shields.io/github/workflow/status/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/PHP%20Stan/master?style=flat-square&logo=github&label=phpstan%20level%204)](https://github.com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/actions?query=workflow%3A"PHP+Stan"+branch%3Amaster)

A spider crawler extension for [Pimcore Dynamic Search](https://github.com/dachcom-digital/pimcore-dynamic-search).

## Release Plan
| Release | Supported Pimcore Versions | Supported Symfony Versions | Release Date | Maintained           | Branch                                                                                          |
|---------|----------------------------|----------------------------|--------------|----------------------|-------------------------------------------------------------------------------------------------|
| **2.x** | `10.0` - `10.6`            | `^5.4`                     | 19.12.2021   | Yes (Bugs, Features) | master                                                                                          |
| **1.x** | `6.6` - `6.9`              | `^4.4`                     | 18.04.2021   | No                   | [1.x](https://github.com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/tree/1.x) |

***

## Installation  
```json
"require" : {
    "dachcom-digital/dynamic-search" : "~2.0.0",
    "dachcom-digital/dynamic-search-data-provider-crawler" : "~2.0.0"
}
```

### Dynamic Search Bundle
You need to install / enable the Dynamic Search Bundle first.
Read more about it [here](https://github.com/dachcom-digital/pimcore-dynamic-search#installation).
After that, proceed as followed:

### Enabling via `config/bundles.php`:
```php
<?php

return [
    \DsWebCrawlerBundle\DsWebCrawlerBundle::class => ['all' => true],
];
```

### Enabling via `Kernel.php`:
```php
<?php

namespace App;

use Pimcore\HttpKernel\BundleCollection\BundleCollection;

class Kernel extends \Pimcore\Kernel
{
    public function registerBundlesToCollection(BundleCollection $collection): void
    {
        $collection->addBundle(new \DsWebCrawlerBundle\DsWebCrawlerBundle());
    }
}
```

***

## Basic Setup

```yaml
dynamic_search:
    context:
        default:
            data_provider:
                service: 'web_crawler'
                options:
                    always:
                        own_host_only: true
                    full_dispatch:
                        seed: 'http://your-domain.test'
                        valid_links:
                            - '@^http://your-domain.test.*@i'
                        user_invalid_links:
                            - '@^http://your-domain.test\/members.*@i'
                    single_dispatch:
                        host: 'http://your-domain.test.test'
                normalizer:
                    service: 'web_crawler_localized_resource_normalizer'
```

***

## Provider Options

### always

| Name                                 | Default Value                      | Description |
|:-------------------------------------|:-----------------------------------|:------------|
|`own_host_only`                       | false                              |             |
|`allow_subdomains`                    | false                              |             |
|`allow_query_in_url`                  | false                              |             |
|`allow_hash_in_url`                   | false                              |             |
|`allowed_mime_types`                  | ['text/html', 'application/pdf']   |             |
|`allowed_schemes`                     | ['http']                           |             |
|`content_max_size`                    | 0                                  |             |

### full_dispatch

| Name                                 | Default Value | Description |
|:-------------------------------------|:--------------|:------------|
|`seed`                                | null          |             |
|`valid_links`                         | []            |             |
|`user_invalid_links`                  | []            |             |
|`max_link_depth`                      | 15            |             |
|`max_crawl_limit`                     | 0             |             |

### single_dispatch

| Name                                 | Default Value | Description |
|:-------------------------------------|:--------------|:------------|
|`host`                                | null          |             |

***

## Resource Normalizer

### DefaultResourceNormalizer
Identifier: `web_crawler_default_resource_normalizer`
Normalize simple documents
Options: none

### LocalizedResourceNormalizer
Identifier: `web_crawler_localized_resource_normalizer`
Scaffold localized documents

Options:

| Name                          | Default Value                           | Allowed Type    | Description |
|:------------------------------|:----------------------------------------|:----------------|:------------|
|`locales`                      | all pimcore enabled languages           | array           |             |
|`skip_not_localized_documents` | true                                    | bool            | if false, an exception rises if a document/object has no valid locale |

***

## Transformer

### Scaffolder

##### HttpResponseHtmlDataScaffolder
Identifier: `http_response_html_scaffolder`   
Simple object scaffolder.   
Supported types: `VDB\Spider\Resource` with content-type `text/html`.

##### HttpResponsePdfDataScaffolder
Identifier: `http_response_pdf_scaffolder`   
Simple object scaffolder.   
Supported types: `VDB\Spider\Resource` with content-type `application/pdf`.

##### PimcoreElementScaffolder
Identifier: `pimcore_element_scaffolder`   
Simple object scaffolder.   
Supported types: `Asset`, `Document`, `DataObject\Concrete`.

### Field Transformer

##### UriExtractor
Identifier: `resource_uri_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`, `http_response_pdf_scaffolder`

Return Type: `string|null`   
Options: none

##### LanguageExtractor
Identifier: `resource_language_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`, `http_response_pdf_scaffolder`

Return Type: `string|null`
Options: none

##### MetaExtractor
Identifier: `resource_meta_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`

Return Type: `string|null`
Options: 
| Name                         | Default Value | Allowed Type   | Description |
|:-----------------------------|:--------------|:---------------|:------------|
|`name`                        | null          | string         | The name of the meta tag to fetch the value from |

##### HtmlTagExtractor
Identifier: `resource_html_tag_content_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`

Return Type: `string|null`
Options: none

##### TextExtractor
Identifier: `resource_text_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`, `http_response_pdf_scaffolder`

Return Type: `string|null`
| Name                             | Default Value            | Allowed Type | Description                                               |
|:---------------------------------|:-------------------------|:-------------|:----------------------------------------------------------|
|`content_start_indicator`         | `<!-- main-content -->`  | string       | Marks the begin of the indexable page content             |
|`content_end_indicator`           | `<!-- /main-content -->` | string       | Marks the end of the indexable page conten                |
|`content_exclude_start_indicator` | null                     | null\|string  | Marks the begin of the text to be excluded from indexing |
|`content_exclude_end_indicator`   | null                     | null\|string  | Marks the end of the text to be excluded from indexing   |

##### TitleExtractor
Identifier: `resource_title_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`, `http_response_pdf_scaffolder`

Return Type: `string|null`
Options: none

***

## Copyright and License
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.com)  
For licensing details please visit [LICENSE.md](./LICENSE.md)

## Upgrade Info
Before updating, please [check our upgrade notes!](./UPGRADE.md)  
