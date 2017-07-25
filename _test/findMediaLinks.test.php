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
        /** @var action_plugin_fetchmedia_ajax $admin */
        $admin = plugin_load('action', 'fetchmedia_ajax');
        $actual_links = $admin->findExternalMediaFiles('wiki', 'all');
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
        /** @var action_plugin_fetchmedia_ajax $admin */
        $admin = plugin_load('action', 'fetchmedia_ajax');
        $actual_filename = $admin->constructFileName('http://php.net/images/php.gif');
        $expected_filename = 'php.gif';
        $this->assertEquals($expected_filename, $actual_filename);

        $actual_filename = $admin->constructFileName('\\\\server\\share\\file.pdf');
        $expected_filename = 'file.pdf';
        $this->assertEquals($expected_filename, $actual_filename);
    }
}
