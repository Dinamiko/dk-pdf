<?php

namespace Dinamiko\DKPDF\Vendor\Mpdf\PsrLogAwareTrait;

use Dinamiko\DKPDF\Vendor\Psr\Log\LoggerInterface;

trait PsrLogAwareTrait 
{

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Psr\Log\LoggerInterface
	 */
	protected $logger;

	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}
	
}
