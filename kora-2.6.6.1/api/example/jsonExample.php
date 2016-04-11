<?php

require_once( __DIR__ . '/config.php' );

$searchToken = SEARCH_TOKEN;
$kid = EXAMPLE_KID;

if (isset($_REQUEST['searchToken']))
	$searchToken = $_REQUEST['searchToken'];
if (isset($_REQUEST['kid']))
	$kid = $_REQUEST['kid'];

$manager = new \KoraORM\KoraManager($searchToken);

$book = $manager->getByKid($kid);

header('Content-type: application/json');
echo json_encode($book);