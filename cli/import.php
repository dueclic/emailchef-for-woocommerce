<?php

require_once("../../../../wp-load.php");

if (php_sapi_name() !== 'cli'){
	die("I can be executed from CLI only :(");
}



echo "## CLI IMPORT TOOL\n";

$wcec = WCEC();

$list = get_option("wc_emailchef_list");

echo "=> eMailChef List: ".$list."\n";

$wcec->emailchef()->upsert_integration($list);
$wcec->emailchef()->sync_list($list);
$wcec->log(sprintf(__("Synchronization and custom fields creation for eMailChef list %d",
	"emailchef-for-woocommerce"), $list));
