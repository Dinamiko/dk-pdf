<?php

namespace Dinamiko\DKPDF\Vendor\Mpdf\Tag;

use Dinamiko\DKPDF\Vendor\Mpdf\Strict;

use Dinamiko\DKPDF\Vendor\Mpdf\Cache;
use Dinamiko\DKPDF\Vendor\Mpdf\Color\ColorConverter;
use Dinamiko\DKPDF\Vendor\Mpdf\CssManager;
use Dinamiko\DKPDF\Vendor\Mpdf\Form;
use Dinamiko\DKPDF\Vendor\Mpdf\Image\ImageProcessor;
use Dinamiko\DKPDF\Vendor\Mpdf\Language\LanguageToFontInterface;
use Dinamiko\DKPDF\Vendor\Mpdf\Mpdf;
use Dinamiko\DKPDF\Vendor\Mpdf\Otl;
use Dinamiko\DKPDF\Vendor\Mpdf\SizeConverter;
use Dinamiko\DKPDF\Vendor\Mpdf\TableOfContents;

abstract class Tag
{

	use Strict;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Mpdf
	 */
	protected $mpdf;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Cache
	 */
	protected $cache;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\CssManager
	 */
	protected $cssManager;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Form
	 */
	protected $form;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Otl
	 */
	protected $otl;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\TableOfContents
	 */
	protected $tableOfContents;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\SizeConverter
	 */
	protected $sizeConverter;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Color\ColorConverter
	 */
	protected $colorConverter;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Image\ImageProcessor
	 */
	protected $imageProcessor;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Language\LanguageToFontInterface
	 */
	protected $languageToFont;

	const ALIGN = [
		'left' => 'L',
		'center' => 'C',
		'right' => 'R',
		'top' => 'T',
		'text-top' => 'TT',
		'middle' => 'M',
		'baseline' => 'BS',
		'bottom' => 'B',
		'text-bottom' => 'TB',
		'justify' => 'J'
	];

	public function __construct(
		$mpdf,
		Cache $cache,
		CssManager $cssManager,
		Form $form,
		Otl $otl,
		TableOfContents $tableOfContents,
		SizeConverter $sizeConverter,
		ColorConverter $colorConverter,
		ImageProcessor $imageProcessor,
		LanguageToFontInterface $languageToFont
	) {

		$this->mpdf = $mpdf;
		$this->cache = $cache;
		$this->cssManager = $cssManager;
		$this->form = $form;
		$this->otl = $otl;
		$this->tableOfContents = $tableOfContents;
		$this->sizeConverter = $sizeConverter;
		$this->colorConverter = $colorConverter;
		$this->imageProcessor = $imageProcessor;
		$this->languageToFont = $languageToFont;
	}

	public function getTagName()
	{
		$tag = get_class($this);
		return strtoupper(str_replace('Dinamiko\DKPDF\Vendor\Mpdf\Tag\\', '', $tag));
	}

	protected function getAlign($property)
	{
		$property = strtolower($property);
		return array_key_exists($property, self::ALIGN) ? self::ALIGN[$property] : '';
	}

	abstract public function open($attr, &$ahtml, &$ihtml);

	abstract public function close(&$ahtml, &$ihtml);

}
