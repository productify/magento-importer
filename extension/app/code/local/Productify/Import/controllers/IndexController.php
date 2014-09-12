<?php
/*
* Controller class has to be inherited from Mage_Core_Controller_action
*/
class Productify_Import_IndexController extends Mage_Core_Controller_Front_Action
{

    /*
    * this method provides default action.
    */
    private $simpleProducts = array();

    public function indexAction()
    {

        error_reporting(E_ALL | E_STRICT);
        $mageFilename = 'app/Mage.php';
        require_once $mageFilename;
        Mage::setIsDeveloperMode(true);
        umask(0);
        Mage::app();
        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));

        $model = Mage::getModel('import/import');
        $collection = $model->getCollection()
            ->addFieldToSelect('url')
            ->addFieldToFilter('status', 0)
            ->addFieldToFilter('import_id', array('neq' => 1));
        $collection->getSelect()->group('url');

        $model1 = Mage::getModel('import/import')->load(1);
        $error_mes = $model1->getErrormsg();
        $count = $model1->getProdimp();
        $err = $model1->getNoimp();
        $send_email = $model1->getEmail();

        //echo count($collection);
        if (!isset($collection) || count($collection) == 0) {
            //error message

            if ($error_mes != '')
                $error_mes = "<strong>Updated products:</strong><br/>" . $error_mes . "<br/>";

            // Always set content-type when sending HTML email
            $header = "MIME-Version: 1.0" . "\r\n";
            $header .= "Content-type:text/html;charset=UTF-8" . "\r\n";

            // More headers
            $header .= 'From: Productify.com <noreply@productify.com>' . "\r\n";

            $to = $send_email;
            $from = 'noreply@productify.com'; // sender
            $subject = 'Data imported successfully';
            $message = '
            Hi there,<br/><br/>
            The Productify Import has been successfully completed.<br/><br/>
            <strong>Details:</strong> <br/>

            <strong>Total records imported: </strong>' . $count . '<br/>

            <strong>Total records updated: </strong>' . $err . '<br/>
            
            <strong>Total records failed: </strong>0<br/><br/>'.$error_mes.

                'Please login into your store to see the imported Products. Note, depending on preferences chosen, you may have to enable the Products or Categories within the admin to display the products in your store.<br/><br/>Regards';
            // message lines should not exceed 70 characters (PHP rule), so wrap it
            $message = wordwrap($message, 70);
            // send mail
            $mail = mail($to, $subject, $message, $header);

            $output = shell_exec('crontab -l');
            $current_cron_array = explode("\n", $output);
            $current_url = Mage::getBaseUrl() . 'import?imageactive=' . $_GET['imageactive'];

            $new_cron = array();
            $update_cron = 0;
            foreach ($current_cron_array as $cur_crn) {
                if (strpos($cur_crn, $current_url) != false) {
                    $update_cron = 1;
                    continue;
                } else {
                    $new_cron[] = $cur_crn;
                }
            }

            if ($update_cron == 1) {
                $new_cron_text = implode("\n", $new_cron);
                file_put_contents('/tmp/crontab.txt', $new_cron_text . PHP_EOL);
                exec('crontab /tmp/crontab.txt');
            }

            // update data for email message
            $id = 1;

            $data = array('errormsg' => '', 'prodimp' => 0, 'noimp' => 0);
            $model = Mage::getModel('import/import')->load($id)->addData($data);
            try {
                $model->setId($id)->save();

            } catch (Exception $e) {
                echo $e->getMessage();
            }

        } else {


            //to retrieve all sku list
            $skuarr = array();

            foreach ($collection as $item) {
                $xml_url = ($item->getUrl());

                $skus = $model->getCollection()
                    ->addFieldToSelect('skus')
                    ->addFieldToSelect('email')
                    ->addFieldToFilter('url', $xml_url)
                    ->addFieldToFilter('status', 0)
                    ->addFieldToFilter('import_id', array('neq' => 1));

                foreach ($skus as $sskuu) {
                    array_push($skuarr, $sskuu->getSkus());
                    $send_email = $sskuu->getEmail();
                }

                //limit the import
                if (isset($_GET['imageactive']) && ($_GET['imageactive'] == 11 || $_GET['imageactive'] == 10))
                    $skuarr = array_slice($skuarr, 0, 8);
                else
                    $skuarr = array_slice($skuarr, 0, 30);
                //$skuarr = array_unique($skuarr);

                $error_mes = '';
                //echo $getskulen = count($skuarr) . "length";

                $xml = simplexml_load_file($xml_url) or die('Cannot load XML file. Please try again later.');
                $count = 0;
                $err = 0;

                foreach ($xml->products->product as $prd) {

                    $product = array(
                        "product_code" => "$prd->product_code",
                        "name" => "$prd->product_name",
                        "brand" => "$prd->brand",
                        "short_description" => "$prd->short_description",
                        "detail_description" => "$prd->detailed_description",
                    );

                    $categories = $prd->categories->category;
                    $product['category'] = "$categories";
                    $images = array();
                    foreach ($prd->media->image_url as $img) {
                        //print_r($img);
                        $image_default = "false";
                        foreach ($img->attributes() as $a => $b) {
                            $image_default = "$b";
                        }
                        $images[] = array("default" => "$image_default", "image_url" => "$img");
                    }

                    $product['media'] = $images;

                    //to check for multiple skus
                    $multiskucounter = 0;
                    foreach ($prd->skus->sku as $s) {
                        $multiskucounter++;
                        $prd_sku = "$s->id";
                        //print_r($s);
                        $sku = array();

                        if (in_array($prd_sku, $skuarr))
                        {
                            //$skulist = $prd->skus->sku;
                            //$skulist = array_unique($skulist);
                            //$multisku = count($skulist);
                            //This one is for checking the ticked values
                            //$multisku = count(in_array($prd->skus->sku, $skuarr));

                            $skup = array();
                            foreach ($prd->skus->sku as $ps)
                            {
                                if (in_array("$ps->id", $skuarr))
                                    array_push($skup, "$ps->id");
                            }
                            $skup = array_unique($skup);
                            $multisku = count($skup);
                            //echo $multisku;

                            $sku = array(
                                "id" => "$prd_sku",
                                "sale_price" => "$s->sale_price",
                                "retail_price" => "$s->retail_price",
                                "stock" => "$s->stock",
                                "ean" => "$s->ean",
                                "upc" => "$s->upc",
                                "weight" => "$s->weight"
                            );
                            $variants = array();
                            foreach ($s->variants->variant as $var) {
                                foreach ($var->attributes() as $a => $v) {
                                    $variants[] = array(
                                        'label' => "$v",
                                        'value' => "$var"
                                    );
                                }
                            }

                            $sku['variants'] = $variants;

                            $product['sku'] = $sku;

                            //add the product to the database

                            $checkid = Mage::getModel('catalog/product')->getIdBySku($prd_sku);
                            if ($checkid > 0) {
                                $err++;
                                $error_mes .= "<strong>" . $prd->product_name . "</strong> " . $prd_sku . "<br/>";
                                $this->importProducts($product, $prd_sku, $multisku);
                                if($multiskucounter == $multisku && $multisku>1)
                                {
                                    //check for old values
                                    $sku = $product['sku']['id']."-config";
                                    $yes = Mage::getModel("catalog/product")->loadByAttribute('sku', $sku);
                                    if($yes)
                                    {
                                        $err++;
                                        $error_mes .= "<strong>" . $prd->product_name . "</strong> " . $sku . "<br/>";
                                        $this->saveConfigurableProduct($product);
                                    }
                                    else
                                    {
                                        $count += $this->saveConfigurableProduct($product);
                                    }
                                    $this->simpleProducts = array();
                                }

                            } else {
                                $count += $this->importProducts($product, 'new', $multisku);
                                if($multiskucounter == $multisku && $multisku>1)
                                {
                                    //check for old values
                                    $sku = $product['sku']['id']."-config";
                                    $yes = Mage::getModel("catalog/product")->loadByAttribute('sku', $sku);
                                    if($yes)
                                    {
                                        $err++;
                                        $error_mes .= "<strong>" . $prd->product_name . "</strong> " . $sku . "<br/>";
                                        $this->saveConfigurableProduct($product);
                                    }
                                    else
                                    {
                                        $count += $this->saveConfigurableProduct($product);
                                    }
                                    $this->simpleProducts = array();
                                }
                            }

                            $collection1 = $model->getCollection()
                                ->addFieldToFilter('skus', $prd_sku);

                            foreach ($collection1 as $item1) {
                                $itemid = $item1->getId();

                                try {
                                    $model->setId($itemid)->delete();

                                } catch (Exception $e) {
                                    echo $e->getMessage();
                                }
                            }
                        }

                    }

                    //to create configurable product
                    if(isset($multisku) && $multisku>1)
                    {
                        echo "multi sku ".$multisku;
                        $multisku = 0;
                        //$this->saveConfigurableProduct($product);
                    }
                }
            }

            // update data for email message
            $id = 1;

            $model1 = Mage::getModel('import/import')->load($id);
            $error_mes = $model1->getErrormsg() . $error_mes;
            $count = $count + $model1->getProdimp();
            $err = $err + $model1->getNoimp();

            $data = array('errormsg' => $error_mes, 'prodimp' => $count, 'noimp' => $err, 'email' => $send_email);
            $model = Mage::getModel('import/import')->load($id)->addData($data);
            try {
                $model->setId($id)->save();

            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }


    }

    public function importProducts($p, $update, $multisku)
    {
        // $sProduct is the object used for product creation
        Mage::getSingleton('catalog/product_option')->unsetOptions(); // forget me or else magento will haunt you with dublicate values
        if ($update == 'new') {
            $product = new Mage_Catalog_Model_Product();
        } else {
            //$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $update);
            try
            {
                Mage::getModel("catalog/product")->loadByAttribute('sku', $update)->delete();
            }
            catch(Exception $e)
            {
                echo "Delete failed";
            }
            $product = new Mage_Catalog_Model_Product();
        }
        $product = new Mage_Catalog_Model_Product();
        // Build the product

        if($multisku == 1)
        {
            $product->setAttributeSetId(4);
            $product->setTypeId('simple');
            $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
        }

        else
        {
            $attributesetid = $this->getAttributeSetAction();
            $product->setAttributeSetId($attributesetid);
            $type_id = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
            $product->setTypeId($type_id);

            //dont make the product visible individually
            $visibility = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
            $product->setVisibility($visibility);
        }


        $product->setName($p['name']);
        //for meta tags
        $product->setMetaDescription($p['short_description']);
        $product->setMetaTitle($p['name']);

        //for sku items
        $product->setSku($p['sku']['id']);

        //because price is in cents
        $product->setPrice($p['sku']['retail_price'] / 100);
        // Special price entry
        $product->setSpecialPrice($p['sku']['sale_price'] / 100);
        $numweight = filter_var($p['sku']['weight'], FILTER_SANITIZE_NUMBER_INT);
        $product->setWeight($numweight);

        $product->setStockData(array(
            'is_in_stock' => 1,
            'qty' => $p['sku']['stock']
        ));

        //Check for categories

        //$cate = "test > subcat";
        //$pieces = explode(">", $cate);

        $values = $p['category'];
        $pieces = explode(">", $values);

        $cat = $this->checkCategory($pieces['0'], 'cat');

        if ($pieces['1']) {
            $cats = $this->checkCategory($pieces['1'], $cat);
            foreach ($cats as $c) {
                array_push($cat, $c);
            }

        }

        //$cats = $this->checkCategory($values, 'abc');

        $product->setCategoryIds($cat); # some cat id's, my is 7
        $product->setWebsiteIDs(array(1)); # Website id, my is 1 (default frontend)
        $product->setDescription($p['detail_description']);
        $product->setShortDescription($p['short_description']);

        # Custom created and assigned attributes
        //$product->setEan($p['ean']);
        //$product->setCost($p['cost']);

//        $product->setDepth('my_custom_attribute3_val');
//        $product->setType('my_custom_attribute4_val');
        //Default Magento attribute


        if (isset($_GET['imageactive']) && ($_GET['imageactive'] == 11 || $_GET['imageactive'] == 01))
            $product->setStatus(1);
        else
            $product->setStatus(2);

        $product->setTaxClassId(0); # My default tax class
        $product->setCreatedAt(strtotime('now'));


        try {
            $product->save();
            //$message = $this->__('Your form has been submitted successfully.');
            //Mage::getSingleton('adminhtml/session')->addSuccess($message);

        } catch (Exception $e) {
            echo "something went wrong!";
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        if($multisku > 1)
        {

            if (isset($p['sku']['variants'])) {
                foreach ($p['sku']['variants'] as $v) {
                    //add custom options
                    //Separate the values
                    $values = $v['value'];
                    $pieces = explode("/", $values);

                    $arr = array();
                    foreach ($pieces as $val) {
                        array_push($arr, array(
                            'is_delete' => 0,
                            'title' => $val,
                            'price_type' => 'fixed'
                        ));
                    }
                    echo $v['label'];
                    foreach($arr as $optval)
                    {
                        if ($this->getAttributeOptionAction($v['label'], $optval['title']))
                            $attropt = $this->getAttributeOptionAction($v['label'], $optval['title']);
                        else
                            $attropt = $this->addAttributeAction($v['label'], $optval['title']);

                        $attributeid = $this->getAttributeId($v['label']);
                        $product->setData($v['label'], $attropt);

                        // we are creating an array with some information which will be used to bind the simple products with the configurable
                        array_push(
                            $this->simpleProducts,
                            array(
                                "id" => $product->getId(),
                                "price" => $product->getPrice(),
                                "attr_code" => $v['label'],
                                "attr_id" => $attributeid, // i have used the hardcoded attribute id of attribute size, you must change according to your store
                                "value" => $attropt,
                                "label" => $v['label'],
                            )
                        );
                    }

                    $optionData = array(
                        'is_delete' => 0,
                        'is_require' => true,
                        'previous_group' => '',
                        'title' => $v['label'],
                        'type' => 'radio',
                        'price_type' => 'fixed',
                        'values' => $arr
                    );
                }
            }

        }
        else{
            if (isset($p['sku']['variants'])) {
                foreach ($p['sku']['variants'] as $v) {
                    //add custom options
                    //Separate the values
                    $values = $v['value'];
                    $pieces = explode("/", $values);

                    $arr = array();
                    foreach ($pieces as $val) {
                        array_push($arr, array(
                            'is_delete' => 0,
                            'title' => $val,
                            'price_type' => 'fixed'
                        ));
                    }
                    //print_r($arr);exit;

                    $optionData = array(
                        'is_delete' => 0,
                        'is_require' => true,
                        'previous_group' => '',
                        'title' => $v['label'],
                        'type' => 'radio',
                        'price_type' => 'fixed',
                        'sort_order' => 1,
                        'values' => $arr
                    );

                    $product->setHasOptions(1);
                    $product->setCanSaveCustomOptions(1);
                    $product->setOptions(array($optionData));
                    Mage::getSingleton('catalog/product_option')->unsetOptions();
                    $product->setProductOptions(array($optionData));

                    $opt = Mage::getSingleton('catalog/product_option');
                    $opt->setProduct($product);
                    $opt->addOption($optionData);
                    $opt->saveOptions();
                    $product->setOption($opt);
                }
            }
        }

        /** Krita EXTERNAL IMAGE IMPORT - START **/

        //create directory if not exists
        if (!file_exists(Mage::getBaseDir('media') . DS . 'import')) {
            mkdir(Mage::getBaseDir('media') . DS . 'import', 0777, true);
        }

        if (isset($_GET['imageactive']) && ($_GET['imageactive'] == 11 || $_GET['imageactive'] == 10)) {
            foreach ($p['media'] as $img) {
                $image_url = $img['image_url'];
                $image_type = substr(strrchr($image_url, "."), 1); //find the image extension
                $filename = md5($image_url . 'sku') . '.' . $image_type; //give a new name, you can modify as per your requirement


                $filepath = Mage::getBaseDir('media') . DS . 'import' . DS . $filename; //path for temp storage folder: ./media/import/
                file_put_contents($filepath, file_get_contents(trim($image_url))); //store the image from external url to the temp storage folder
                $mediaAttribute = array(
                    'thumbnail',
                    'small_image',
                    'image'
                );
                /**
                 * Add image to media gallery
                 *
                 * @param string $file file path of image in file system
                 * @param string|array $mediaAttribute code of attribute with type 'media_image',
                 *                                         leave blank if image should be only in gallery
                 * @param boolean $move if true, it will move source file
                 * @param boolean $exclude mark image as disabled in product page view
                 */

                //add image and setting default image
                if ($img['default'])
                    $product->addImageToMediaGallery($filepath, $mediaAttribute, false, false);
                else
                    $product->addImageToMediaGallery($filepath, null, false, false);
            }
        }

        /** EXTERNAL IMAGE IMPORT - END **/


        try {
            $product->getResource()->save($product);
            return 1;

        } catch (Exception $e) {
            echo "something went wrong!";
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    public
    function checkCategory($name, $par_id)
    {
        //check if category exits
        //$arr_length = count($name);
        $count = 0;
        $parent_id = 0;
        $catid = array();
        $childCategory = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToFilter('name', $name)
            ->getFirstItem() // Assuming your category names are unique ??
        ;

        if (null !== $childCategory->getId()) {
            //if found
            $parent_id = $childCategory->getId();
            array_push($catid, $parent_id);
        } else {
            //if not found
            $parent_id = $this->createCategory($name, $par_id);
            array_push($catid, $parent_id);
            /*else
            {
                $parent_id = $this->createCategory($name, $parent_id);
                array_push($catid, $parent_id);
            }*/
        }

        //checking category complete
        return $catid;
    }

    public
    function createCategory($name, $parent_id)
    {
        try {
            $store = Mage::getModel('core/store')->load(Mage_Core_Model_App::DISTRO_STORE_ID);
            if ($parent_id == 'cat') {
                $parentId = $store->getRootCategoryId();

                $category = new Mage_Catalog_Model_Category();
                $category->setStoreId(Mage::app()->getStore()->getId());
                $category->setName($name);
                $category->setIsActive(1);
                $category->setIsAnchor(0);

                $parentCategory = Mage::getModel('catalog/category')->load($parentId);
                $category->setPath($parentCategory->getPath());

                $category->save();
                //print_r($category);
                return $category->getId();
            } else {
                $parentId = $parent_id;
                $category = Mage::getModel('catalog/category');
                $category->setName($name)
                    ->setIsActive(1) //activate your category
                    ->setDisplayMode('PRODUCTS')
                    ->setIsAnchor(0)
                    ->setAttributeSetId($category->getDefaultAttributeSetId());

                $parentCategory = Mage::getModel('catalog/category')->load($parentId);
                $category->setPath($parentCategory->getPath());

                $category->save();
                return $category->getId();
            }

        } catch (Exception $e) {
            var_dump($e);
        }
    }

    //all the code below this is for configurable product
    public function addAttributeAction($arg_attribute, $arg_value)
    {
        $attribute_model = Mage::getModel('eav/entity_attribute');
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute);

        if (!$attribute_code)
            $attribute_code = $this->createAttributeAction($arg_attribute);

        $attribute = $attribute_model->load($attribute_code);
        $attribute_table = $attribute_options_model->setAttribute($attribute);
        $value['option'] = array($arg_value);
        $result = array('value' => $value);
        $attribute->setData('option', $result);
        $attribute->save();

        $model = Mage::getModel('eav/entity_setup', 'core_setup');

        $attributeOptionId = $this->getAttributeOptionAction($arg_attribute, $arg_value);

        $attributeSetId = $this->getAttributeSetAction();
        try {
            $attributeGroupId = $this->getAttributeGroupId('Additional Options', $attributeSetId);
            //$attributeGroupId=$model->getAttributeGroup('catalog_product',$attributeSetId,'Additional Options');
        } catch (Exception $e) {
            $attributeGroupId = $model->getDefaultAttributeGroupId('catalog/product', $attributeSetId);
        }
        $model->addAttributeToSet('catalog_product', $attributeSetId, $attributeGroupId, $attribute_code);

        return $attributeOptionId;
    }

    // to create attribute of type text
    function createAttributeAction($attribute_name)
    {
        $attribute_code = $this->generateCode($attribute_name);
        $attributeId = $this->getAttributeId($attribute_name);
        if (isset($attributeId) && !empty($attributeId)) {
            return $attributeId;
        }
        $installer = $this->getInstaller();
        $array = array(
            'type' => 'int',
            'input' => 'select', //drop-down
            'label' => $attribute_name,
            'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'is_required' => '1',
            'is_comparable' => '0',
            'is_searchable' => '0',
            'is_unique' => '0',
            'is_configurable' => '1',
            'user_defined' => '1'
        );

        $installer->addAttribute('catalog_product', $attribute_code, $array);
        $attributeId = $this->getAttributeId($attribute_code);
        return $attributeId = $this->getAttributeId($attribute_code);
    }

    function generateCode($string)
    {
        //Lower case everything
        $string = strtolower($string);
        //Make alphanumeric (removes all other characters)
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
        //Clean up multiple dashes or whitespaces
        $string = preg_replace("/[\s-]+/", " ", $string);
        //Convert whitespaces and underscore to dash
        $string = preg_replace("/[\s_]/", "-", $string);
        return $string;
    }

    // to get attribute id
    function getAttributeId($attribute_code)
    {
        $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attribute_code);
        return $attribute->getId();
    }

    public function getAttributeOptionAction($arg_attribute, $arg_value)
    {
        $attribute_model = Mage::getModel('eav/entity_attribute');
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
        $attribute = $attribute_model->load($attribute_code);
        $attribute_table = $attribute_options_model->setAttribute($attribute);
        $options = $attribute_options_model->getAllOptions(false);
        foreach ($options as $option) {
            if ($option['label'] == $arg_value) {
                return $option['value'];
            }
        }
        return false;
    }

    public function getAttributeSetAction()
    {
        $entityTypeId = Mage::getModel('eav/entity')
            ->setType('catalog_product')
            ->getTypeId();
        $attributeSetName = 'Productify';
        $attributeSetId = Mage::getModel('eav/entity_attribute_set')
            ->getCollection()
            ->setEntityTypeFilter($entityTypeId)
            ->addFieldToFilter('attribute_set_name', $attributeSetName)
            ->getFirstItem()
            ->getAttributeSetId();

        if ($attributeSetId)
            return $attributeSetId;
        else
            return $this->createAttributeSet('Productify');
    }

    function createAttributeSet($setName)
    {
        //get default attribute set id
        $skeletonID = Mage::getModel('catalog/product')->getDefaultAttributeSetId();

        $entityTypeId = Mage::getModel('catalog/product')
            ->getResource()
            ->getEntityType()
            ->getId(); //product entity type

        $attributeSet = Mage::getModel('eav/entity_attribute_set')
            ->setEntityTypeId($entityTypeId)
            ->setAttributeSetName($setName);

        $attributeSet->validate();
        $attributeSet->save();

        //Sets based on 'default'
        $attributeSet->initFromSkeleton($entityTypeId)->save();

        $setId = $attributeSet->getId();
        $this->createAttributeGroup('Additional Options', $setId);
        return $setId;
    }

    // to get attribute group id
    function getAttributeGroupId($attribute_group_name, $attributeSetId)
    {
        $entityTypeId = $this->getEntityTypeId();
        $installer = $this->getInstaller(); //new Mage_Eav_Model_Entity_Setup('core_setup');
        $attributeGroupObject = new Varien_Object($installer->getAttributeGroup($entityTypeId, $attributeSetId, $attribute_group_name));
        return $attributeGroupId = $attributeGroupObject->getAttributeGroupId();
    }

    // to create attribute group
    function createAttributeGroup($attribute_group_name, $attributeSetId)
    {
        if (isset($attributeGroupId) && !empty($attributeGroupId)) {
            return $attributeGroupId;
        }
        $entityTypeId = $this->getEntityTypeId();
        $installer = $this->getInstaller(); //new Mage_Eav_Model_Entity_Setup('core_setup');

        $installer->addAttributeGroup($entityTypeId, $attributeSetId, $attribute_group_name);
        return $attributeGroupId = $this->getAttributeGroupId($attribute_group_name, $attributeSetId);
    }

    // to get entity type id ( product entity )
    function getEntityTypeId()
    {
        return $entityTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
    }

    // get installer object
    function getInstaller()
    {
        return new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup('core_setup');
    }

    function saveConfigurableProduct($p)
    {

        //check for old values
        $sku = $p['sku']['id']."-config";

        $yes = Mage::getModel("catalog/product")->loadByAttribute('sku', $sku);

        if($yes)
        {

            try
            {
                Mage::getModel("catalog/product")->loadByAttribute('sku', $sku)->delete();
            }
            catch(Exception $e)
            {
                echo "Delete failed";
            }
        }

        //create a configurable product
        $cProduct = Mage::getModel('catalog/product');

        //Check for categories
        $values = $p['category'];
        $pieces = explode(">", $values);

        $cat = $this->checkCategory($pieces['0'], 'cat');

        if ($pieces['1']) {
            $cats = $this->checkCategory($pieces['1'], $cat);
            foreach ($cats as $c) {
                array_push($cat, $c);
            }

        }

        $attributesetid = $this->getAttributeSetAction();
        $cProduct->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
            ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
            ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->setWebsiteIds(array(1))
            ->setCategoryIds($cat)
            ->setAttributeSetId($attributesetid) // You can determine this another way if you need to.
            ->setSku($p['sku']['id']."-config") //change this
            ->setName($p['name'])
            ->setShortDescription($p['short_description'])
            ->setDescription($p['detail_description'])
            ->setPrice($p['sku']['retail_price'] / 100)
            ->setTaxClassId(0)
            ->setStockData(
                array(
                    'is_in_stock' => 1,
                    'qty' => 9999
                )
            )
        ;

        //for meta tags
        $cProduct->setMetaDescription($p['short_description']);
        $cProduct->setMetaTitle($p['name']);
        // Special price entry
        $cProduct->setSpecialPrice($p['sku']['sale_price'] / 100);

        $numweight = filter_var($p['sku']['weight'], FILTER_SANITIZE_NUMBER_INT);
        $cProduct->setWeight($numweight);

        /*$cProduct->setStockData(array(
            'manage_stock' => 1,
            'is_in_stock' => 1,
            'use_config_manage_stock' => 1
        ));*/

        $cProduct->setCanSaveConfigurableAttributes(true);
        $cProduct->setCanSaveCustomOptions(true);

        $cProductTypeInstance = $cProduct->getTypeInstance();

        $attribute_ids = array();
        if (isset($p['sku']['variants'])) {
            foreach ($p['sku']['variants'] as $v) {
                //add custom options
                //Separate the values
                $attribute_id = $this->getAttributeId($v['label']);
                array_push($attribute_ids, $attribute_id);
            }
        }

        //$attribute_ids = array($optionId); //take out all the attributes

        $cProductTypeInstance->setUsedProductAttributeIds($attribute_ids);
        $attributes_array = $cProductTypeInstance->getConfigurableAttributesAsArray();

        foreach ($attributes_array as $key => $attribute_array)
        {
            $attributes_array[$key]['use_default'] = 1;
            $attributes_array[$key]['position'] = 0;

            if (isset($attribute_array['frontend_label'])) {
                $attributes_array[$key]['label'] = $attribute_array['frontend_label'];
            } else {
                $attributes_array[$key]['label'] = $attribute_array['attribute_code'];
            }
        }
        // Add it back to the configurable product..
        $cProduct->setConfigurableAttributesData($attributes_array);

        $dataArray = array();
        foreach ($this->simpleProducts as $simpleArray) {
            $dataArray[$simpleArray['id']] = array();
            foreach ($attributes_array as $key => $attrArray) {
                array_push(
                    $dataArray[$simpleArray['id']],
                    array(
                        "attribute_id" => $simpleArray['attr_id'][$key],
                        "label" => $simpleArray['label'][$key],
                        "is_percent" => 0
                    )
                );
            }
        }
        $cProduct->setConfigurableProductsData($dataArray);

        try {
            $cProduct->save();

            if (isset($_GET['imageactive']) && ($_GET['imageactive'] == 11 || $_GET['imageactive'] == 10)) {
                foreach ($p['media'] as $img) {
                    $image_url = $img['image_url'];
                    $image_type = substr(strrchr($image_url, "."), 1); //find the image extension
                    $filename = md5($image_url . 'sku') . '.' . $image_type; //give a new name, you can modify as per your requirement


                    $filepath = Mage::getBaseDir('media') . DS . 'import' . DS . $filename; //path for temp storage folder: ./media/import/
                    file_put_contents($filepath, file_get_contents(trim($image_url))); //store the image from external url to the temp storage folder
                    $mediaAttribute = array(
                        'thumbnail',
                        'small_image',
                        'image'
                    );
                    /**
                     * Add image to media gallery
                     *
                     * @param string $file file path of image in file system
                     * @param string|array $mediaAttribute code of attribute with type 'media_image',
                     *                                         leave blank if image should be only in gallery
                     * @param boolean $move if true, it will move source file
                     * @param boolean $exclude mark image as disabled in product page view
                     */

                    //add image and setting default image
                    if ($img['default'])
                        $cProduct->addImageToMediaGallery($filepath, $mediaAttribute, false, false);
                    else
                        $cProduct->addImageToMediaGallery($filepath, null, false, false);
                }
                $cProduct->getResource()->save($cProduct);
            }
            //$message = $this->__('Your form has been submitted successfully.');
            //Mage::getSingleton('adminhtml/session')->addSuccess($message);

        } catch (Exception $e) {
            echo "something went wrong!";
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        return true;

        //$cProduct->save();
    }
}