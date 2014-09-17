<?php

require dirname(__FILE__) . "/vendor/autoload.php";
require dirname(dirname(dirname(dirname(__FILE__)))) . "/init.php";

function onoffval($v) {
	return $v === "on" ? true : false;
}

class Web extends Slim\Slim {
		function makeError($err, $code = 500) {
			$this->response->status($code);
			$this->response->headers->set("Content-Type", "application/json");
			echo json_encode(array("errors" => array($err)));
		}
		function authed() {
			return isset($_SESSION["adminid"]);
		}
}

$app = new Web(array("debug" => false));
$app->add(new Slim\Middleware\ContentTypes());

$app->notFound(function() use ($app) {
	echo "Requested resource not found";
});

$app->get("/", function() use ($app) {
	$app->redirect("/");
});

$app->get("/plans", function() use ($app) {
	if(!$app->authed()) {
		$app->makeError("Access denied");
		return;
	}

	$qres = mysql_query("SELECT * FROM `tblproducts` p WHERE p.`type` IN ( 'hostingaccount','reselleraccount') and p.`configoption1` != '' and p.`servertype` = 'cpanel';");
	if($qres === FALSE) {
		$app->makeError("Unable to get the list of hosting packages");
		return;
	}
	$plans = array();
	while(($row = mysql_fetch_assoc($qres)) !== FALSE) {
		$plans[] = array(
			"id" => intval($row["id"]),
			"label" => $row["name"],
			"description" => $row["description"],
			"name" => $row["configoption1"],
			"limits" => array(
				"ftp" => intval($row["configoption2"]),
				"disk" => intval($row["configoption3"]),
				"email" => intval($row["configoption4"]),
				"bandwidth" => intval($row["configoption5"]),
				"dedicated_ip" => onoffval($row["configoption6"]),
				"ssh" => onoffval($row["configoption7"]),
				"databases" => intval($row["configoption8"]),
				"cgi" => onoffval($row["configoption9"]),
				"subdomains" => intval($row["configoption10"]),
				"frontpage" => onoffval($row["configoption11"]),
				"parked" => intval($row["configoption12"]),
				"addon" => intval($row["configoption14"]),
				"overage" => strval($row["overagesenabled"][0]) === "1"
			)
		);
	}
	echo json_encode($plans);
});

$app->post("/update", function() use ($app) {
	if(!$app->authed()) {
		$app->makeError("Access denied");
		return;
	}
	$body = $app->request()->getBody();

	$qres = mysql_query("SELECT s.* FROM `tblservers` s WHERE s.`type` = 'cpanel' and s.`disabled` = 0;");
	if($qres === FALSE) {
		$app->makeError("Unable to get list of cPanel servers");
		return;
	}
	$servers = array();
	while(($row = mysql_fetch_assoc($qres)) !== FALSE) {
		$servers[] = array(
			"id" => intval($row["id"]),
			"name" => $row["name"],
			"hostname" => $row["hostname"],
			"username" => $row["username"],
			"accesshash" => $row["accesshash"]

		);
	}

	$plan = $body["plan"];
	$changes = array();

	// Update each WHM package
	foreach($servers as $server) {
		$cpanel = new \Gufy\CpanelPhp\Cpanel(array(
			"host" => "https://" . $server["hostname"] . ":2087",
			"username" => $server["username"],
			"password" => $server["accesshash"],
			"auth_type" => "hash"	
		));
		$package = $cpanel->getpkginfo(array("pkg" => $plan["name"], "api.version" => 1));
		if($package["metadata"]["result"] !== 1) {
			continue;
		}
		$package = $package["data"]["pkg"];
		$edits = array();
		if($plan["limits"]["overage"] == 1) {
			if($package["BWLIMIT"]) $edits["bwlimit"] = "unlimited";
			if($package["QUOTA"]) $edits["quota"] = "unlimited";
		} else {
			if($package["BWLIMIT"] != $plan["limits"]["bandwidth"]) $edits["bwlimit"] = $plan["limits"]["bandwidth"];
			if($package["QUOTA"] != $plan["limits"]["disk"]) $edits["quota"] = $plan["limits"]["disk"];
		}
		if(count($edits) > 0) {
			$keys = array_keys($edits);
			$edits["name"] = $plan["name"];
			$edits["api.version"] = 1;
			$result = $cpanel->editpkg($edits);
			if($result["metadata"]["result"] !== 1) {
				$app->makeError($result);
				continue;
			} 
			$changes[] = $plan["name"] . " on " . $server["hostname"] . " updated: " . implode(",", $keys);

		}
	}
	// Update WHMCS
	$uPlanId	= intval($plan["id"]);
	$uDisk		= intval($plan["limits"]["disk"]);
	$uBw		= intval($plan["limits"]["bandwidth"]);
	$uDesc		= mysql_real_escape_string($plan["description"]);
	$uOverages	= $plan["limits"]["overage"] == 1 ? "1,MB,MB" : "";

	$update = mysql_query("UPDATE tblproducts SET configoption3 = '$uDisk', configoption5 = '$uBw', description = '$uDesc', overagesenabled = '$uOverages', overagesdisklimit = $uDisk, overagesbwlimit = $uBw WHERE id = $uPlanId");
	if($update === FALSE) {
		$changes[] = "*** WARNING: Changes could not be synced to WHMCS - please resolve this manually! ***";
		$changes[] = mysql_error();
	}
	echo json_encode($changes);
});

$app->run();

?>
