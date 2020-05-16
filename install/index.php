<?
use \Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
class dmbgeo_ozonorderimport extends CModule
{
    public $MODULE_ID = 'dmbgeo.ozonorderimport';
    public $COMPANY_ID = 'dmbgeo';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function dmbgeo_ozonorderimport()
    {
        $arModuleVersion = array();
        include __DIR__ . "/version.php";
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("DMBGEO_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("DMBGEO_MODULE_DESC");
        $this->PARTNER_NAME = getMessage("DMBGEO_PARTNER_NAME");
        $this->PARTNER_URI = getMessage("DMBGEO_PARTNER_URI");
        $this->exclusionAdminFiles = array(
            '..',
            '.',
            'menu.php',
            'operation_description.php',
            'task_description.php',
        );
    }
   

    public function installDB(){
        global $DB;
        $DB->Query('ALTER TABLE `b_sale_order` ADD `OZON_ID` INT(11) UNSIGNED NULL DEFAULT NULL');
    }


    public function UninstallDB(){
        global $DB;
        $DB->Query('ALTER TABLE `b_sale_order` DROP `OZON_ID`');
    }
  
    public function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
    }

    public function GetPath($notDocumentRoot = false)
    {
        if ($notDocumentRoot) {
            return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
        } else {
            return dirname(__DIR__);
        }
    }

   
    public function UnInstallOptions()
    {
        \Bitrix\Main\Config\Option::delete($this->MODULE_ID);
    }

    public function DoInstall()
    {

        global $APPLICATION;
        if ($this->isVersionD7()) {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
            $path = $this->GetPath() . "/install/tools";

            if (\Bitrix\Main\IO\Directory::isDirectoryExists($path)) {
                CopyDirFiles($path, $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools", true, true);
            }

            $this->installDB();
    

        } else {
            $APPLICATION->ThrowException(Loc::getMessage("DMBGEO_INSTALL_ERROR_VERSION"));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage("DMBGEO_INSTALL"), $this->GetPath() . "/install/step.php");
    }

    public function DoUninstall()
    {

        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $this->UnInstallOptions();
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bitrix/tools/' . $this->MODULE_ID . '/');
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UninstallDB();
        $APPLICATION->IncludeAdminFile(Loc::getMessage("DMBGEO_UNINSTALL"), $this->GetPath() . "/install/unstep.php");
    }
}
