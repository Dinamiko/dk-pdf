<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('test dkpdf-remove shortcode');

$I->loginAsAdmin();
$I->amOnPluginsPage();
$I->activatePlugin('dk-pdf');

// As an admin I want to remove a simple shortcode in the generated PDF
$I->am( 'admin' );
$I->wantToTest( 'remove a simple shortcode in the generated PDF' );
$I->amOnPage( 'wp-admin/post-new.php' );
$I->see( 'Add New Post' );
$I->seeElement('#post #title');
// create post with gallery shortcode
$I->fillField('#post input[type=text]', 'Remove shortcodes in the PDF');
$I->fillField('#post #content', '[dkpdf-remove tag="gallery"][gallery ids="54,49,46"][/dkpdf-remove]');
$I->click( '#publish' );
$I->see( 'Post published.' );
