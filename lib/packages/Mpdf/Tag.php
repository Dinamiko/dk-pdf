<?php

namespace Dinamiko\DKPDF\Vendor\Mpdf;

use Dinamiko\DKPDF\Vendor\Mpdf\Strict;
use Dinamiko\DKPDF\Vendor\Mpdf\Color\ColorConverter;
use Dinamiko\DKPDF\Vendor\Mpdf\Image\ImageProcessor;
use Dinamiko\DKPDF\Vendor\Mpdf\Language\LanguageToFontInterface;

class Tag
{

	use Strict;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Mpdf
	 */
	private $mpdf;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Cache
	 */
	private $cache;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\CssManager
	 */
	private $cssManager;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Form
	 */
	private $form;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Otl
	 */
	private $otl;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\TableOfContents
	 */
	private $tableOfContents;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\SizeConverter
	 */
	private $sizeConverter;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Color\ColorConverter
	 */
	private $colorConverter;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Image\ImageProcessor
	 */
	private $imageProcessor;

	/**
	 * @var \Dinamiko\DKPDF\Vendor\Mpdf\Language\LanguageToFontInterface
	 */
	private $languageToFont;

	/**
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\Mpdf $mpdf
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\Cache $cache
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\CssManager $cssManager
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\Form $form
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\Otl $otl
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\TableOfContents $tableOfContents
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\SizeConverter $sizeConverter
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\Color\ColorConverter $colorConverter
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\Image\ImageProcessor $imageProcessor
	 * @param \Dinamiko\DKPDF\Vendor\Mpdf\Language\LanguageToFontInterface $languageToFont
	 */
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

	/**
	 * @param string $tag The tag name
	 * @return \Dinamiko\DKPDF\Vendor\Mpdf\Tag\Tag
	 */
	private function getTagInstance($tag)
	{
		$className = self::getTagClassName($tag);
		if (class_exists($className)) {
			return new $className(
				$this->mpdf,
				$this->cache,
				$this->cssManager,
				$this->form,
				$this->otl,
				$this->tableOfContents,
				$this->sizeConverter,
				$this->colorConverter,
				$this->imageProcessor,
				$this->languageToFont
			);
		}
	}

	/**
	 * Returns the fully qualified name of the class handling the rendering of the given tag
	 *
	 * @param string $tag The tag name
	 * @return string The fully qualified name
	 */
	public static function getTagClassName($tag)
	{
		static $map = [
			'BARCODE' => 'BarCode',
			'BLOCKQUOTE' => 'BlockQuote',
			'COLUMN_BREAK' => 'ColumnBreak',
			'COLUMNBREAK' => 'ColumnBreak',
			'DOTTAB' => 'DotTab',
			'FIELDSET' => 'FieldSet',
			'FIGCAPTION' => 'FigCaption',
			'FORMFEED' => 'FormFeed',
			'HGROUP' => 'HGroup',
			'INDEXENTRY' => 'IndexEntry',
			'INDEXINSERT' => 'IndexInsert',
			'NEWCOLUMN' => 'NewColumn',
			'NEWPAGE' => 'NewPage',
			'PAGEFOOTER' => 'PageFooter',
			'PAGEHEADER' => 'PageHeader',
			'PAGE_BREAK' => 'PageBreak',
			'PAGEBREAK' => 'PageBreak',
			'SETHTMLPAGEFOOTER' => 'SetHtmlPageFooter',
			'SETHTMLPAGEHEADER' => 'SetHtmlPageHeader',
			'SETPAGEFOOTER' => 'SetPageFooter',
			'SETPAGEHEADER' => 'SetPageHeader',
			'TBODY' => 'TBody',
			'TFOOT' => 'TFoot',
			'THEAD' => 'THead',
			'TEXTAREA' => 'TextArea',
			'TEXTCIRCLE' => 'TextCircle',
			'TOCENTRY' => 'TocEntry',
			'TOCPAGEBREAK' => 'TocPageBreak',
			'VAR' => 'VarTag',
			'WATERMARKIMAGE' => 'WatermarkImage',
			'WATERMARKTEXT' => 'WatermarkText',
		];

		$className = 'Dinamiko\DKPDF\Vendor\Mpdf\Tag\\';
		$className .= isset($map[$tag]) ? $map[$tag] : ucfirst(strtolower($tag));

		return $className;
	}

	public function OpenTag($tag, $attr, &$ahtml, &$ihtml)
	{
		// Correct for tags where HTML5 specifies optional end tags excluding table elements (cf WriteHTML() )
		if ($this->mpdf->allow_html_optional_endtags) {
			if (isset($this->mpdf->blk[$this->mpdf->blklvl]['tag'])) {
				$closed = false;
				// li end tag may be omitted if immediately followed by another li element
				if (!$closed && $this->mpdf->blk[$this->mpdf->blklvl]['tag'] == 'LI' && $tag == 'LI') {
					$this->CloseTag('LI', $ahtml, $ihtml);
					$closed = true;
				}
				// dt end tag may be omitted if immediately followed by another dt element or a dd element
				if (!$closed && $this->mpdf->blk[$this->mpdf->blklvl]['tag'] == 'DT' && ($tag == 'DT' || $tag == 'DD')) {
					$this->CloseTag('DT', $ahtml, $ihtml);
					$closed = true;
				}
				// dd end tag may be omitted if immediately followed by another dd element or a dt element
				if (!$closed && $this->mpdf->blk[$this->mpdf->blklvl]['tag'] == 'DD' && ($tag == 'DT' || $tag == 'DD')) {
					$this->CloseTag('DD', $ahtml, $ihtml);
					$closed = true;
				}
				// p end tag may be omitted if immediately followed by an address, article, aside, blockquote, div, dl,
				// fieldset, form, h1, h2, h3, h4, h5, h6, hgroup, hr, main, nav, ol, p, pre, section, table, ul
				if (!$closed && $this->mpdf->blk[$this->mpdf->blklvl]['tag'] == 'P'
						&& ($tag == 'P' || $tag == 'DIV' || $tag == 'H1' || $tag == 'H2' || $tag == 'H3'
							|| $tag == 'H4' || $tag == 'H5' || $tag == 'H6' || $tag == 'UL' || $tag == 'OL'
							|| $tag == 'TABLE' || $tag == 'PRE' || $tag == 'FORM' || $tag == 'ADDRESS' || $tag == 'BLOCKQUOTE'
							|| $tag == 'CENTER' || $tag == 'DL' || $tag == 'HR' || $tag == 'ARTICLE' || $tag == 'ASIDE'
							|| $tag == 'FIELDSET' || $tag == 'HGROUP' || $tag == 'MAIN' || $tag == 'NAV' || $tag == 'SECTION')) {
					$this->CloseTag('P', $ahtml, $ihtml);
					$closed = true;
				}
				// option end tag may be omitted if immediately followed by another option element
				// (or if it is immediately followed by an optgroup element)
				if (!$closed && $this->mpdf->blk[$this->mpdf->blklvl]['tag'] == 'OPTION' && $tag == 'OPTION') {
					$this->CloseTag('OPTION', $ahtml, $ihtml);
					$closed = true;
				}
				// Table elements - see also WriteHTML()
				if (!$closed && ($tag == 'TD' || $tag == 'TH') && $this->mpdf->lastoptionaltag == 'TD') {
					$this->CloseTag($this->mpdf->lastoptionaltag, $ahtml, $ihtml);
					$closed = true;
				} // *TABLES*
				if (!$closed && ($tag == 'TD' || $tag == 'TH') && $this->mpdf->lastoptionaltag == 'TH') {
					$this->CloseTag($this->mpdf->lastoptionaltag, $ahtml, $ihtml);
					$closed = true;
				} // *TABLES*
				if (!$closed && $tag == 'TR' && $this->mpdf->lastoptionaltag == 'TR') {
					$this->CloseTag($this->mpdf->lastoptionaltag, $ahtml, $ihtml);
					$closed = true;
				} // *TABLES*
				if (!$closed && $tag == 'TR' && $this->mpdf->lastoptionaltag == 'TD') {
					$this->CloseTag($this->mpdf->lastoptionaltag, $ahtml, $ihtml);
					$this->CloseTag('TR', $ahtml, $ihtml);
					$this->CloseTag('THEAD', $ahtml, $ihtml);
					$closed = true;
				} // *TABLES*
				if (!$closed && $tag == 'TR' && $this->mpdf->lastoptionaltag == 'TH') {
					$this->CloseTag($this->mpdf->lastoptionaltag, $ahtml, $ihtml);
					$this->CloseTag('TR', $ahtml, $ihtml);
					$this->CloseTag('THEAD', $ahtml, $ihtml);
					$closed = true;
				} // *TABLES*
			}
		}

		if ($object = $this->getTagInstance($tag)) {
			return $object->open($attr, $ahtml, $ihtml);
		}
	}

	public function CloseTag($tag, &$ahtml, &$ihtml)
	{
		if ($object = $this->getTagInstance($tag)) {
			return $object->close($ahtml, $ihtml);
		}
	}
}
