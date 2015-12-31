<?php
interface CodeBank_APIClass
{
    /**
     * Gets the permissions required to access the class
     * @return {array} Array of permission names to check
     */
    public function getRequiredPermissions();
}
