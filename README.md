# How to use `furesz/covid-data-checker`

## 1. Install

```
composer require furesz/covid-data-checker
```

## 2. Description

The service use archive.org WaybackMachine to get historical result from https://koronavirus.gov.hu

NOTE:
Unfortunately the archive.org website sometimes instable, so we can retry request in case of

- GuzzleException
- GetMetadataException

There is also an Exception what we need to handle is the:
- SiteParserException

The `SiteParserException` is because the site structure may change (and changed in the past), so for the different version
we have different parsers:

