<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NetworkLogoExtension extends AbstractExtension
{
    private const ALLOWED_LOGOS = [
            'bbc_1xtra', 'bbc_6music', 'bbc_7', 'bbc_alba', 'bbc_asian_network',
            'bbc_four', 'bbc_hd', 'bbc_london', 'bbc_news24', 'bbc_one',
            'bbc_parliament', 'bbc_radio_berkshire', 'bbc_radio_bristol',
            'bbc_radio_cambridge', 'bbc_radio_cornwall',
            'bbc_radio_coventry_warwickshire', 'bbc_radio_cumbria',
            'bbc_radio_cymru', 'bbc_radio_cymru_2', 'bbc_radio_cymru_mwy', 'bbc_scotland', 'bbc_radio_derby', 'bbc_radio_devon',
            'bbc_radio_essex', 'bbc_radio_five_live',
            'bbc_radio_five_live_olympics_extra',
            'bbc_radio_five_live_sports_extra', 'bbc_radio_four',
            'bbc_radio_four_extra', 'bbc_radio_foyle',
            'bbc_radio_gloucestershire', 'bbc_radio_guernsey',
            'bbc_radio_hereford_worcester', 'bbc_radio_humberside',
            'bbc_radio_jersey', 'bbc_radio_kent', 'bbc_radio_lancashire',
            'bbc_radio_leeds', 'bbc_radio_leicester', 'bbc_radio_lincolnshire',
            'bbc_radio_manchester', 'bbc_radio_merseyside',
            'bbc_radio_nan_gaidheal', 'bbc_radio_newcastle',
            'bbc_radio_norfolk', 'bbc_radio_northampton',
            'bbc_radio_nottingham', 'bbc_radio_one', 'bbc_radio_one_vintage', 'bbc_radio_oxford',
            'bbc_radio_scotland', 'bbc_radio_sheffield', 'bbc_radio_shropshire',
            'bbc_radio_solent', 'bbc_radio_somerset_sound', 'bbc_radio_stoke',
            'bbc_radio_suffolk', 'bbc_radio_surrey', 'bbc_radio_sussex',
            'bbc_radio_three', 'bbc_radio_two', 'bbc_radio_two_country', 'bbc_radio_two_eurovision',
            'bbc_radio_ulster', 'bbc_radio_wales', 'bbc_radio_wiltshire', 'bbc_radio_york',
            'bbc_school_radio', 'bbc_tees', 'bbc_three',
            'bbc_three_counties_radio', 'bbc_two', 'bbc_wm', 'bbc_world_news',
            'bbc_world_service', 'cbbc', 'cbeebies', 's4cpbs',
            'bbc_afrique_radio', 'bbc_gahuza_radio', 'bbc_hausa_radio',
            'bbc_indonesian_radio', 'bbc_somali_radio', 'bbc_swahili_radio', 'bbc_music_jazz',
            'bbc_news', 'bbc_sport', 'bbc_hindi_radio', 'bbc_radio_scotland_music_extra',
            'bbc_radio_glastonbury', 'bbc_radio_two_beatles', 'bradford_1xtra',
    ];

    /**
     * @var Packages
     */
    private $packages;

    public function __construct(
        Packages $packages
    ) {
        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_network_logo', [$this, 'getNetworkLogo']),
        ];
    }

    public function getNetworkLogo(string $nid, string $size): string
    {
        if ($size != '64x36') {
            // Only two sizes supported, default to 112x63
            $size = '112x63';
        }

        $logo = 'bbc';
        if (in_array($nid, self::ALLOWED_LOGOS)) {
            $logo = $nid;
        }
        return $this->packages->getUrl('images/logos/' . $size . '/' . $logo . '.png');
    }
}
