<?php
define('CB_BUILD_DATE','@@BUILD_DATE@@');
define('CB_VERSION','@@VERSION@@');


//Extensions
Object::add_extension('Member','CodeBankMember');


//CMS Menu
CMSMenu::remove_menu_item('CodeBankAddSnippet');
CMSMenu::remove_menu_item('CodeBankEditSnippet');
CMSMenu::remove_menu_item('CodeBankSettings');


//Inject Menu Styles
LeftAndMain::require_css('CodeBank/css/CodeBankMenu.css');
?>