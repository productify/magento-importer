<?php
class Productify_Import_Block_Myform extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
    }

    protected $_myCollection;


    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $pager = $this->getLayout()->createBlock('page/html_pager','productlist.pager')->setTemplate("page/html/pager.phtml");
        $pager->setAvailableLimit(array(1=>1,9=>9,15=>15,20=>20,'all'=>'all'));
        $pager->setCollection($this->getMyCollection());
        $this->setChild('pager', $pager);
        $this->getMyCollection()->load();

        return $this;
    }


    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }


    protected function getMyCollection()
    {
        if (is_null($this->_myCollection)) {
            $this->_myCollection = Mage::getModel('import/import')->getCollection();
        }

        return $this->_myCollection;
    }

    public function gettabledata()
    {
        //on initialize la variable
        $retour='';
        /* we are doing the query to select all elements of the pfay_test table (thanks to our model test/test and we sort them by id_pfay_test */
        $collection = Mage::getModel('import/import')->getCollection()->setOrder('id_pfay_test','asc');
        /* then, we check the result of the query and with the function getData() */
        foreach($collection as $data)
        {
            $retour .= $data->getData('nom').' '.$data->getData('prenom')
                .' '.$data->getData('telephone').'<br />';
        }
        //i return a success message to the user thanks to the Session.
        //Mage::getSingleton('adminhtml/session')->addSuccess('Cool Ca marche !!');
        return $retour;
    }
}