<?php

namespace Erusev\Parsedown\Tests;

use Erusev\Parsedown\Components\Inlines\Url;
use Erusev\Parsedown\Configurables\InlineTypes;
use Erusev\Parsedown\Configurables\StrictMode;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

/**
 * Test Parsedown against the CommonMark spec
 *
 * @link http://commonmark.org/ CommonMark
 */
class CommonMarkTestStrict extends TestCase
{
    const SPEC_URL = 'https://raw.githubusercontent.com/jgm/CommonMark/master/spec.txt';
    const SPEC_LOCAL_CACHE = __DIR__ .'/spec_cache.txt';
    const SPEC_CACHE_SECONDS = 5 * 60;

    protected $parsedown;

    protected function setUp()
    {
        $this->parsedown = new Parsedown(new State([
            StrictMode::enabled(),
            InlineTypes::initial()->removing([Url::class]),
        ]));
    }

    /**
     * @dataProvider data
     * @param $id
     * @param $section
     * @param $markdown
     * @param $expectedHtml
     */
    public function testExample($id, $section, $markdown, $expectedHtml)
    {
        $actualHtml = $this->parsedown->text($markdown);
        $this->assertEquals($expectedHtml, $actualHtml);
    }

    public static function getSpec()
    {
        if (
            \is_file(self::SPEC_LOCAL_CACHE)
            && \time() - \filemtime(self::SPEC_LOCAL_CACHE) < self::SPEC_CACHE_SECONDS
        ) {
            $spec = \file_get_contents(self::SPEC_LOCAL_CACHE);
        } else {
            $spec = \file_get_contents(self::SPEC_URL);
            \file_put_contents(self::SPEC_LOCAL_CACHE, $spec);
        }

        return $spec;
    }

    /**
     * @return array
     */
    public function data()
    {
        $spec = self::getSpec();
        if ($spec === false) {
            $this->fail('Unable to load CommonMark spec from ' . self::SPEC_URL);
        }

        $spec = \str_replace("\r\n", "\n", $spec);
        $spec = \strstr($spec, '<!-- END TESTS -->', true);

        $matches = [];
        \preg_match_all('/^`{32} example\n((?s).*?)\n\.\n(?:|((?s).*?)\n)`{32}$|^#{1,6} *(.*?)$/m', $spec, $matches, \PREG_SET_ORDER);

        $data = [];
        $currentId = 0;
        $currentSection = '';
        foreach ($matches as $match) {
            if (isset($match[3])) {
                $currentSection = $match[3];
            } else {
                $currentId++;
                $markdown = \str_replace('→', "\t", $match[1]);
                $expectedHtml = isset($match[2]) ? \str_replace('→', "\t", $match[2]) : '';

                $data[$currentId] = [
                    'id' => $currentId,
                    'section' => $currentSection,
                    'markdown' => $markdown,
                    'expectedHtml' => $expectedHtml
                ];
            }
        }

        return $data;
    }
}
