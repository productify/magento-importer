<?php

class Productify_Import_Adminhtml_SelectController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('import')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Product Import'), Mage::helper('adminhtml')->__('Product Import'));
            
            $backurl = $this->getUrl('*/adminhtml_link/index');
        Mage::getSingleton('core/session')->setBackUrl($backurl);

        $steps = "
        <div class='side-col'>
        <h3>STEPS</h3>
        <ul class='tabs'>
        <li>
            <a href='".$this->getUrl('*/adminhtml_link/index')."' class='tab-item-link'><span>Step 1</span></a>
        </li>
        <li>
            <a href='#' class='tab-item-link active' id='take2' onclick='reverse_options()'><span>Step 2</span></a>
        </li>
        <li>
            <a href='#' class='tab-item-link' id='take3'><span>Step 3</span></a>
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

        $this->renderLayout();
    }
}