<?php 
/** 
 * Displays directory contents, like apache's mod_autoindex 
 *  
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) 
 * @author     Jan Wessely <info@jawe.net>   
 */ 

if(!defined('DOKU_INC')) die(); 

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/'); 
require_once(DOKU_PLUGIN.'syntax.php'); 

/** 
 * All DokuWiki plugins to extend the parser/rendering mechanism 
 * need to inherit from this class 
 */ 
class syntax_plugin_fileindex extends DokuWiki_Syntax_Plugin { 
	
	/** 
	 * return some info 
	 */ 
	function getInfo(){ 
		return array( 
			'author' => 'Jan Wessely', 
			'email'  => 'info@jawe.net', 
			'date'   => '2008-04-02', 
			'name'   => 'Fileindex (Syntax Plugin)', 
			'desc'   => 'Displays directory contents, like apache\'s mod_autoindex', 
			'url'    => 'http://jawe.net/wiki/dev/dokuwiki/fileindex', 
		); 
	} 
	
	function getType() { return 'substition'; } 
	function getPType(){ return 'block'; }
	function getSort() { return 1; }
	
	function connectTo($mode) { 
		$this->Lexer->addSpecialPattern('{{fileindex(?:>[a-zA-Z0-9_\-/\.]+)?}}', $mode, 'plugin_fileindex');
	}
	
	/** 
	 * Handle the match 
	 */ 
	function handle($match, $state, $pos, &$handler) {
		$this->_check_conf_paths();
        // extract target page from match pattern
        $path = substr($match,strlen('{{fileindex'),-2);
        if (!empty($path) && substr($path, 0, 1) == '>') $path = substr($path, 1);
#        error_log(__FILE__ . ":" . __LINE__ . ": path=$path, conf=" . print_r($this->conf, true));
        return array($path);
	}

	/**
	 * Create output and metadata entry
	 */
	function render($mode, &$renderer, $data) {
        $renderer->info['cache'] = false;
        $this->_check_conf_paths();
		$path = $data[0];

		if (isset($_GET["path"]) && (empty($_GET["path"]) || strpos($_GET["path"], "..") === false)) {
            if ($this->getConf('chrootPath') == "" || (strpos($_GET["path"], $this->getConf('chrootPath')) === 0)
                    && file_exists($this->getConf('rootPath') . $_GET['path'])) {
                $path = $_GET["path"];
            }
		}
        if (empty($path)) $path = $this->getConf('defaultPath');
		$fullpath = $this->getConf('rootPath') . $path;

#        error_log(__FILE__ . ":" . __LINE__ . ": mode=$mode, path=$path, conf=" . print_r($this->conf, true));

		if ($mode == 'xhtml') {
			if (is_dir($fullpath)) {
                $renderer->doc .= $this->_render_fileindex($this->_addTrailingSlash($path),
                        $this->_addTrailingSlash($fullpath));
			}
			return true;
		} elseif ($mode == 'metadata') {
			return true;
		}
		return false;
	}

	/*
	 * Sanitize and validate all paths
	 */
	function _check_conf_paths() {
		$ok = $this->_check_conf_path('rootPath');
		$ok = $this->_check_conf_path('chrootPath', true) && $ok;
		$ok = $this->_check_conf_path('defaultPath', true ) && $ok;
		if ($this->conf['chrootPath'] != "" && strpos($this->conf['defaultPath'], $this->conf['chrootPath']) !== 0) {
   			error_log($this->getLang('err_invalid_defaultPath') . " defaultPath=" . $this->conf['defaultPath'] . ", chrootPath=" . $this->conf['chrootPath']);
			$this->conf['defaultPath'] = $this->conf['chrootPath'];
		}
		return $ok;
	}

	function _check_conf_path($confname, $is_relative = false) {
		$path = $this->conf[$confname] = $this->_addTrailingSlash($this->getConf($confname));
		if (!empty($path) && !is_dir(($is_relative ? $this->getConf('rootPath') : '') . $path)) {
   			error_log(sprintf($this->getLang('err_invalid_path'), htmlspecialchars($path), $confname));
			return false;
		}
		return true;
	}
	
	function _get_index_file($fullpath) {
        if ($this->getConf('indexFiles') != '') {
            $indexFiles = split('[ ;,]+', $this->getConf('indexFiles'));
            $fullpath = $this->_addTrailingSlash($fullpath);
            foreach($indexFiles as $f) {
                if(is_file($fullpath . $f)) {
                    return $f;
                }
            }
        }
        return '';
	}

	function _render_fileindex($path, $fullpath) {
		global $ID;
		global $conf;

		$dirs = array();
		$files = array();
		$icons = array();
		$this->_list_files($fullpath, $dirs, $files, $icons);

        $numrows = 3;
		ob_start();
	?>
		<table>
			<caption> <?php echo $this->getLang('contents') . ($path ? " " . $this->getLang('of') . " $path" : "") ?></caption>
		<thead>
		<tr><th><?php echo $this->getLang('file'); ?></th><th><? echo $this->getLang('date'); ?></th><th><?php echo $this->getLang('size'); ?></th></tr>
		</thead>
		<tbody>
		<?php

		$img = $this->getConf('showIcons') ? "<img src='" . $this->getConf('upfolderIcon') . "' border='0' alt='" . $this->getLang('parent_dir') . "' />&nbsp;" : "";

		if ((!empty($path) && $this->getConf('chrootPath') == "") || substr($path, strlen($this->getConf('chrootPath')))) {
			$parent = substr($path, 0, strrpos(substr($path, 0, strlen($path) - 1), "/"));
			echo "<tr><td><a href='" . wl($ID, "path=" . rawurlencode($parent)) . "' title='" . htmlspecialchars($parent) . "'>$img" . $this->getLang('parent_dir') . "</a></td>";
			echo "<td>" . strftime($conf['dformat'], filemtime($fullpath . $entry)) . "</td>";
			echo "<td>&nbsp;</td></tr>\n";
		}

        if (empty($dirs) && empty($files)) {
            echo "<tr><td colspan='$numrows'>";
            echo $this->getLang('nofiles');
            echo "</td></tr>\n";
        }

		$img = $this->getConf('showIcons') ? "<img src='" . $this->getConf('folderIcon') . "' border='0' alt='" . $this->getLang('directory') . "' />&nbsp;" : "";

		foreach ($dirs as $entry) {
            $indexFile = $this->_get_index_file($fullpath . $entry);
            if (!empty($indexFile)) {
                $dirURI = htmlspecialchars($this->getConf('rootURL') . $path . $this->_addTrailingSlash($entry));
            } else {
                $dirURI = wl($ID, "path=" . rawurlencode($path . $entry));
            }
			echo "<tr>";
			echo "<td><a href='" . $dirURI . "' title='" . htmlspecialchars($path . $entry) . "'>$img$entry</a></td>";
			echo "<td>" . strftime($conf['dformat'], filemtime($fullpath . $entry)) . "</td>";
			echo "<td>&nbsp;</td>";
			echo "</tr>\n";
		}

		foreach ($files as $entry) {
			$img = "";

			if ($this->getConf('showIcons')) {
				$icon = $this->getConf('fileIcon');
				$dot = strrpos($entry, '.');
				if ($dot !== false) {
					$ext = substr($entry, max($dot + 1, count($entry) - 1));
					if($icons[$ext]) {
						$icon = $icons[$ext];
					}
				}
				$img = "<img src='$icon' border='0' alt='" . $this->getLang('file') . "' />&nbsp;";
			}
            $uri = $this->getConf('rootURL') . htmlspecialchars($path . $entry);
			echo "<tr>";
            echo "<td><a href='$uri' title='$uri'>$img$entry</a></td>";
			echo "<td>" . strftime($conf['dformat'], filemtime($fullpath . $entry)) . "</td>";
			echo "<td align='right'>" . $this->_formatbytes(filesize($fullpath . $entry), 3, "IEC") . "</td>";
			echo "</tr>\n";
		}

		?>
		</tbody>
		</table>
	<?php
		return ob_get_clean();
	}

	function _list_files($fullpath, &$dirs, &$files, &$icons) {
		$d = dir($fullpath);
		while (false !== ($entry = $d->read())) {
			if ($entry != "." && $entry != "..") {
				if ($this->getConf('showDotFiles') || substr($entry, 0, 1) != ".") {
					if (is_dir($fullpath . $entry)) {
						$dirs[] = $entry;
					} else {
						$files[] = $entry;
					}
				}
			}
		}
		$d->close();

		$d = dir($this->getConf('iconPath'));
		while (false !== ($entry = $d->read())) {
			$f = $this->getConf('iconPath') . $entry;
			if (is_file($f)) {
				$dot = strrpos($entry, '.');
				if ($dot !== false) {
					$icons[substr($entry, 0, $dot)] = $this->getConf('iconURL') . $entry;
				}
			}
		}
		$d->close();

		sort($dirs);
		sort($files);
	}

	function _addTrailingSlash($s) {
		if ($s && substr($s, strlen($s) - 1, 1) != "/") {
			$s .= "/";
		}
		return $s;
	}

	function _formatbytes($val, $digits = 3, $mode = "SI", $bB = "B"){ //$mode == "SI"|"IEC", $bB == "b"|"B"
        $si = array("", "k", "M", "G", "T", "P", "E", "Z", "Y");
        # $iec = array("", "Ki", "Mi", "Gi", "Ti", "Pi", "Ei", "Zi", "Yi");
        $iec = $si;
        switch(strtoupper($mode)) {
            case "SI" : $factor = 1000; $symbols = $si; break;
            case "IEC" : $factor = 1024; $symbols = $iec; break;
            default : $factor = 1000; $symbols = $si; break;
        }
        switch($bB) {
            case "b" : $val *= 8; break;
            default : $bB = "B"; break;
        }
        for($i=0;$i<count($symbols)-1 && $val>=$factor;$i++)
            $val /= $factor;
        $p = strpos($val, ".");
        if($p !== false && $p > $digits) $val = round($val);
        elseif($p !== false) $val = round($val, $digits-$p);
        return round($val, $digits) . " " . $symbols[$i] . $bB;
	}
}

//Setup VIM: ex: et ts=4 sw=4 enc=utf-8 :
?>
