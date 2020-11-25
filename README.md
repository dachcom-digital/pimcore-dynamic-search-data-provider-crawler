# Dynamic Search | Data Provider: Web Crawler

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/dynamic-search-data-provider-crawler.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/dynamic-search-data-provider-crawler)
[![Travis](https://img.shields.io/travis/com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/master.svg?style=flat-square)](https://travis-ci.com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler)
[![PhpStan](https://img.shields.io/badge/PHPStan-level%202-brightgreen.svg?style=flat-square)](#)

A Spider Crawler Extension for [Pimcore Dynamic Search](https://github.com/dachcom-digital/pimcore-dynamic-search).


## Requirements
* Pimcore >= 6.3
* Symfony >= 4.4
- Pimcore Dynamic Search

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
Options: none

##### HtmlTagExtractor
Identifier: `resource_html_tag_content_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`

Return Type: `string|null`
Options: none

##### TextExtractor
Identifier: `resource_text_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`, `http_response_pdf_scaffolder`

Return Type: `string|null`
Options: none

##### TitleExtractor
Identifier: `resource_title_extractor`   
Supported Scaffolder: `http_response_html_scaffolder`, `http_response_pdf_scaffolder`

Return Type: `string|null`
Options: none