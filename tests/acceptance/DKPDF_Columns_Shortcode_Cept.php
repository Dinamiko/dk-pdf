<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('test dkpdf-columns and dkpdf-columnbreak shortcodes');

$I->loginAsAdmin();
$I->amOnPluginsPage();
$I->activatePlugin('dk-pdf');

// As an admin I want to split the content in columns using [dkpdf-columns]
$I->am( 'admin' );
$I->wantToTest( 'split the content in columns using [dkpdf-columns] shortcode' );
$I->amOnPage( 'wp-admin/post-new.php' );
$I->see( 'Add New Post' );
$I->seeElement('#post #title');
$I->click( '#publish' );
$I->see( 'Publish' );
$I->fillField('#post input[type=text]', 'Split content in columns [dkpdf-columns]');
$I->fillField('#post #content', '[dkpdf-columns]
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin convallis quam sit amet erat egestas mattis. Vestibulum eros dui, bibendum non ante non, placerat placerat nibh.
<ul>
<li>Pellentesque laoreet arcu lorem</li>
<li>At sagittis leo suscipit eu</li>
<li>Nam egestas lorem ornare</li>
<li>Class aptent taciti sociosqu</li>
<li>Ad litora torquent per conubia</li>
<li>Nostra, per inceptos himenaeos.</li>
</ul>
[dkpdf-columnbreak]
Vestibulum risus quis, efficitur libero. Morbi ac mattis odio, ut volutpat est. Nulla faucibus est vel turpis lobortis volutpat. Integer tincidunt feugiat tortor ut eleifend. Cras vitae enim elementum, sagittis lorem dignissim, pharetra nulla. Vivamus placerat dignissim metus sit amet vulputate. Vestibulum pellentesque in dolor non luctus.
[/dkpdf-columns]

[dkpdf-columns columns="3" equal-columns="true" gap="20"]
Etiam sed euismod neque. Cras tristique massa ante, a tincidunt ipsum sagittis vel. Fusce tristique facilisis neque non semper. Vivamus pharetra risus vitae velit ultricies auctor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Aliquam condimentum felis arcu, eget mollis ipsum pharetra nec. Aliquam justo sapien, fringilla a erat et, luctus elementum nibh. Curabitur tincidunt gravida eleifend. Vivamus ornare auctor lacus, in eleifend ex gravida ac. Quisque sodales dui odio, nec venenatis neque ultrices eget. Phasellus et sodales lectus. Sed quis cursus augue. Maecenas ornare eros dolor, interdum laoreet massa tristique in.
[/dkpdf-columns]');
$I->click( '#publish' );
$I->see( 'Post published.' );
