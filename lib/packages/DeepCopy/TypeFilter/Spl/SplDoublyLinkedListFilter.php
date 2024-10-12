<?php

namespace Dinamiko\DKPDF\Vendor\DeepCopy\TypeFilter\Spl;

use Closure;
use Dinamiko\DKPDF\Vendor\DeepCopy\DeepCopy;
use Dinamiko\DKPDF\Vendor\DeepCopy\TypeFilter\TypeFilter;
use SplDoublyLinkedList;

/**
 * @final
 */
class SplDoublyLinkedListFilter implements TypeFilter
{
    private $copier;

    public function __construct(Dinamiko\DKPDF\Vendor\DeepCopy $copier)
    {
        $this->copier = $copier;
    }

    /**
     * {@inheritdoc}
     */
    public function apply($element)
    {
        $newElement = clone $element;

        $copy = $this->createCopyClosure();

        return $copy($newElement);
    }

    private function createCopyClosure()
    {
        $copier = $this->copier;

        $copy = function (SplDoublyLinkedList $list) use ($copier) {
            // Replace each element in the list with a deep copy of itself
            for ($i = 1; $i <= $list->count(); $i++) {
                $copy = $copier->recursiveCopy($list->shift());

                $list->push($copy);
            }

            return $list;
        };

        return Closure::bind($copy, null, DeepCopy::class);
    }
}
