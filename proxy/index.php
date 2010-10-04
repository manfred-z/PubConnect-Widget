<?php

/**
 * Copyright 2010 SURFfoundation
 * 
 * In licentie gegeven krachtens de EUPL, versie 1.1 of –
 * zodra deze worden goedgekeurd door de Europese Commissie
 * - opeenvolgende versies van de EUPL (de "licentie");
 * U mag dit werk niet gebruiken, behalve onder de
 * voorwaarden van de licentie.
 * U kunt een kopie van de licentie vinden op:
 * 
 * http://ec.europa.eu/idabc/eupl5
 * 
 * Tenzij dit op grond van toepasselijk recht vereist is
 * of schriftelijk is overeengekomen, wordt software
 * krachtens deze licentie verspreid "zoals deze is",
 * ZONDER ENIGE GARANTIES OF VOORWAARDEN,
 * noch expliciet noch impliciet.
 * Zie de licentie voor de specifieke bepalingen voor
 * toestemmingen en beperkingen op grond van de licentie.
 */
		
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/library'),
    get_include_path(),
)));

include "Zend/Http/Client.php";
include "Zend/Json.php";
include "Zend/Db.php";
include "Zend/Db/Adapter/Pdo/Mysql.php";
include "settings.php";	

header("Expires: Mon, 01 Jan 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
				
//url uitelkaar halen
$args = explode('/', $_GET['route']);

foreach ($_GET as $key => $arg) {
	if ($key == 'route') {			
		$request = array();
		$request["type"] = $args[0];
		$request[$args[1]] = $args[2];
		$request["output"] = $args[3];

		for ($i = 4; $i < count($args); $i++) {
			$request[$args[$i]] = $args[++$i];
		}
	} else {
		$request[$key] = $arg;
	}
}

if ($request["type"] == "institutions") {
	echo Zend_Json::encode($institutions);
	die();
}

//request url weer inelkaar stoppen
$path = null;
$args = array();
foreach ($_GET as $key => $arg) {
	if ($key == 'route') {
		$path = $arg;
		continue;
	} else if($key == 'institution') {
		continue;
	}

	$args[] = $key."=".$arg;
}
if ($args)
	$path .= "?".implode('&', $args);

//neem default de eerste institution, als er geen is meegegeven
$institution = null;
if ($request['institution'] == 'null') {
	reset($institutions);
	$institution = key($institutions);
} else {
	$institution = $request['institution'];
}

$url = $institutions[$institution]['url'] . $path;

//$client = new Zend_Rest_Client($url);
$client = new Zend_Http_Client($url);
$response = $client->request();

//zet publications in de db
if ($request["output"] == "results") {
	$publications = Zend_Json::decode($response->getBody());
	
	// voeg automatisch de APA toe aan publicaties
	/*$apa = array();
	foreach ($publications as &$publication) {
		$url = $institutions[$institution]['url'] . "result/resultid/".intval($publication['id'])."/apa";
		$client = new Zend_Http_Client($url);
		$response = $client->request();
		$data = Zend_Json::decode($response->getBody());
		$apa[] = array(
						"id" => intval($publication['id']),
						"result" => $data['result']
						);
	}
	*/

	/*
	//doe hier iets met de data, je weet nu de dai, institution etc..
	$db = new Zend_Db_Adapter_Pdo_Mysql(array(
		'host'     => '127.0.0.1',
		'username' => 'metiswidget_demo',
		'password' => '9EAUxaJ1',
		'dbname'   => 'metiswidget_demo'
	));
	$db->setFetchMode(Zend_Db::FETCH_OBJ);

	foreach ($publications as &$publication) {
		$pubRow = $db->fetchRow("select * from publication where publication_id = ? and institution_id = ?", array($publication['id'], $institution));

		if (!$pubRow) {
			$data = array(
				'publication_id'	=> $publication['id'],
				'dai'				=> $request['dai'],
				'institution_id' 	=> $institution
			);
			$db->insert('publication', $data);
			$publication['new'] = 1;
		}		
	}
	*/
	echo Zend_Json::encode($publications);
} else {
	echo $response->getBody();
}