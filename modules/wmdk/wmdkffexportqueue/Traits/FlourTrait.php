<?php

namespace Wmdk\FactFinderQueue\Traits;

trait FlourTrait
{
    private function _getFlourId()
    {
        // TODO: Get data & return from OXARTICLES__WMDKFLOURID

        return 'NULL';
    }

    private function _getFlourActive()
    {
        // TODO: Get data & return from OXARTICLES__WMDKFLOURACTIVE

        return 0;
    }

    private function _getFlourPrice()
    {
        // TODO: Get data & return from OXARTICLES__WMDKFLOURWAREHOUSEPRICE

        return 0;
    }

    private function _getFlourSaleAmount()
    {
        // TODO: Prozentualer Rabatt Lagerverkauf, muss berechnet werden als gerundete Zahl, ohne Nachkommastelle und
        // Sonerzeichen, wenn keine Preisgegenüberstellung bleibt das Feld leer

        return '';
    }

    private function _getFlourShortUrl()
    {
        // TODO: Get data & return from OXARTICLES__WMDKFLOURSHORTURL

        return '';
    }
}