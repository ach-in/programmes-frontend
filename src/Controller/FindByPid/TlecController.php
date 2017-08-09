<?php
declare(strict_types = 1);

namespace App\Controller\FindByPid;

use App\Controller\BaseController;
use App\DsAmen\Organism\Map\MapPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Top-level Programme Container Page
 *
 * For Top level ProgrammeContainers such as the Doctor Who brand page.
 *
 * We tend to call this "the brand page", but both Brands and Series are both
 * ProgrammeContainers that may appear at the top of the programme hierarchy.
 */
class TlecController extends BaseController
{
    public function __invoke(
        Request $request,
        ProgrammeContainer $programme,
        ProgrammesService $programmesService,
        CollapsedBroadcastsService $collapsedBroadcastsService,
        ProgrammesAggregationService $aggregationService
    ) {
        $this->setContext($programme);

        $clips = [];
        $galleries = [];

        if ($programme->getOption('show_clip_cards')) {
            $clips = $aggregationService->findDescendantClips($programme, 4);
        }

        if ($programme->getOption('show_gallery_cards')) {
            $galleries = $aggregationService->findDescendantGalleries($programme, 4);
        }

        $upcomingEpisodesCount = $collapsedBroadcastsService->countUpcomingByProgramme($programme);
        $mostRecentBroadcast = null;
        if ($upcomingEpisodesCount === 0) {
            $mostRecentBroadcast = $collapsedBroadcastsService->findPastByProgramme($programme, 1)[0];
        }

        $mapPresenter = new MapPresenter($request, $programme, $upcomingEpisodesCount, $mostRecentBroadcast);

        return $this->renderWithChrome('find_by_pid/tlec.html.twig', [
            'programme' => $programme,
            'clips' => $clips,
            'galleries' => $galleries,
            'mapPresenter' => $mapPresenter,
        ]);
    }
}
