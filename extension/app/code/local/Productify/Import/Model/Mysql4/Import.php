<?php
class Productify_Import_Model_Mysql4_Import extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        /*test/test will use as primary key the id_pfay_test*/
        $this->_init('import/import', 'import_id');
    }
}