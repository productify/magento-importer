<?php
class Productify_Import_Model_Mysql4_Import_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        /* define the model for your collection test/test */
        $this->_init('import/import');
    }
}