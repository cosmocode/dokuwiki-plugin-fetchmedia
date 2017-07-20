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
}
