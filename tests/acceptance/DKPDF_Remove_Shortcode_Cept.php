<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('use [dkpdf-remove] shortcode and see result');

$I->loginAsAdmin();
$I->amOnPluginsPage();
$I->activatePlugin('dk-pdf');

// As an admin I want to remove a piece of content in the generated PDF using [dkpdf-remove]
$I->am( 'admin' );
$I->wantToTest( 'remove a piece of content in the generated PDF using [dkpdf-remove] shortcode' );
$I->amOnPage( 'wp-admin/post-new.php' );
$I->see( 'Add New Post' );
$I->seeElement('#post #title');
$I->click( '#publish' );
$I->see( 'Publish' );
$I->fillField('#post input[type=text]', 'Remove content using [dkpdf-remove] shortcode');
$I->fillField('#post #content', 'Welcome [dkpdf-remove]<span style="width:auto;color:#FFF;background:red;">to</span>[/dkpdf-remove] WordPress.<br>');
$I->click( '#publish' );
$I->see( 'Post published.' );
