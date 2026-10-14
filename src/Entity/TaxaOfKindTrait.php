<?php

namespace Base\Classroom\Entity;

/**
 * One kind of taxa written at a time: the levels, the subjects and the
 * purposes share one association (taxa), and each has its own picker in the
 * back office. A picker sets the taxa of its kind and leaves the others where
 * they are; a value that is not of its kind (an identifier no entity was found
 * for, a taxon of another kind) is ignored, never handed to Doctrine.
 *
 * The class using it has getTaxa(), addTaxon() and removeTaxon().
 */
trait TaxaOfKindTrait
{
    /**
     * @param class-string           $kind
     * @param iterable<mixed>|null   $taxa
     */
    protected function replaceTaxaOf(string $kind, ?iterable $taxa): static
    {
        $wanted = [];
        foreach ($taxa ?? [] as $taxon) {
            if ($taxon instanceof $kind && !\in_array($taxon, $wanted, true)) {
                $wanted[] = $taxon;
            }
        }

        foreach ($this->getTaxa()->toArray() as $taxon) {
            if ($taxon instanceof $kind && !\in_array($taxon, $wanted, true)) {
                $this->removeTaxon($taxon);
            }
        }
        foreach ($wanted as $taxon) {
            $this->addTaxon($taxon);
        }

        return $this;
    }
}
