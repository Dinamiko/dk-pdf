<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('see FontAwesome glyphs displayed in the PDF');

$I->am( 'admin' );
$I->loginAsAdmin();
$I->amOnPluginsPage();
$I->activatePlugin('dk-pdf');

$I->amOnPage( 'wp-admin/admin.php?page=dkpdf_settings' );
$I->checkOption('#pdfbutton_post_types_post');
$I->click('Save Settings');
$I->see('Settings saved.');

$I->click('Log Out', '#wp-admin-bar-logout a');
$I->seeElement('#user_login');
$I->see('Log In');

$I->am( 'site visitor' );
$I->wantToTest( 'see if FontAwesome glyphs displayed in the PDF' );
$I->amOnPage('test-post/');
$I->expect('see fontawesome pdf icon');
$I->see('PDF Button');
$I->seeElement('.fa-file-pdf-o');

$I->am( 'admin' );
$I->wantToTest( 'create a post with fontawesome icon in the content' );
$I->loginAsAdmin();
$I->amOnPage( 'wp-admin/post-new.php' );
$I->see( 'Add New Post' );
$I->seeElement('#post #title');
$I->click( '#publish' );
$I->see( 'Publish' );
$I->fillField('#post input[type=text]', 'FontAwesome Icon');
$I->fillField('#post #content', 'Test FontAwesome icon<br><i style="font-size:50px;" class="fa fa-file-pdf-o" aria-hidden="true"></i>');
$I->click( '#publish' );
$I->see( 'Post published.' );
/*
test click pdf button manually in frontend in order to see if fontawesome icon appear
*/
