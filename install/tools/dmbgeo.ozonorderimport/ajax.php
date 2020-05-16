<? include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('dmbgeo.ozonorderimport');
if(isset($_POST['EVENT_CHANGE_IBLOCK_ID']) && $_POST['EVENT_CHANGE_IBLOCK_ID']==true){
    $GLOBALS['APPLICATION']->RestartBuffer();
    echo json_encode(OzonOrderImport::getIblockFields($_POST['IBLOCK_ID']),true);
}
?>
