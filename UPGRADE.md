# Upgrade Notes

### 2.0.2
- [BUGFIX] Fix wrong type for method `openFile()` [@simonkey](https://github.com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/issues/7)
- [BUGFIX] Fix XPathExpressionDiscoverer selector [@simonkey](https://github.com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/issues/8)
- [BUGFIX] Empty `valid_links` option check in no crawling [@simonkey](https://github.com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/issues/9)

### 2.0.1
- [BUGFIX] do not fetch default locales on build process
- [BUGFIX] Wrong call to get resource in HtmlTagExtractor [@Brainshaker95](https://github.com/dachcom-digital/pimcore-dynamic-search-data-provider-crawler/issues/4)

***

## Migrating from Version 1.x to Version 2.0.0

### Global Changes
- PHP8 return type declarations added: you may have to adjust your extensions accordingly
