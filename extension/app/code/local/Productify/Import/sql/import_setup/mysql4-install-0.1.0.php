<?php
//die("here i started to install the script");
$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS productify_import;
CREATE TABLE productify_import (
  `import_id` int(11) NOT NULL auto_increment,
  `date_added` datetime NOT NULL,
  `status` tinyint(1) NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `skus` text COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `images` tinyint(1) NOT NULL,
  `enable_products` tinyint(1) NOT NULL,
  `errormsg` text COLLATE utf8_unicode_ci NOT NULL,
  `prodimp` int(11) NOT NULL,
  `noimp` int(11) NOT NULL,
  PRIMARY KEY (`import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `productify_import` (`import_id`, `date_added`, `status`, `url`, `skus`, `email`, `images`, `enable_products`, `errormsg`, `prodimp`, `noimp`) VALUES
(1, '2014-05-21 00:00:00', 0, 'abcd.com', 'ac', 'shweta@productify.com', 1, 1, '', 0, 0);

    ");



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


$installer->endSetup();