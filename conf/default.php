<?php
/**
 * Defaults for the fileindex plugin.
 * @author Jan Wessely <info@jawe.net>
 */

$conf['rootURL'] = DOKU_URL . "lib/plugins/fileindex/files/";
$conf['rootPath'] = DOKU_INC . "lib/plugins/fileindex/files/";
$conf['chrootPath'] = "";
$conf['defaultPath'] = "";
$conf['showDotFiles'] = 0;
$conf['showIcons'] = 1;
$conf['iconPath'] = DOKU_INC . "lib/images/fileicons/";
$conf['iconURL'] = DOKU_URL . "lib/images/fileicons/";
$conf['folderIcon'] = DOKU_URL . "lib/plugins/fileindex/images/book-closed.png";
$conf['fileIcon'] = DOKU_URL . "lib/plugins/fileindex/images/file-generic.png";
$conf['upfolderIcon'] = DOKU_URL . "lib/plugins/fileindex/images/up.png";
$conf['indexFiles'] = "index.html index.php";

//Setup VIM: ex: et ts=4 sw=4 enc=utf-8 :
?>
