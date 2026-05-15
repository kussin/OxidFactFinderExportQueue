<?php

class wmdkffqueuearticle_attribute extends wmdkffqueuearticle_attribute_parent
{

    public function render()
    {
        parent::render();

        $this->_aViewData['edit'] = $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);

        $soxId = $this->getEditObjectId();
        if (isset($soxId) && $soxId != "-1") {
            // load object
            $oArticle->load($soxId);

            if ($oArticle->isDerived()) {
                $this->_aViewData["readonly"] = true;
            }
        }

        $iAoc = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter("aoc");
        if ($iAoc == 1) {
            $oArticleAttributeAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\ArticleAttributeAjax::class);
            $this->_aViewData['oxajax'] = $oArticleAttributeAjax->getColumns();

            return "popups/wmdk_article_attribute.tpl";
        } elseif ($iAoc == 2) {
            $oArticleSelectionAjax = oxNew(\OxidEsales\Eshop\Application\Controller\Admin\ArticleSelectionAjax::class);
            $this->_aViewData['oxajax'] = $oArticleSelectionAjax->getColumns();

            return "popups/article_selection.tpl";
        }

        return "article_attribute.tpl";
    }

}