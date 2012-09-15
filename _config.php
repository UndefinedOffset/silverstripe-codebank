<?php
define('CB_BUILD_DATE','@@BUILD_DATE@@');
define('CB_VERSION','@@VERSION@@');

LeftAndMain::require_css('CodeBank/css/LeftAndMain.css');
LeftAndMain::setApplicationName('Code Bank: '.CB_VERSION.' '.CB_BUILD_DATE);
?>