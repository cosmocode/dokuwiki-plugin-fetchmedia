<?php
/**
 * DokuWiki Plugin fetchmedia (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr, Michael Große <dokuwiki@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_fetchmedia_ajax extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     *
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax');
    }

    /**
     *
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     */
    public function handle_ajax(Doku_Event $event, $param) {
        $call = 'plugin_fetchmedia';
        if (0 !== strpos($event->data, $call)) {
            return;
        }

        if (!auth_isadmin()) {
            return;
        }
        $event->preventDefault();
        $event->stopPropagation();

        global $INPUT, $conf;
        $action = substr($event->data, strlen($call)+1);

        header('Content-Type: application/json');
        try {
            $result = $this->executeAjaxAction($action);
        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage() . ' ' . basename($e->getFile()) . ':' . $e->getLine()
            );
            if ($conf['allowdebug']) {
                $result['stacktrace'] = $e->getTraceAsString();
            }
            http_status(500);
        }

        echo json_encode($result);
    }

    protected function executeAjaxAction($action) {
        global $INPUT;
        switch ($action) {
            case 'getExternalMediaLinks':
                $namespace = $INPUT->str('namespace');
                $type = $INPUT->str('type');
                return $this->findExternalMediaFiles($namespace, $type);
            case 'downloadExternalFile':
                $page = $INPUT->str('page');
                $link = $INPUT->str('link');
                return $this->downloadExternalFile($page, $link);
                break;
            default:
                throw new Exception('FIXME invalid action');
        }
    }

    public function downloadExternalFile($page, $link) {
        // check that link is on page

        $fn = $this->constructFileName($link);
        $id = getNS(cleanID($page)) . ':' . $fn;

        // download file
        $res = fopen($link, 'rb');
        if (!($tmp = io_mktmpdir())) return false;
        $path = $tmp.'/'.md5($id);
        $target = fopen($path, 'wb');
        $realSize = stream_copy_to_stream($res, $target);
        fclose($target);
        fclose($res);

        // check if download was successful
        dbglog($realSize === false ? 'failure' : 'success', __FILE__ . ': ' . __LINE__);

        list($ext,$mime) = mimetype($id);
        $file = [
            'name' => $path,
            'mime' => $mime,
            'ext' => $ext,
        ];
        $mediaID = media_save($file, $id, true, auth_quickaclcheck($id), 'rename');
        dbglog($mediaID, __FILE__ . ': ' . __LINE__);
        if (!is_string($mediaID)) {
            return;
        }

        // report status?

        // replace link
        $text = rawWiki($page);
        $newText = str_replace($link, $mediaID, $text);

        // create new page revision
        if ($text !== $newText) {
            saveWikiText($page, $newText, 'File ' . hsc($link) . ' downloaded by fetchmedia plugin');
        }


        // report ok
    }

    /**
     * @param $link
     *
     * @return string
     */
    public function constructFileName($link) {
        $urlFNstart = strrpos($link, '/') + 1;
        $windosFNstart = strrpos($link, '\\') + 1;
        $fnStart = max($urlFNstart, $windosFNstart);
        return substr($link, $fnStart);
    }

    public function findExternalMediaFiles($namespace, $type) {
        $pageresults = [];
        $basedir = dirname(wikiFN(cleanID($namespace) . ':start'));
        search($pageresults, $basedir, 'search_allpages', []);
        $mediaLinks = [];
        $instructionNames = [];
        if ('all' == $type || 'windows-shares' == $type) {
            $instructionNames[] = 'windowssharelink';
        }
        if ('all' == $type || 'common' == $type) {
            $instructionNames[] = 'externalmedia';
        }
        if (empty($instructionNames)) {
            return [];
        }
        foreach ($pageresults as $page) {
            $pagename = cleanID($namespace) . ':' . $page['id'];
            $ins = p_cached_instructions(wikiFN($pagename));

            $mediaLinkInstructions = $this->searchInstructions($ins, $instructionNames);
            $mediaLinks[$pagename] = [];
            foreach ($mediaLinkInstructions as $mediaLinkInstruction) {
                if ($mediaLinkInstruction[0] == 'windowssharelink') {
                    $mediaLinks[$pagename][] = $mediaLinkInstruction[1][0];
                } elseif (filter_var($mediaLinkInstruction[1][0], FILTER_VALIDATE_URL)) {
                    $mediaLinks[$pagename][] = $mediaLinkInstruction[1][0];
                }
            }
        }
        $mediaLinks = array_filter($mediaLinks);
        return $mediaLinks;
    }


    /**
     * FIXME: ensure that this also catches media-links
     *
     * @param $instructions
     * @param $searchString
     *
     * @return array
     */
    protected function searchInstructions($instructions, array $searchStringList) {
        $results = [];
        foreach ($instructions as $instruction) {
            if (in_array($instruction[0], $searchStringList)) {
                $results[] = $instruction;
            }
        }
        return $results;
    }
}
