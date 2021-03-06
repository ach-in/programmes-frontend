<?php
declare(strict_types = 1);

namespace App\Ds2013\Presenters\Section\RelatedTopics;

use App\Ds2013\Presenter;
use App\ExternalApi\Ada\Domain\AdaClass;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;

class RelatedTopicsPresenter extends Presenter
{
    /** @var Programme */
    private $context;

    /** @var array AdaClass[] */
    private $relatedTopics;

    protected $options = [
        'ATI_prefix' => 'related',
    ];

    public function __construct(array $relatedTopics, Programme $context, array $options = [])
    {
        parent::__construct($options);
        $this->context = $context;
        $this->relatedTopics = $relatedTopics;
    }

    /**
     * @return AdaClass[]
     */
    public function getRelatedTopics(): array
    {
        return $this->relatedTopics;
    }

    public function hideCount(): bool
    {
        $contextIsEpisodePage = $this->context instanceof Episode;
        $contextIsTleo = $this->context->isTleo();

        return ($contextIsEpisodePage && $contextIsTleo);
    }
}
