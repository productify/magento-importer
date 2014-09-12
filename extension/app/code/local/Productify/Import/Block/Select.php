<?php
class Productify_Import_Block_Adminhtml_Select extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_select';
        $this->_blockGroup = 'select';
        $this->_headerText = Mage::helper('employee')->__('Employee Manager');
        $this->_addButtonLabel = Mage::helper('employee')->__('Add Employee');
        parent::__construct();
    }
}