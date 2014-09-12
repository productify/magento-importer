<?php

class Productify_Import_Adminhtml_LinkController extends Mage_Adminhtml_Controller_Action
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
            <a href='#' class='tab-item-link active'><span>Step 1</span></a>
        </li>
        <li>
            <a href='#' class='tab-item-link'><span>Step 2</span></a>
        </li>
        <li>
            <a href='#' class='tab-item-link'><span>Step 3</span></a>
        </li>
        <li>
            <a href='#' class='tab-item-link'><span>Step 4</span></a>
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