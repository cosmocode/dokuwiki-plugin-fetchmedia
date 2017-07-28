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
        $doc = '<h1>'.$this->getLang('menu').'</h1>';


        $form = new \dokuwiki\Form\Form(['id' => 'fetchmedia_form']);
        $form->addFieldsetOpen();
        $form->addTextInput('namespace', 'Namespace to download')->attrs([
            'inputmode' => 'verbatim',
            'pattern' => '[a-z0-9_:/;\.]+',
            'placeholder' => 'name:space',
            'title' => 'Valid namespaces can consist of lowercase letters, numbers and the _ and : characters',
            'required' => 'required',
            'autofocus' => 'autofocus,'
        ]);
        $form->addHTML('<br />');
        $form->addRadioButton('mediatypes', 'All types of Media')->val('all')->attr('required', 'required');
        $form->addRadioButton('mediatypes', 'Only Windows-File-Shares')->val('windows-shares')->attrs([
            'required' => 'required',
            'checked' => 'checked',
        ]);
        $form->addRadioButton('mediatypes', 'Only common media files')->val('common')->attr('required', 'required');
        $form->addButton('submit', 'Download');
        $form->addFieldsetClose();

        $doc .= '<div id="plugin_fetchmedia_page">';
        $doc .= $form->toHTML();
        $doc .= '<div id="fetchmedia_results"></div>';
        $doc .= '</div>';
        echo $doc;
    }
}

// vim:ts=4:sw=4:et:
