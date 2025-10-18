<?php
declare(strict_types=1);

namespace Dinamiko\DKPDF\Tests\Integration;

use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase {
	public function test_sample() {
		$post_type = get_post_type( 1 );

		$this->assertEquals('post', $post_type);
	}
}
