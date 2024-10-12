<?php

namespace Dinamiko\DKPDF\Vendor\Mpdf\File;

class LocalContentLoader implements \Dinamiko\DKPDF\Vendor\Mpdf\File\LocalContentLoaderInterface
{

	public function load($path)
	{
		return file_get_contents($path);
	}

}
