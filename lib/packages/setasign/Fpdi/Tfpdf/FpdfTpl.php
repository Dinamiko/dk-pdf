<?php

/**
 * This file is part of FPDI
 *
 * @package   Dinamiko\DKPDF\Vendor\setasign\Fpdi
 * @copyright Copyright (c) 2024 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace Dinamiko\DKPDF\Vendor\setasign\Fpdi\Tfpdf;

use Dinamiko\DKPDF\Vendor\setasign\Fpdi\FpdfTplTrait;

/**
 * Class FpdfTpl
 *
 * We need to change some access levels and implement the setPageFormat() method to bring back compatibility to tFPDF.
 */
class FpdfTpl extends \tFPDF
{
    use FpdfTplTrait;
}
