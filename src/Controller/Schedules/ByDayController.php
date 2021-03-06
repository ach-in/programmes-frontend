<?php
declare(strict_types = 1);
namespace App\Controller\Schedules;

use App\Controller\Helpers\Breadcrumbs;
use App\Controller\Helpers\StructuredDataHelper;
use App\Controller\Traits\SchedulesPageResponseCodeTrait;
use App\Controller\Traits\UtcOffsetValidatorTrait;
use App\Ds2013\Presenters\Pages\Schedules\ByDayPage\SchedulesByDayPagePresenter;
use App\DsShared\Factory\HelperFactory;
use App\ValueObject\BroadcastDay;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\Broadcast;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Network;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use BBC\ProgrammesPagesService\Service\BroadcastsService;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\NetworksService;
use BBC\ProgrammesPagesService\Service\ServicesService;
use Cake\Chronos\Chronos;
use Cake\Chronos\Date;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ByDayController extends SchedulesBaseController
{
    use UtcOffsetValidatorTrait;
    use SchedulesPageResponseCodeTrait;

    /** @var HelperFactory */
    protected $helperFactory;

    public function __invoke(
        Service $service,
        ?string $date,
        NetworksService $networksService,
        ServicesService $servicesService,
        BroadcastsService $broadcastService,
        CollapsedBroadcastsService $collapsedBroadcastsService,
        HelperFactory $helperFactory,
        UrlGeneratorInterface $router,
        StructuredDataHelper $structuredDataHelper,
        Breadcrumbs $breadcrumbs
    ) {
        if ($this->shouldRedirectToOverriddenUrl($service)) {
            return $this->cachedRedirect(
                $service->getNetwork()->getOption('pid_override_url'),
                $service->getNetwork()->getOption('pid_override_code'),
                3600
            );
        }

        if (!$this->isValidDate($date) || !$this->isValidUtcOffset($this->request()->query->get('utcoffset'))) {
            throw $this->createNotFoundException('Invalid date supplied');
        }

        $this->helperFactory = $helperFactory;
        $this->setAtiContentLabels('schedule', 'schedule-day');
        $this->setContextAndPreloadBranding($service);
        $this->setInternationalStatusAndTimezoneFromContext($service);
        $this->setAtiContentId((string) $service->getPid(), 'pips');

        $dateTimeToShow = $this->dateTimeToShow($date, $service);
        if (!$dateTimeToShow) {
            throw $this->createNotFoundException('Invalid date');
        }

        $broadcastDay = new BroadcastDay($dateTimeToShow, $service->getNetwork()->getMedium());
        $broadcastDayStart = $broadcastDay->start();

        // Get all services that belong to this network
        $servicesInNetwork = $servicesService->findAllInNetworkActiveOn(
            $service->getNetwork()->getNid(),
            $broadcastDayStart
        );

        $broadcasts = [];

        $liveCollapsedBroadcast = null;
        $serviceIsActiveInThisPeriod = $broadcastDay->serviceIsActiveInThisPeriod($service);
        if ($serviceIsActiveInThisPeriod) {
            // Get broadcasts in relevant period
            $broadcasts = $broadcastService->findByServiceAndDateRange(
                $service->getSid(),
                $broadcastDayStart,
                $broadcastDay->end()
            );

            // Hydrate any live broadcasts with a collapsed broadcast
            if ($broadcastDay->isNow()) {
                $liveCollapsedBroadcast = $this->findLiveCollapsedBroadcast(
                    $broadcasts,
                    $collapsedBroadcastsService
                );
            }
        }
        $pagePresenter = new SchedulesByDayPagePresenter(
            $service,
            $broadcastDayStart,
            $broadcasts,
            $date,
            $servicesInNetwork,
            $this->getOtherNetworks($service, $networksService, $broadcastDay),
            $liveCollapsedBroadcast
        );

        $viewData = $this->viewData(
            $service,
            $broadcastDayStart,
            $pagePresenter,
            $service->isInternational() && !$this->request()->query->has('utcoffset'),
            !is_null($date),
            $this->getSchema($structuredDataHelper, $broadcasts)
        );

        // This is from a trait and sets a 404 status code or noindex on the controller
        // as appropriate when we have no broadcasts
        $this->setResponseCodeAndNoIndexProperties($serviceIsActiveInThisPeriod, $broadcasts, $broadcastDay);

        $this->overridenDescription = "This is the daily broadcast schedule for " . $service->getName();
        if ($this->request()->query->has('no_chrome')) {
            return $this->renderWithoutChrome('schedules/by_day.html.twig', $viewData);
        }

        $opts = ['pid' => $service->getPid()];
        $breadcrumbs = $breadcrumbs
            ->forRoute('Schedules', 'schedules_home')
            ->forRoute($service->getName(), 'schedules_by_day', $opts);

        if ($date) {
            $breadcrumbs
                ->forRoute(
                    $broadcastDayStart->format('Y'),
                    'schedules_by_year',
                    ['year' => $broadcastDayStart->format('Y')] + $opts
                )
                ->forRoute(
                    $broadcastDayStart->format('F'),
                    'schedules_by_month',
                    ['date' => $broadcastDayStart->format('Y/m')] + $opts
                )
                ->forRoute(
                    $broadcastDayStart->format('j'),
                    'schedules_by_day',
                    [ 'date' => $broadcastDayStart->format('Y/m/d') ] + $opts
                );
        }

        $this->breadcrumbs = $breadcrumbs->toArray();

        return $this->renderWithChrome('schedules/by_day.html.twig', $viewData);
    }

    /**
     * Returns a string representation of days between the current schedules page and today.
     * Examples returned: "-5", "0", "+3"
     */
    private function getScheduleOffset(int $diffInDays): string
    {
        if ($diffInDays == 0) {
            return (string) $diffInDays;
        }

        return sprintf("%+d", $diffInDays);
    }

    private function getScheduleContext(int $diffInDays): string
    {
        if ($diffInDays == 0) {
            return 'today';
        }

        if ($diffInDays < 0) {
            return 'past';
        }

        return 'future';
    }

    private function getScheduleCurrentFortnight(int $diffInDays): string
    {
        return (abs($diffInDays) > 7) ? 'false' : 'true';
    }

    private function viewData(
        Service $service,
        Chronos $broadcastDayStart,
        SchedulesByDayPagePresenter $pagePresenter,
        bool $scheduleReload,
        bool $isDateExplicit,
        ?array $schema
    ): array {
        return [
            'service' => $service,
            'broadcast_day_start' => $broadcastDayStart,
            'page_presenter' => $pagePresenter,
            'schedule_reload' => $scheduleReload,
            'is_date_explicit' => $isDateExplicit,
            'schema' => $schema,
        ];
    }

    private function dateTimeToShow(?string $dateString, Service $service): Chronos
    {
        if ($this->request()->query->has('utcoffset')) {
            ApplicationTime::setLocalTimeZone($this->request()->query->get('utcoffset'));
        } elseif ($service->isInternational()) {
            // "International" services are UTC, all others are Europe/London (the default)
            ApplicationTime::setLocalTimeZone('UTC');
        }
        if (!$dateString) {
            return ApplicationTime::getLocalTime();
        }
        // Routing should ensure $dateString is in format \d{4}-\d{2}-\d{2}
        // If a date has been provided, use the broadcast date for midday on
        // the given date
        return Chronos::createFromFormat('Y/m/d H:i:s', $dateString . ' 12:00:00', ApplicationTime::getLocalTimeZone());
    }

    private function findLiveCollapsedBroadcast(
        array $broadcasts,
        CollapsedBroadcastsService $collapsedBroadcastsService
    ): ?CollapsedBroadcast {
        foreach ($broadcasts as $broadcast) {
            if ($broadcast->isOnAir()) {
                return $collapsedBroadcastsService->findByBroadcast($broadcast);
            }
        }

        return null;
    }

    /**
     * @param Service $service
     * @param NetworksService $networksService
     * @param BroadcastDay $broadcastDay
     * @return Network[]
     */
    private function getOtherNetworks(Service $service, NetworksService $networksService, BroadcastDay $broadcastDay): array
    {
        if (!$service->isTv() || $service->isInternational()) {
            return [];
        }

        $allTvNetworks = $networksService->findPublishedNetworksByType(
            ['TV'],
            NetworksService::NO_LIMIT
        );
        $whitelistedNetworks = [
            'bbc_one',
            'bbc_two',
            'bbc_three',
            'bbc_four',
            'cbbc',
            'cbeebies',
            'bbc_scotland',
            'bbc_news24',
            'bbc_parliament',
            'bbc_alba',
            's4cpbs',
        ];
        return array_filter($allTvNetworks, function (Network $network) use ($broadcastDay, $whitelistedNetworks) {
            return in_array((string) $network->getNid(), $whitelistedNetworks) &&
                $broadcastDay->serviceIsActiveInThisPeriod($network->getDefaultService());
        });
    }

    private function isValidDate(?string $date): bool
    {
        if (is_null($date)) {
            return true;
        }

        if (!preg_match('#\d{4}/\d{2}/\d{2}#', $date)) {
            return false;
        }

        list($year, $month, $day) = explode('/', $date);

        if ($year < ByYearController::MINIMUM_VALID_YEAR) {
            return false;
        }

        return checkdate((int) $month, (int) $day, (int) $year);
    }

    /**
     * @param StructuredDataHelper $structuredDataHelper
     * @param Broadcast[] $broadcasts
     * @return array|null
     */
    private function getSchema(StructuredDataHelper $structuredDataHelper, array $broadcasts): ?array
    {
        $schemas = [];
        foreach ($broadcasts as $broadcast) {
            $episode = $structuredDataHelper->getSchemaForEpisode($broadcast->getProgrammeItem(), true);
            $episode['publication'] = $structuredDataHelper->getSchemaForBroadcast($broadcast);
            $schemas[] = $episode;
        }

        return $schemas ? $structuredDataHelper->prepare($schemas, true) : null;
    }
}
