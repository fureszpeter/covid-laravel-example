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

## 3. Basic command usage

```
artisan stat:fetch
```

This command will build a `json` file in the base_path folder called `app-status.json`

The file looks like: 

```json
{
    "lastRequestedDate": "2020-04-12T00:00:00+00:00",
    "closestDateForLastRequest": "2020-04-12T12:15:16+00:00",
    "results": [
        {
            "url": "http:\/\/web.archive.org\/web\/20200305085214\/https:\/\/koronavirus.gov.hu\/",
            "date": "2020-03-05T08:52:14+00:00",
            "data": {
                "infected": 2,
                "healed": 0,
                "lockDown": 21,
                "samples": 230,
                "died": 0
            }
        }
  ]
}
```
