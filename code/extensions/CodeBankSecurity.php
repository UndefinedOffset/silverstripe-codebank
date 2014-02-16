<?php
class CodeBankSecurity extends Extension {
    /**
     * Gets the current version of Code Bank
     * @return {string} Version Number Plus Build Date
     */
    final public function getCodeBankVersion() {
        if(CB_VERSION!='@@VERSION@@') {
            return CB_VERSION.' '.CB_BUILD_DATE;
        }
        
        // Tries to obtain version number from composer.lock if it exists
        $composerLockPath=BASE_PATH.'/composer.lock';
        if(file_exists($composerLockPath)) {
            $cache=SS_Cache::factory('CodeBank_Version');
            $cacheKey=filemtime($composerLockPath);
            $version=$cache->load($cacheKey);
            if($version) {
                $version=$version;
            }else {
                $version='';
            }
            
            if(!$version && $jsonData=file_get_contents($composerLockPath)) {
                $lockData=json_decode($jsonData);
                if($lockData && isset($lockData->packages)) {
                    foreach ($lockData->packages as $package) {
                        if($package->name=='undefinedoffset/silverstripe-codebank' && isset($package->version)) {
                            $version=$package->version;
                            break;
                        }
                    }
                    
                    $cache->save($version, $cacheKey);
                }
            }
        }
        
        if(!empty($version)) {
            return $version;
        }
        
        return _t('CodeBank.DEVELOPMENT_BUILD', '_Development Build');
    }
}
?>