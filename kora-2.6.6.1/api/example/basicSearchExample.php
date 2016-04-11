<?php

require_once( __DIR__ . '/config.php' );

$manager = new \KoraORM\KoraManager(SEARCH_TOKEN);
$searchManager = $manager->getSearchManager();
$searchHandler = $searchManager->getDefaultBasicHandler(PROJECT_ID, SCHEME_ID);

if (($results = $searchHandler->handleRequest()) !== false)
{
	//header('Content-type: application/json');
	//echo json_encode($results);
	
	foreach ($results as $photo): ?>
	  <div class="photo" style="border-style: solid">
	   <h3><?php echo htmlspecialchars($photo->Title); ?></h3>
	   <p><strong>Taken</strong>: <?php echo htmlspecialchars($photo->Taken); ?></p>
	   <p><img src="<?php echo htmlspecialchars($photo->Photo->thumbUrl); ?>" /></p>
	<?php foreach ($photo->Categories as $category): ?>
	   <p><strong>Category</strong>: <?php echo htmlspecialchars($category); ?></p>
	<?php endforeach; ?>
	  </div>
	<?php endforeach;

} else {
	$searchHandler->printForm();
}

?>