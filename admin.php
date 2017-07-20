<?php
/**
 * DokuWiki Plugin fetchmedia (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michael Große <dokuwiki@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class admin_plugin_fetchmedia extends DokuWiki_Admin_Plugin {
    /**
     * Should carry out any processing required by the plugin.
     */
    public function handle() {
    }

    /**
     * Render HTML output, e.g. helpful text and a form
     */
    public function html() {
        ptln('<h1>'.$this->getLang('menu').'</h1>');
    }
}

// vim:ts=4:sw=4:et: