<?php
class Productify_Import_Model_Import extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        /*telling that there’s a logical entity test of your plugin test*/
        $this->_init('import/import');
    }
}