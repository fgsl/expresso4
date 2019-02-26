<?php
use Expresso\Core\GlobalService;

/**
 *
 * @package Setup
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 * @copyright Copyright (c) 2019 FGSL (http://www.fgsl.eti.br)
 *           
 */
class ErrorSys
{

    /**
     * *************************\
     * Instance Variables...
     * *
     * \**************************
     */
    private $severity = 'E';

    private $code = 'Unknown';

    private $msg = 'Unknown error';

    private $parms = array();

    private $ismsg = 0;

    private $timestamp;

    private $fname;

    private $line;

    private $app;

    private $public_functions = array();

    // Translate Message into Language
    function langmsg()
    {
        return lang($this->msg, $this->parms);
    }

    function error_sys($parms)
    {
        if ($parms == '') {
            return;
        }
        $etext = $parms['text'];
        $parray = Array();
        for ($counter = 1; $counter <= 10; $counter ++) {
            $str = 'p_' . $counter;
            if (isset($parms[$str]) && ! empty($parms[$str])) {
                $parray[$counter] = $parms[$str];
            } else {
                $str = 'p' . $counter;
                if (isset($parms[$str]) && ! empty($parms[$str])) {
                    $parray[$counter] = $parms[$str];
                }
            }
        }
        $fname = $parms['file'];
        $line = $parms['line'];
        if (preg_match('/([DIWEF])-([[:alnum:]]*)\, (.*)/i', $etext, $match)) {
            $this->severity = strtoupper($match[1]);
            $this->code = $match[2];
            $this->msg = trim($match[3]);
        } else {
            $this->msg = trim($etext);
        }

        @reset($parray);
        while (list ($key, $val) = each($parray)) {
            $this->msg = preg_replace("/%$key/", "'" . $val . "'", $this->msg);
        }
        @reset($parray);

        $this->timestamp = time();
        $this->parms = $parray;
        $this->ismsg = $parms['ismsg'];
        $this->fname = $fname;
        $this->line = $line;
        $this->app = GlobalService::get('phpgw_info')['flags']['currentapp'];

        if (! $this->fname or ! $this->line) {
            GlobalService::get('phpgw')->log->error(array(
                'text' => 'W-PGMERR, Programmer failed to pass __FILE__ and/or __LINE__ in next log message',
                'file' => __FILE__,
                'line' => __LINE__
            ));
        }

        GlobalService::get('phpgw')->log->errorstack[] = $this;
        if ($this->severity == 'F') {
            // This is it... Don't return
            // do rollback!
            // Hmmm this only works if UI!!!!
            // What Do we do if it's a SOAP/XML?
            echo "<Center>";
            echo "<h1>Fatal Error</h1>";
            echo "<h2>Error Stack</h2>";
            echo GlobalService::get('phpgw')->log->astable();
            echo "</center>";
            // Commit stack to log
            GlobalService::get('phpgw')->log->commit();
            GlobalService::get('phpgw')->common->phpgw_exit(True);
        }
    }
}