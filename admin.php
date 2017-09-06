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

        $doc .= $this->locale_xhtml('intro');

        $form = new \dokuwiki\Form\Form(['id' => 'fetchmedia_form']);
        $form->addFieldsetOpen();
        $form->addTextInput('namespace', $this->getLang('label: namespace input'))->attrs([
            'inputmode' => 'verbatim',
            'pattern' => '[-a-z0-9_:/;\.]+',
            'placeholder' => 'name:space',
            'title' => $this->getLang('title: namespace input hint'),
            'required' => 'required',
            'autofocus' => 'autofocus,'
        ]);
        $form->addHTML('<br />');
        $radioCommon = $form->addRadioButton('mediatypes', $this->getLang('label: common media only'))->val('common')->attr('required', 'required');
        $radioWin = $form->addRadioButton('mediatypes', $this->getLang('label: windows shares'))->val('windows-shares')->attr('required', 'required');
        $radioAll = $form->addRadioButton('mediatypes', $this->getLang('label: all media'))->val('all')->attr('required', 'required');

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $radioAll->attr('disabled', 'disabled');
            $radioWin->attr('disabled', 'disabled');
            $radioCommon->attr('checked', 'checked');
        } else {
            $radioWin->attr('checked', 'checked');
        }

        $form->addButton('submit', $this->getLang('label: button search'));
        $form->addFieldsetClose();

        $doc .= '<div id="plugin_fetchmedia_page">';
        $doc .= $form->toHTML();
        $doc .= '<div id="fetchmedia_results"></div>';
        $doc .= '</div>';
        echo $doc;
    }
}

// vim:ts=4:sw=4:et:
