<?php
namespace nt;
/**
 *
 * Init for Private
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-28
 *
 */


 define('NT_PRIVATE', true);

require_once(__DIR__ . '/../core/define.php');
require_once(__DIR__ . '/../core/function.php');
require_once(__DIR__ . '/../core/class-store.php');
require_once(__DIR__ . '/class-session.php');
require_once(__DIR__ . '/class-media.php');

reject_direct_access(NT_URL_HOST, __FILE__, 2);
set_locale_setting();

$nt_config  = load_config(NT_DIR_DATA);
$nt_res     = load_resource(NT_DIR_PRIVATE, $nt_config['lang_private']);
$nt_q       = prepare_query(['mode' => '', 'id' => 0, 'page' => 1, 'posts_per_page' => 10, 'cat' => '', 'date' => '', 'date_bgn' => '', 'date_end' => '']);
$nt_store   = new Store(NT_URL_POST, NT_DIR_POST, NT_DIR_DATA, $nt_config, NT_URL_PRIVATE);
$nt_session = new Session(NT_URL_PRIVATE, NT_DIR_POST, NT_DIR_ACCOUNT, NT_DIR_SESSION);

if (!$nt_session->check()) {
	header('Location: ' . NT_URL_PRIVATE . 'login.php');
	exit(1);
}
