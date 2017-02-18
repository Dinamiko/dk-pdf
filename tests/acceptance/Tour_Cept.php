<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Backend and Frontend Tour');

$I->am( 'admin' );
$I->loginAsAdmin();
$I->amOnPluginsPage();
$I->wantToTest( 'activate DK PDF plugin' );
$I->activatePlugin('dk-pdf');
$I->wantToTest( 'setup show pdf button in post' );
$I->amOnPage( 'wp-admin/admin.php?page=dkpdf_settings' );
$I->see('PDF Button');
$I->checkOption('#pdfbutton_post_types_post');
$I->click('Save Settings');
$I->see('Settings saved.');

// quick fix error: Failed asserting that  on page /wp-admin/wp-admin/wp-admin/post-new.php -->  404 Not Found 404 Not Found nginx
// we've to logout and then login in order to continue the test
$I->click('Log Out', '#wp-admin-bar-logout a');
$I->seeElement('#user_login');
$I->see('Log In');

$I->loginAsAdmin();
$I->wantToTest( 'create a new post' );
$I->amOnPage( 'wp-admin/index.php' );
$I->see('Dashboard');

$I->am( 'admin' );
$I->wantToTest( 'create a new post' );

$I->click('Log Out', '#wp-admin-bar-logout a');
$I->seeElement('#user_login');
$I->see('Log In');
$I->loginAsAdmin();

$I->amOnPage( 'wp-admin/post-new.php' );
$I->see( 'Add New Post' );
$I->seeElement('#post #title');
$I->click( '#publish' );
$I->see( 'Publish' );
$I->fillField('#post input[type=text]', 'Test Post');
$I->click( 'publish' );
$I->see( 'Post published.' );

$I->am( 'site visitor' );
$I->wantToTest( 'see if PDF button appears in post' );
$I->amOnPage('test-post/');
$I->expect('See PDF Button');
$I->see('PDF Button');
