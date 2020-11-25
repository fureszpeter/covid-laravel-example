<?php

declare(strict_types=1);

namespace App\Console\Commands;

use CovidDataFetcher\Exception\GetMetadataException;
use CovidDataFetcher\Exception\SiteParserException;
use CovidDataFetcher\Service\SiteParserBuilder;
use CovidDataFetcher\Service\WaybackService;
use DateTime;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class FetchData extends Command
{
    protected $signature = 'stat:fetch
            {--D|exact-date= : fetch given date only}
            {--dry-run : do not save}
    ';

    protected $description = 'fetch data';

    private $waybackService;

    public function __construct()
    {
        parent::__construct();
        $this->waybackService = new WaybackService(new Client(), new SiteParserBuilder());
    }

    public function handle(): int
    {
        $today = (new DateTime())->setTime(0, 0, 0, 0);

        $fromDate = config('settings.from_date', '2020-03-05');
        $date = $this->getCurrentState()
            ? $this->getCurrentState()->modify('+1 day')->setTime(0, 0, 0, 0)
            : (new DateTimeImmutable($fromDate))->setTime(0, 0, 0, 0);

        $exactDate = $this->option('exact-date');
        $date = $exactDate ? new DateTimeImmutable($exactDate) : $date;

        $this->info('Processing website at date: '.$date->format(DATE_ATOM));

        try {
            $extractedData = $this->waybackService->extractSiteDataForDate($date);
        } catch (SiteParserException $exception) {
            $this->error(
                sprintf(
                    'Something wrong on date. [date: %s, message: %s]',
                    $date->format(DATE_ATOM),
                    $exception->getMessage()
                )
            );

            file_put_contents(base_path().'/error.log', $exception->getContent());

            return 1;
        } catch (GuzzleException | GetMetadataException $exception) {
            $this->warn('Wayback machine error, retrying...');
            sleep(1);

            return $this->handle();
        }

        if (!$this->option('dry-run')) {
            file_put_contents(base_path().'/app-status.json', json_encode([
                'lastRequestedDate' => $date->format(DATE_ATOM),
                'closestDateForLastRequest' => $extractedData->getDate()->format(DATE_ATOM),
                'results' => array_merge($this->getCurrentResults(), [$extractedData]),
            ], JSON_PRETTY_PRINT));
        }

        $this->info(json_encode($extractedData, JSON_PRETTY_PRINT));

        if (null === $exactDate && $date < $today) {
            return $this->handle();
        }

        return 0;
    }

    private function getCurrentResults(): array
    {
        $content = @file_get_contents(base_path().'/app-status.json');

        if (false === $content) {
            return [];
        }

        $data = \GuzzleHttp\json_decode($content, true);

        return $data['results'] ?? [];
    }

    private function getCurrentState(): ?DateTimeImmutable
    {
        $content = @file_get_contents(base_path().'/app-status.json');

        if (false === $content) {
            return null;
        }

        $data = \GuzzleHttp\json_decode($content, true);

        $currentStateDate = $data['lastRequestedDate'] ?? null;

        return null === $currentStateDate ? null : new DateTimeImmutable($currentStateDate);
    }
}
