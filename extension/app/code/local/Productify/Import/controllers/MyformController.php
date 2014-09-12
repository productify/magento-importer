<?php

class Productify_Import_Adminhtml_MyformController extends Mage_Adminhtml_Controller_Action
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
            <a href='" . $this->getUrl('*/adminhtml_link/index') . "' class='tab-item-link'><span>Step 1</span></a>
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
            <a href='#' class='tab-item-link active'><span>Step 5</span></a>
        </li>
        </ul>
        </div>
        ";

        $this->_addLeft($this->getLayout()
            ->createBlock('core/text')
            ->setText($steps));

        $this->renderLayout();
    }
    function redirect($url)
    {
        if (!headers_sent())
        {
            header('Location: '.$url);
        }
        else
        {
            echo '<script type="text/javascript">';
            echo 'window.location.href="'.$url.'";';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
            echo '</noscript>';
        }
    }
    
    
    public function testAction()
    {
        
        $message = $this->__('The products have been imported successfully.');

        $header = "From:krita@access-keys.com\r\n";
        $to = 'krita@access-keys.com';
        $from = 'krita@access-keys.com'; // sender
        $subject = 'Test confirmation mail';
        $messages = 'The import has been successfully completed. <br/>'.$count.'products have been imported.';
        // message lines should not exceed 70 characters (PHP rule), so wrap it
        $messages = wordwrap($message, 70);
        // send mail
        $mail = mail($to,$subject,$messages,$header);
    }
    
    public function add_to_import($url, $skus, $import_images,$active_products, $email)
    {
        $date_time = date("Y-m-d H:i:s");
        foreach($skus as $sku)
        {
            $data = array('url'=>$url,'skus'=>$sku,'images'=>$import_images, 'enable_products'=>$active_products, 'email'=>$email, 'date_added'=>$date_time);
            $model = Mage::getModel('import/import')->setData($data);
            try {
                $insertId = $model->save()->getId();
                //echo "Data successfully inserted. Insert ID: ".$insertId;
            } catch (Exception $e){
                echo $e->getMessage();
            }
        }
        return true;
    }

    public function postAction()
    {

	$post = $this->getRequest()->getPost();
        $sespost = Mage::getSingleton('admin/session')->getFormPost();

        if (empty($post) && empty($sespost)) {
            Mage::throwException($this->__('Invalid form data.'));
        }
        elseif($post) {
            Mage::getSingleton('admin/session')->setFormPost($post);
        }
        
        $skulist = $_POST['product_sku'];
        //print_r($skulist);exit;
        
        $url = $_SESSION['url'];
        //echo "the url is ".$url; exit;

        $skus = array_unique($skulist);
        

        $image = ((isset($_POST['images']) && $_POST['images'] == 'on')? '1':'0');
        $active = ((isset($_POST['active']) && $_POST['active'] == 'on')? '1':'0');

        $done = $this->add_to_import($url,$skus,$image,$active,$_POST['mail']);

        $stat = $image.$active;
	$cron_url = Mage::getBaseUrl().'import?imageactive='.$stat;
	
	//cron begins
	
	    
	    $output = shell_exec('crontab -l');
	    
	    if(strpos($output,$cron_url) === false)
	    {
	        file_put_contents('/tmp/crontab.txt', $output."*/1 * * * * wget -q /dev/null $cron_url".PHP_EOL);
	        exec('crontab /tmp/crontab.txt');
	    }
	
	$url = $this->getUrl('*/adminhtml_myform/index');
        $this->redirect($url);
        exit;
        
        ///This ones for other purpose. Please ignore it.
	//exec function begins. This is where cron ends.
	exec("curl --silent $cron_url");
	//exec($cron_url . " 2>&1 > /dev/null &");
	
	$url = $this->getUrl('*/adminhtml_myform/index');
        $this->redirect($url);
	exit;
    }

    public function importProducts($p,$update)
    {
        // $sProduct is the object used for product creation
        //$sProduct = Mage::getModel('catalog/product');
        Mage::getSingleton('catalog/product_option')->unsetOptions(); // forget me or else magento will haunt you with dublicate values
        if($update == 0)
        {
            $product = new Mage_Catalog_Model_Product();
        }
        else
        {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku',$update);
        }
        //$product = new Mage_Catalog_Model_Product();
        // Build the product

        $product->setAttributeSetId(4);
        $product->setTypeId('simple');
        $product->setName($p['name']);

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

        $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

        if (isset($_POST['active']) && $_POST['active'] == 'on')
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
        /** Krita EXTERNAL IMAGE IMPORT - START **/

        //create directory if not exists
        if (!file_exists(Mage::getBaseDir('media') . DS . 'import')) {
            mkdir(Mage::getBaseDir('media') . DS . 'import', 0777, true);
        }

        if (isset($_POST['images']) && $_POST['images'] == 'on') {
            foreach ($p['media'] as $img) {
            set_time_limit(0);
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

    public function checkCategory($name, $par_id)
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

    public function createCategory($name, $parent_id)
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

}