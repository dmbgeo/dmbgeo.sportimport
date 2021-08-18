<?

use \Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
class dmbgeo_sportimport extends CModule
{
    public $MODULE_ID = 'dmbgeo.sportimport';
    public $COMPANY_ID = 'dmbgeo';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function dmbgeo_sportimport()
    {
        $arModuleVersion = array();
        include __DIR__ . "/version.php";
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("DMBGEO_SPORTIMPORT_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("DMBGEO_SPORTIMPORT_MODULE_DESC");
        $this->PARTNER_NAME = getMessage("DMBGEO_SPORTIMPORT_PARTNER_NAME");
        $this->PARTNER_URI = getMessage("DMBGEO_SPORTIMPORT_PARTNER_URI");
        $this->exclusionAdminFiles = array(
            '..',
            '.',
            'menu.php',
        );
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


    public function InstallFiles()
    {

        $path = $this->GetPath() . "/admin";

        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path)) {
            if ($dir = opendir($path)) {
                while (false !== $item = readdir($dir)) {
                    if (in_array($item, $this->exclusionAdminFiles)) {
                        continue;
                    }
                    file_put_contents(
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $item,
                        '<' . '? require("' . $path . '/' . $item . '");?' . '>'
                    );
                }
                closedir($dir);
            }
        }
        return true;
    }

    public function UnInstallFiles()
    {
        $path = $this->GetPath() . "/admin";

        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path)) {
            if ($dir = opendir($path)) {
                while (false !== $item = readdir($dir)) {
                    if (in_array($item, $this->exclusionAdminFiles)) {
                        continue;
                    }
                    \Bitrix\Main\IO\File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $item);
                }
                closedir($dir);
            }
        }
        return true;
    }

    public function DoInstall()
    {
        $this->InstallFiles();
        global $APPLICATION;
        if ($this->isVersionD7()) {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        } else {
            $APPLICATION->ThrowException(Loc::getMessage("DMBGEO_SPORTIMPORT_INSTALL_ERROR_VERSION"));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage("DMBGEO_SPORTIMPORT_INSTALL"), $this->GetPath() . "/install/step.php");
    }

    public function DoUninstall()
    {

        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $this->UnInstallFiles();
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(Loc::getMessage("DMBGEO_SPORTIMPORT_UNINSTALL"), $this->GetPath() . "/install/unstep.php");
    }
}
