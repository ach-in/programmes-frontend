<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\Group;

use App\Ds2013\Presenter;
use App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters\CoreEntityTitlePresenter;
use App\DsShared\Factory\HelperFactory;
use BBC\ProgrammesPagesService\Domain\Entity\Collection;
use BBC\ProgrammesPagesService\Domain\Entity\Gallery;
use BBC\ProgrammesPagesService\Domain\Entity\Group;
use BBC\ProgrammesPagesService\Domain\Entity\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GroupPresenter extends Presenter
{
    protected $options = [
        'context_programme' => null,
        'show_image' => true,
        'show_synopsis' => true,
        'img_classes' => '1/4@bpb1 1/4@bpb2 1/3@bpw',
        'img_default_width' => 320,
        'img_sizes' => [ 0 => '0vw', 320 => 1/4, 480 => 1/4, 600 => 1/3, 1008 => '336px' ],
        'highlight_box_classes' => '',
        'branding_context' => 'page',
        'title_options' => [
            'title_tag' => 'h4',
            'title_format' => 'item::ancestry',
        ],
        'ATI_prefix' => '', // see constructor
    ];

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var \App\DsShared\Factory\HelperFactory */
    private $helperFactory;

    /** @var Group */
    private $group;

    public function __construct(
        UrlGeneratorInterface $router,
        HelperFactory $helperFactory,
        Group $group,
        array $options = []
    ) {
        if (array_key_exists('title_options', $options)) {
            $options['title_options'] = array_merge(
                $this->options['title_options'],
                $options['title_options']
            );
        }
        if (!array_key_exists('ATI_prefix', $options)) {
            $options['ATI_prefix'] = $group->getType();
        }
        parent::__construct($options);
        $this->router = $router;
        $this->helperFactory = $helperFactory;
        $this->group = $group;

        if ($this->options['highlight_box_classes']) {
            $this->options['highlight_box_classes'] .= sprintf(
                ' br-keyline br-blocklink-%1$s br-%1$s-linkhover-onbg015--hover',
                $this->options['branding_context']
            );
        }
    }

    public function getCoreEntityTitlePresenter(): CoreEntityTitlePresenter
    {
        return new CoreEntityTitlePresenter(
            $this->router,
            $this->helperFactory->getTitleLogicHelper(),
            $this->group,
            $this->helperFactory->getStreamUrlHelper(),
            array_merge($this->options['title_options'], [
                'context_programme' => $this->options['context_programme'],
                'ATI_prefix' => $this->options['ATI_prefix'],
            ])
        );
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getImage(): ?Image
    {
        if (!$this->options['show_image']) {
            return null;
        }

        return $this->group->getImage();
    }

    public function getSynopsis(): string
    {
        if (!$this->options['show_synopsis']) {
            return '';
        }

        return $this->group->getShortSynopsis();
    }
}
