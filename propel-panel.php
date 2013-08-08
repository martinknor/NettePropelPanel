<?php

namespace Addons\Propel\Diagnostics;

use Nette\Diagnostics\Debugger;
use \Propel;

class PropelPanel extends \Nette\Object implements \Nette\Diagnostics\IBarPanel, \BasicLogger {

    /** @var int logged time */
    public $totalTime = 0;

    /** @var int */
    public $maxPriority = 0;

    /** @var array */
    public $queries = array();

    /** @var array */
    public $priorities = array();

    const name = 'propel';

    public function emergency($m) {
        $this->log($m, Propel::LOG_EMERG);
    }

    public function alert($m) {
        $this->log($m, Propel::LOG_ALERT);
    }

    public function crit($m) {
        $this->log($m, Propel::LOG_CRIT);
    }

    public function err($m) {
        $this->log($m, Propel::LOG_ERR);
    }

    public function warning($m) {
        $this->log($m, Propel::LOG_WARNING);
    }

    public function notice($m) {
        $this->log($m, Propel::LOG_NOTICE);
    }

    public function info($m) {
        $this->log($m, Propel::LOG_INFO);
    }

    public function debug($m) {
        $this->log($m, Propel::LOG_DEBUG);
    }

    public function log($message, $priority = null) {
        Debugger::timer(self::name);
        $this->queries[] = array($message, $this->priorityToText($priority));

        # set priority count
        isset($this->priorities[$priority]) ? null : $this->priorities[$priority] = 0;
        $this->priorities[$priority] ++;

        # set max priority
        if (!isset($this->maxPriority) || $priority > $this->maxPriority)
            $this->maxPriority = $priority;

        $keys = array_keys($this->queries);
        $key = end($keys);
        $this->queries[$key][2] = $queryTime = Debugger::timer(self::name);
        $this->totalTime += $queryTime;
    }

    private function priorityToText($priority) {
        switch ($priority) {
            case Propel::LOG_EMERG:
                return '<span style="color: red">Emergency</span>';
                break;
            case Propel::LOG_ALERT:
                return '<span style="color: red">Alert</span>';
                break;
            case Propel::LOG_CRIT:
                return '<span style="color: red">Critical</span>';
                break;
            case Propel::LOG_ERR:
                return '<span style="color: red">Error</span>';
                break;
            case Propel::LOG_WARNING:
                return '<span style="color: orange">Warning</span>';
                break;
            case Propel::LOG_NOTICE:
                return '<span style="color: green">Notice</span>';
                break;
            case Propel::LOG_INFO:
                return '<span style="color: blue">Info</span>';
                break;
            case Propel::LOG_DEBUG:
                return '<span style="color: grey">Debug</span>';
                break;
        }
    }

    public function getTab() {
        return '<span title="Propel">'
                . '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC" />'
                . count($this->queries) . ' queries'
                . ($this->totalTime ? ' / ' . sprintf('%0.1f', $this->totalTime * 1000) . 'ms' : '')
                . (isset($this->maxPriority) ? ' / ' . $this->priorityToText($this->maxPriority) : '')
                . '</span>';
    }

    /**
     * @param array
     * @return string
     */
    protected function processQuery(array $query) {
        $s = '';
        list($sql, $priority, $time) = $query;

        $s .= '<tr><td>' . sprintf('%0.3f', $time * 1000);
        $s .= '</td><td class="nette-PropelPanel-sql">' . \Nette\Database\Helpers::dumpSql($sql);
        $s .= '</td><td>' . $this->priorityToText($priority) . '</tr>';

        return $s;
    }

    protected function renderStyles() {
        return '<style> #nette-debug td.nette-PropelPanel-sql { background: white !important }
  		#nette-debug .nette-PropelPanel-source { color: #BBB !important }
			#nette-debug nette-PropelPanel tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>';
    }

    /**
     * @param array
     * @return string
     */
    protected function processPriorities(array $priorities) {
        if (empty($priorities))
            return '';
        $s = '<table><tr>';
        foreach ($priorities as $priority => $count) {
            $s .= '<td>' . $this->priorityToText($priority) . '</td><td>' . $count . '</td>';
        }
        $s .= '</tr></table><br/>';
        return $s;
    }

    /**
     * @param \PDOException
     * @return array
     */
    public function renderException($e) {
        if ($e instanceof \PDOException && count($this->queries)) {
            $s = '<table><tr><th>Time&nbsp;ms</th><th>SQL</th><th>Type</th></tr>';
            $s .= $this->processQuery(end($this->queries));
            $s .= '</table>';
            return array(
                'tab' => 'SQL',
                'panel' => $this->renderStyles() . '<div class="nette-inner nette-PropelPanel">' . $s . '</div>',
            );
        } else {
            \Nette\Database\Diagnostics\ConnectionPanel::renderException($e);
        }
    }

    public function getPanel() {
        $s = '';
        foreach ($this->queries as $query) {
            $s .= $this->processQuery($query);
        }

        return empty($this->queries) ? '' :
                $this->renderStyles() .
                '<h1>Queries: ' . count($this->queries) . ($this->totalTime ? ', time: ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : '') . '</h1>' .
                $this->processPriorities($this->priorities) .
                '<div class="nette-inner nette-PropelPanel">
			<table>
			<tr><th>Time&nbsp;ms</th><th>SQL</th><th>Type</th></tr>' . $s . '
			</table>
			</div>';
    }

    /**
     * @return ConnectionPanel
     */
    public static function register() {
        $panel = new static;
        if (Debugger::$bar) {
            Debugger::$bar->addPanel($panel);
        }
        Debugger::$blueScreen->addPanel(array($panel, 'renderException'));
        return $panel;
    }

}
