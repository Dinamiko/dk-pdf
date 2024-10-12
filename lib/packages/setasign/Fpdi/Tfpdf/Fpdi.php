<?php

/**
 * This file is part of FPDI
 *
 * @package   Dinamiko\DKPDF\Vendor\setasign\Fpdi
 * @copyright Copyright (c) 2024 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace Dinamiko\DKPDF\Vendor\setasign\Fpdi\Tfpdf;

use Dinamiko\DKPDF\Vendor\setasign\Fpdi\FpdfTrait;
use Dinamiko\DKPDF\Vendor\setasign\Fpdi\FpdiTrait;

/**
 * Class Fpdi
 *
 * This class let you import pages of existing PDF documents into a reusable structure for tFPDF.
 */
class Fpdi extends FpdfTpl
{
    use FpdiTrait;
    use FpdfTrait;

    /**
     * FPDI version
     *
     * @string
     */
    const VERSION = '2.6.1';
}
