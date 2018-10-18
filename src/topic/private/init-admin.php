<?php
namespace nt;
/**
 *
 * Init for Admin
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


define('NT_PRIVATE', true);
if (!defined('NT_LANG')) define('NT_LANG', 'ja');

require_once(__DIR__ . '/../core/define.php');
require_once(__DIR__ . '/../core/function.php');
require_once(__DIR__ . '/../core/class-store.php');
require_once(__DIR__ . '/class-session.php');

reject_direct_access(__FILE__, 2);
set_locale_setting();

$nt_res     = load_resource();
$nt_q       = prepare_query(['mode' => '', 'id' => 0, 'page' => 1, 'posts_per_page' => 10, 'cat' => '', 'date' => '', 'date_bgn' => '', 'date_end' => '']);
$nt_store   = new Store(NT_URL_POST);
$nt_session = new Session();

if (!$nt_session->check($nt_q)) {
	header('Location: ' . NT_URL_PRIVATE . 'login.php');
	exit(1);
}
