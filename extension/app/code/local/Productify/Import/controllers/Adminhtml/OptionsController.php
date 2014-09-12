<?php

class Productify_Import_Adminhtml_OptionsController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {

        $this->loadLayout()
            ->_setActiveMenu('import')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Product Import'), Mage::helper('adminhtml')->__('Product Import'));

        $steps = "
        <div class='side-col'>
        <h3>STEPS</h3>
        <ul class='tabs'>
        <li>
            <a href='".$this->getUrl('*/adminhtml_link/index')."' class='tab-item-link'><span>Step 1</span></a>
        </li>
        <li>
            <a href='".$this->getUrl('*/adminhtml_select/index')."' class='tab-item-link'><span>Step 2</span></a>
        </li>
        <li>
            <a href='#' class='tab-item-link active' id='take3'><span>Step 3</span></a>
        </li>
        <li>
            <a href='#' class='tab-item-link' id='take4'><span>Step 4</span></a>
        </li>
        <li>
            <a href='#' class='tab-item-link'><span>Step 5</span></a>
        </li>
        </ul>
        </div>
        ";

        $this->_addLeft($this->getLayout()
            ->createBlock('core/text')
            ->setText($steps));

        $layout = $this->getLayout();
        $block = $layout->getBlock('options');

        @session_start();
        $url = $_SESSION['url'];
        $xml = simplexml_load_file($url) or die('error');

        if (isset($_POST['next_submit']))
        {
            Mage::getSingleton('core/session')->setSkulist($_POST['product']);
            $no_product_selected = count($_POST['product']);
            //to retrieve data in template
            $block->setProductNum($no_product_selected);

            $skus = array_unique($_POST['product']);
            $block->setSkuNum(count($skus));

        }

        $this->renderLayout();
    }

}