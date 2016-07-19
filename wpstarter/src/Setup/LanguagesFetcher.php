<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class LanguagesFetcher
{
    /**
     * @var array
     */
    private static $languages = [];

    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @param \WCM\WPStarter\Setup\IO $io
     */
    public function __construct(IO $io)
    {
        $this->io = $io;
    }

    /**
     * @param string $version
     * @param bool   $useSsl
     * @return array|bool
     */
    public function fetch($version = '0.0.0', $useSsl = true)
    {
        if (self::$languages) {
            return self::$languages;
        }

        $url = $useSsl ? 'https' : 'http';
        $url .= '://api.wordpress.org/translations/core/1.0/?version=';
        $remote = new UrlDownloader($url.$version, $this->io);
        $result = $remote->fetch();

        if (! $result && $useSsl) {
            $this->io->comment('Languages download failed, trying with disabled SSL...');

            return $this->fetch($version, false);
        } elseif (! $result && substr_count($version, '.') === 2) {
            $verArray = explode('.', $version);
            array_pop($verArray);
            $version = implode('.', $verArray);
            $this->io->comment("Languages download failed, trying with version {$version}...");

            return $this->fetch($version, true);
        } elseif (! $result) {
            return [];
        }

        try {
            $all = (array)@json_decode($result, true);
            $languages = [];
            if (! empty($all['translations'])) {
                foreach ((array)$all['translations'] as $lang) {
                    empty($lang['language']) or $languages[] = $lang['language'];
                }
            }
        } catch (\Exception $e) {
            $languages = [];
        }

        $languages
            ? self::$languages = $languages
            : $this->io->comment('Error on loading languages from wordpress.org');

        return $languages;
    }
}