<?php

require_once( __DIR__ . '/config.php' );

// create a new KoraManager
$manager = new \KoraORM\KoraManager(SEARCH_TOKEN);

// we are retrieving the same book 3 times in 2 different ways in order to
//   confirm that we are in fact getting the same PHP object all 3 times.
$photo = $manager->getByKid(EXAMPLE_KID);
$photo2 = $manager->getByKid(EXAMPLE_KID);
$photos = $manager->search(
		PROJECT_ID,
		SCHEME_ID,
		new KORA_Clause('KID', '=', EXAMPLE_KID),
		'ALL', //fields
		array( array( 'field' => 'kid', 'direction' => SORT_ASC ) )
		);
$photo3 = $photos[0];

?>

<p>Are they the same? <strong><?php echo ($photo === $photo2 && $photo === $photo3 ? 'Yes!' : 'no...'); ?></strong></p>
<p>Trying the pretty print method.</p>
<?php $photo->prettyPrint(); ?>