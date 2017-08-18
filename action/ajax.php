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

        if (is_array($result) && isset($result['status'])) {
            http_status($result['status']);
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
                return $this->lockAndDownload($page, $link);
            default:
                throw new Exception('FIXME invalid action');
        }
    }

    protected function lockAndDownload($pageId, $link) {
        $lock = checklock($pageId);
        if ($lock !== false) {
            return ['status' => 409, 'status_text' => sprintf($this->getLang('error: page is locked'), $lock)];
        }
        lock($pageId);
        try {
            $results = $this->downloadExternalFile($pageId, $link);
        } catch (Exception $e) {
            return ['status' => 500, 'status_text' => hsc($e->getMessage())];
        }
        unlock($pageId);
        return $results;
    }

    protected function downloadExternalFile($pageId, $link) {
        // check that link is on page

        $fn = $this->constructFileName($link);
        $id = getNS(cleanID($pageId)) . ':' . $fn;

        // check if file exists
        if (filter_var($link, FILTER_VALIDATE_URL)) {
            // check headers
            $headers = get_headers($link);
            $statusHeaders = array_filter($headers, function ($elem) {
                return strpos($elem, ':') === false;
            });
            $finalStatus = end($statusHeaders);
            list($protocoll, $code, $textstatus) = explode(' ', $finalStatus, 3);
            if ($code >= 400) {
                return ['status' => $code, 'status_text' => $textstatus];
            }
        } else {
            // windows share
            if (!file_exists($link)) {
                return ['status' => 404, 'status_text' => $this->getLang('error: windows share missing')];
            }

            if (is_dir($link)) {
                return ['status' => 422, 'status_text' => $this->getLang('error: windows share is directory')];
            }

            if (!is_readable($link)) {
                return ['status' => 403, 'status_text' => $this->getLang('error: windows share not readable')];
            }
        }

        // download file
        $res = fopen($link, 'rb');
        if ($res === false) {
            return ['status' => 500, 'status_text' => $this->getLang('error: failed to open stream')];
        }
        if (!($tmp = io_mktmpdir())) {
            throw new Exception('Failed to create tempdir');
        };
        $path = $tmp.'/'.md5($id);
        $target = fopen($path, 'wb');
        $realSize = stream_copy_to_stream($res, $target);
        fclose($target);
        fclose($res);

        // check if download was successful
        if ($realSize === false) {
            return ['status' => 500, 'status_text' => $this->getLang('error: failed to download file')];
        }

        list($ext,$mime) = mimetype($id);
        $file = [
            'name' => $path,
            'mime' => $mime,
            'ext' => $ext,
        ];
        $mediaID = media_save($file, $id, false, auth_quickaclcheck($id), 'rename');
        if (!is_string($mediaID)) {
            list($textstatus, $code) = $mediaID;
            return ['status' => 400, 'status_text' => $textstatus];
        }

        // report status?

        // replace link
        $text = rawWiki($pageId);
        $newText = $this->replaceLinkInText($text, $link, $mediaID);

        // create new page revision
        if ($text !== $newText) {
            if (filemtime(wikiFN($pageId)) == time()) {
                $this->waitForTick(true);
            }
            saveWikiText($pageId, $newText, 'File ' . hsc($link) . ' downloaded by fetchmedia plugin');
        }

        // report ok
        return ['status' => 200, 'status_text' => $mediaID];
    }

    /**
     * @param string $text
     * @param string $oldlink
     * @param string $newMediaId
     *
     * @return string the adjusted text
     */
    public function replaceLinkInText($text, $oldlink, $newMediaId) {
        if (filter_var($oldlink, FILTER_VALIDATE_URL)) {
            $type = ['externalmedia'];
        } else {
            $type = ['windowssharelink'];
        }
        $done = false;
        while (!$done) {
            $done = true;
            $ins = p_get_instructions($text);
            $mediaLinkInstructions = $this->searchInstructions($ins, $type);
            foreach ($mediaLinkInstructions as $mediaLinkInstruction) {
                if ($mediaLinkInstruction[1][0] !== $oldlink) {
                    continue;
                }
                $done = false;

                // FIXME: handle spaces for positioning! m(

                $start = $mediaLinkInstruction[2] + 1;
                $end = $mediaLinkInstruction[2] + 1 + strlen($oldlink);
                $prefix = substr($text, 0, $start);
                $postfix = substr($text, $end);
                if (substr($prefix, -2) === '[[') {
                    $prefix = substr($prefix, 0, -2) . '{{';
                    $closingBracketsPos = strpos($postfix, ']]');
                    $postfix = substr($postfix, 0, $closingBracketsPos) . '}}' . substr($postfix,  $closingBracketsPos + 2);
                }
                $text = $prefix . $newMediaId . $postfix;
                break;
            }
        }

        return $text;
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
            $mediaLinks[$pagename] = array_unique($mediaLinks[$pagename]); // ensure we have no duplicates
            $mediaLinks[$pagename] = array_values($mediaLinks[$pagename]); // ensure that the array is correctly numbered 0,1,2,...
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

    /**
     * Waits until a new second has passed
     *
     * The very first call will return immeadiately, proceeding calls will return
     * only after at least 1 second after the last call has passed.
     *
     * When passing $init=true it will not return immeadiately but use the current
     * second as initialization. It might still return faster than a second.
     *
     * This is a duplicate of the code in @see \DokuWikiTest::waitForTick
     *
     * @param bool $init wait from now on, not from last time
     * @return int new timestamp
     */
    protected function waitForTick($init = false) {
        static $last = 0;
        if($init) $last = time();
        while($last === $now = time()) {
            usleep(100000); //recheck in a 10th of a second
        }
        $last = $now;
        return $now;
    }
}
