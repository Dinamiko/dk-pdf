<?php

namespace Dinamiko\DKPDF\Vendor\Mpdf\Http;

use Dinamiko\DKPDF\Vendor\Psr\Http\Message\RequestInterface;

interface ClientInterface
{

	public function sendRequest(RequestInterface $request);

}
