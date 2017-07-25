<?php

/**
 * tests for the fetchmedia plugin
 *
 * @group plugin_fetchmedia
 * @group plugins
 */
class FindMediaLinks_plugin_fetchmedia_test extends DokuWikiTest {
    /**
     * tests can override this
     *
     * @var array plugins to enable for test class
     */
    protected $pluginsEnabled = array('fetchmedia');

    public function test_findmedialinks() {
        /** @var action_plugin_fetchmedia_ajax $plugin */
        $plugin = plugin_load('action', 'fetchmedia_ajax');
        $actual_links = $plugin->findExternalMediaFiles('wiki', 'all');
        $expected_links = [
            'wiki:syntax' =>
                [
                    '\\\\server\\share',
                    'http://php.net/images/php.gif',
                ],
        ];
        $this->assertEquals($expected_links, $actual_links);
    }

    public function test_filenameConstruction() {
        /** @var action_plugin_fetchmedia_ajax $plugin */
        $plugin = plugin_load('action', 'fetchmedia_ajax');
        $actual_filename = $plugin->constructFileName('http://php.net/images/php.gif');
        $expected_filename = 'php.gif';
        $this->assertEquals($expected_filename, $actual_filename);

        $actual_filename = $plugin->constructFileName('\\\\server\\share\\file.pdf');
        $expected_filename = 'file.pdf';
        $this->assertEquals($expected_filename, $actual_filename);
    }

    public function test_replaceLinksInText_externalMedia() {
        /** @var action_plugin_fetchmedia_ajax $plugin */
        $plugin = plugin_load('action', 'fetchmedia_ajax');
        $text = '
Resized external image:           {{http://php.net/images/php.gif?200x50}}

  Real size:                        {{wiki:dokuwiki-128.png}}';
        $actual_text = $plugin->replaceLinkInText($text, 'http://php.net/images/php.gif', 'wiki:php.gif');
        $expected_text = '
Resized external image:           {{wiki:php.gif?200x50}}

  Real size:                        {{wiki:dokuwiki-128.png}}';
        $this->assertEquals($expected_text, $actual_text);
    }

    public function test_replaceLinksInText_windowsShare() {
        /** @var action_plugin_fetchmedia_ajax $plugin */
        $plugin = plugin_load('action', 'fetchmedia_ajax');
        $text = 'Windows shares like [[\\\\server\\share\\file.pdf|this]] are recognized, too. Please note that these only make sense in a homogeneous user group like a corporate [[wp>Intranet]].

  Windows Shares like [[\\\\server\\share\\file.pdf|this]] are recognized, too.';
        $actual_text = $plugin->replaceLinkInText($text, '\\\\server\\share\\file.pdf', 'wiki:file.pdf');
        $expected_text = 'Windows shares like {{wiki:file.pdf|this}} are recognized, too. Please note that these only make sense in a homogeneous user group like a corporate [[wp>Intranet]].

  Windows Shares like [[\\\\server\\share\\file.pdf|this]] are recognized, too.';
        $this->assertEquals($expected_text, $actual_text);
    }
}
