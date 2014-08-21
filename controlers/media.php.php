<?php
$CMedia = new media;

if (!isset($_GET['action'])) {
	NFW::i()->stop(NFW::i()->lang['Errors']['Bad_request'], 'error-page');
}

if (!$CMedia->action($_GET['action'])) {
	NFW::i()->stop($CMedia->last_msg);
}