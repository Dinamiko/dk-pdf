<?php

namespace Dinamiko\DKPDF\Vendor\Mpdf\Tag;

class Th extends Td
{

	public function close(&$ahtml, &$ihtml)
	{
		$this->mpdf->SetStyle('B', false);
		parent::close($ahtml, $ihtml);
	}
}
