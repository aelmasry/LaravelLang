<?php namespace Arcanedev\LaravelLang;

use Arcanedev\LaravelLang\Contracts\TransChecker as TransCheckerInterface;
use Arcanedev\LaravelLang\Contracts\TransManager as TransManagerInterface;
use Illuminate\Translation\Translator;

/**
 * Class     TransChecker
 *
 * @package  Arcanedev\LaravelLang
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class TransChecker implements TransCheckerInterface
{
    /* ------------------------------------------------------------------------------------------------
     |  Properties
     | ------------------------------------------------------------------------------------------------
     */
    /** @var array */
    private $configs;

    /** @var Translator */
    private $translator;

    /** @var TransManagerInterface */
    private $manager;

    /** @var array */
    private $missing     = [];

    /* ------------------------------------------------------------------------------------------------
     |  Constructor
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Make TransChecker instance.
     *
     * @param  Translator             $translator
     * @param  TransManagerInterface  $manager
     * @param  array                  $configs
     */
    public function __construct(
        Translator $translator,
        TransManagerInterface $manager,
        array $configs
    ) {
        $this->translator = $translator;
        $this->manager    = $manager;
        $this->configs    = $configs;
    }

    /* ------------------------------------------------------------------------------------------------
     |  Getters & Setter
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->translator->getLocale();
    }

    /**
     * Get the locales to check.
     *
     * @return array
     */
    public function getLocales()
    {
        return array_get($this->configs, 'locales', []);
    }

    /**
     * Get the ignored translation attributes.
     *
     * @return array
     */
    public function getIgnoredTranslations()
    {
        return array_get($this->configs, 'check.ignore', []);
    }

    /* ------------------------------------------------------------------------------------------------
     |  Main Functions
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Check the missing translations.
     *
     * @return array
     */
    public function check()
    {
        $this->missing = [];
        $from          = $this->getDefaultLocale();
        $locales       = $this->getLocales();
        $ignored       = $this->getIgnoredTranslations();
        $fromTrans     = $this->getTranslations($from, $ignored);

        foreach ($locales as $to) {
            $toTrans = $this->getTranslations($to, $ignored);

            $this->diffMissing($toTrans, $fromTrans, $from);
            $this->diffMissing($fromTrans, $toTrans, $to);
        }

        return $this->missing;
    }

    /* ------------------------------------------------------------------------------------------------
     |  Other Functions
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Get locale translations from multiple groups.
     *
     * @param  string  $locale
     * @param  array   $ignored
     *
     * @return array
     */
    private function getTranslations($locale, array $ignored)
    {
        $appLocale    = $this->manager->getFrom('app', $locale);
        $vendorLocale = $this->manager->getFrom('vendor', $locale);

        return is_null($appLocale)
            ? $vendorLocale->mergeTranslations($appLocale, $ignored)
            : $appLocale->mergeTranslations($vendorLocale, $ignored);
    }

    /**
     * Diff the missing translations.
     *
     * @param  array   $toTranslations
     * @param  array   $fromTranslations
     * @param  string  $locale
     *
     * @return array
     */
    private function diffMissing(array $toTranslations, array $fromTranslations, $locale)
    {
        $diff = array_diff_key($toTranslations, $fromTranslations);

        if (count($diff) === 0) { return; }

        foreach ($diff as $key => $value) {
            $this->missing[$locale][] = $key;
        }
    }
}