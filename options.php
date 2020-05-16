<?php

use Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;

$module_id = 'dmbgeo.ozonorderimport';
$module_path = str_ireplace($_SERVER["DOCUMENT_ROOT"], '', __DIR__) . $module_id . '/';
$ajax_path = '/bitrix/tools/' . $module_id . '/' . 'ajax.php';
CModule::IncludeModule('main');
CModule::IncludeModule($module_id);
CModule::IncludeModule('iblock');
CModule::IncludeModule('sale');
Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);
if ($APPLICATION->GetGroupRight($module_id) < "S") {
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}
$ajax_path = '/bitrix/tools/' . $module_id . '/' . 'ajax.php';
$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$SITES = \OzonOrderImport::getSites();

$OrderOzonStatus = [
	['ID' => "awaiting_packaging", 'NAME' => Loc::getMessage("awaiting_packaging")],
	['ID' => "not_accepted", 'NAME' => Loc::getMessage("not_accepted")],
	['ID' => "arbitration", 'NAME' => Loc::getMessage("arbitration")],
	['ID' => "awaiting_deliver", 'NAME' => Loc::getMessage("awaiting_deliver")],
	['ID' => "delivering", 'NAME' => Loc::getMessage("delivering")],
	['ID' => "driver_pickup", 'NAME' => Loc::getMessage("driver_pickup")],
	['ID' => "delivered", 'NAME' => Loc::getMessage("delivered")],
	['ID' => "cancelled", 'NAME' => Loc::getMessage("cancelled")]
];

$arFilter = array(
	"ACTIVE"    => "Y",
	"GROUPS_ID" => array(1, 5)
);
$rsUsers = CUser::GetList(
	($by = "ID"),
	($order = "ASC"),
	$arFilter
);
$Users = array();
while ($arUsers = $rsUsers->fetch()) {
	$Users[] = array('ID' => $arUsers['ID'], 'NAME' =>"[".$arUsers['LOGIN']."] ". $arUsers['NAME']);
}


foreach ($SITES as $SITE) {
	$aTabs[] = array(
		'DIV' => $SITE['LID'],
		'TAB' => $SITE['NAME'],
		'OPTIONS' => array(
			array('OPTION_STATUS_' . $SITE['LID'], Loc::getMessage('OPTION_STATUS'), 'Y', array('checkbox', 1)),
			array('OPTION_API_LINK_' . $SITE['LID'], Loc::getMessage('OPTION_API_LINK'), '', array('text', 40)),
			array('OPTION_LOGIN_' . $SITE['LID'], Loc::getMessage('OPTION_LOGIN'), '', array('text', 20)),
			array('OPTION_PASSWORD_' . $SITE['LID'], Loc::getMessage('OPTION_PASSWORD'), '', array('text', 20)),
			array('OPTION_COMMENT_' . $SITE['LID'], Loc::getMessage('OPTION_COMMENT'), '', array('textarea', 15,100)),
			array('OPTION_USER_' . $SITE['LID'], Loc::getMessage('OPTION_USER'), '', array('text', 20)),
			
			// array('OPTION_ORDER_PAYMENT_STATUS_' . $SITE['LID'], Loc::getMessage('OPTION_ORDER_PAYMENT_STATUS'), 'Y', array('checkbox', 1)),
		),
	);
	$params[] = 'OPTION_STATUS_' . $SITE['LID'];
	$params[] = 'OPTION_API_LINK_' . $SITE['LID'];
	$params[] = 'OPTION_LOGIN_' . $SITE['LID'];
	$params[] = 'OPTION_PASSWORD_' . $SITE['LID'];
	$params[] = 'OPTION_COMMENT_' . $SITE['LID'];
	$params[] = 'OPTION_IBLOCK_ID_' . $SITE['LID'];
	$params[] = 'OPTION_PRODUCT_LINK_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_OZON_STATUS_' . $SITE['LID'];
	$params[] = 'OPTION_USER_' . $SITE['LID'];
	$params[] = 'OPTION_USER_PROFILE_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_PAYMENT_' . $SITE['LID'];
	// $params[] = 'OPTION_ORDER_PAYMENT_STATUS_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_DELIVERY_' . $SITE['LID'];
	
}
$aTabs[] = array(
	'DIV' => "agent",
	'TAB' => Loc::getMessage('AGENT_SETTING'),
	'OPTIONS' => array(
		array('OPTION_AGENT_STATUS', Loc::getMessage('OPTION_AGENT_STATUS'), '', array('checkbox', "Y")),
		array('OPTION_AGENT_INTERVAL', Loc::getMessage('OPTION_AGENT_INTERVAL'), '3600', array('text', 20)),
	),
);
$params[] = 'OPTION_AGENT_STATUS';
$params[] = 'OPTION_AGENT_INTERVAL';

if ($request->isPost() && $request['Apply'] && check_bitrix_sessid()) {

	foreach ($params as $param) {
		if (array_key_exists($param, $_POST) === true) {
			Option::set($module_id, $param, is_array($_POST[$param]) ? implode(",", $_POST[$param]) : $_POST[$param]);
		} else {

			Option::set($module_id, $param, "N");
		}
	}
	if (($_POST["OPTION_AGENT_STATUS"] ?? "N") == "Y") {
		$newInterval = $_POST["OPTION_AGENT_INTERVAL"] ?? 0;
		if (is_numeric($newInterval) && $newInterval > 60) {
			$newInterval = intval($newInterval);
		} else {
			$newInterval = 3600;
			Option::set($module_id, "OPTION_AGENT_INTERVAL", $newInterval);
		}

		createAgent($module_id, $newInterval);
	} else {
		deleteAgent($module_id);
	}
}
function deleteAgent($module_id)
{
	\CAgent::RemoveModuleAgents($module_id);
}

function createAgent($module_id, $newInterval)
{
	$interval = intval($newInterval);
	$arFields = array();
	$result = \CAgent::AddAgent(
		'\OzonOrderImport::Agent();', // имя функции
		$module_id, // идентификатор модуля
		"N", // агент не критичен к кол-ву запусков
		$interval, // интервал запуска - 1 сутки
		date("d.m.Y H:i:s", (time() + $interval)), // дата первой проверки - текущее
		"Y", // агент активен
		date("d.m.Y H:i:s", time()), // дата первого запуска - текущее
		1
	);
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);

?>
<? $tabControl->Begin(); ?>

<form method='post' action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($request['mid']) ?>&amp;lang=<?= $request['lang'] ?>' name='DMBGEO_settings'>

	<? $n = count($aTabs); ?>
	<? foreach ($aTabs as $key => $aTab) :
		if ($aTab['OPTIONS']) : ?>
			<? $tabControl->BeginNextTab(); ?>
			<? $OPTION_IBLOCK_ID = \COption::GetOptionString($module_id, 'OPTION_IBLOCK_ID_' . $aTab['DIV']); ?>
			<? __AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>
			<? if ($aTab['DIV'] !== "agent") : ?>
			
				<tr>
					<?
					$OPTION_USER = COption::GetOptionString($module_id, 'OPTION_USER_' . $aTab['DIV']);
					// упорядочив результат по дате последнего изменения
					$db_sales = CSaleOrderUserProps::GetList(
						array("DATE_UPDATE" => "ASC"),
						array("USER_ID" => intVal($OPTION_USER))
					);

					$OPTION_USER_PROFILE = COption::GetOptionString($module_id, 'OPTION_USER_PROFILE_' . $aTab['DIV']);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_USER_PROFILE"); ?><a name="opt_OPTION_USER_PROFILE_<?= $aTab['DIV']; ?>"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select data-site="<?= $aTab['DIV']; ?>" class="OPTION_USER_PROFILE" size="1" id='OPTION_USER_PROFILE_<?= $aTab['DIV']; ?>' name="OPTION_USER_PROFILE_<?= $aTab['DIV']; ?>">


							<? while ($arField = $db_sales->Fetch()) : ?>
								<?
								$option = '';
								$option .= '<option value="' . $arField['ID'] . '"';
								if ($arField['ID'] == $OPTION_USER_PROFILE) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $arField['NAME'];
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endwhile; ?>

						</select>
					</td>
				</tr>
				<tr>
				<?
					$OPTION_ORDER_PAYMENT = COption::GetOptionString($module_id, 'OPTION_ORDER_PAYMENT_' . $aTab['DIV']);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_ORDER_PAYMENT"); ?><a name="opt_OPTION_ORDER_PAYMENT_<?= $aTab['DIV']; ?>"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="1" id='OPTION_ORDER_PAYMENT_<?= $aTab['DIV']; ?>' name="OPTION_ORDER_PAYMENT_<?= $aTab['DIV']; ?>">


							<? $db_dtype = \CSalePaySystem::GetList(
								    array(),
								    array(
								            // "LID" => $aTab['DIV'],
								            "ACTIVE" => "Y"
								        )
								);
								?>

							<? while ($ar_dtype = $db_dtype->Fetch()):?>
								<?
								$option = '';
								$option .= '<option value="' . $ar_dtype['ID'] . '"';
								if ($ar_dtype['ID'] == $OPTION_ORDER_PAYMENT) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $ar_dtype['NAME'];
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endwhile; ?>

						</select>
					</td>
				</tr>
				<tr>
					<?
					$OPTION_ORDER_DELIVERY = COption::GetOptionString($module_id, 'OPTION_ORDER_DELIVERY_' . $aTab['DIV']);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_ORDER_DELIVERY"); ?><a name="opt_OPTION_ORDER_DELIVERY_<?= $aTab['DIV']; ?>"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="1" id='OPTION_ORDER_DELIVERY_<?= $aTab['DIV']; ?>' name="OPTION_ORDER_DELIVERY_<?= $aTab['DIV']; ?>">


							<? $db_dtype = \CSaleDelivery::GetList(
								    array(),
								    array(
								            // "LID" => $aTab['DIV'],
								            "ACTIVE" => "Y"
								        )
								);
								?>

							<? while ($ar_dtype = $db_dtype->Fetch()):?>
								<?
								$option = '';
								$option .= '<option value="' . $ar_dtype['ID'] . '"';
								if ($ar_dtype['ID'] == $OPTION_ORDER_DELIVERY) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $ar_dtype['NAME'];
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endwhile; ?>

						</select>
					</td>
				</tr>
				
			
				<tr>
					<?
					$OPTION_ORDER_OZON_STATUS = COption::GetOptionString($module_id, 'OPTION_ORDER_OZON_STATUS_' . $aTab['DIV']);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_ORDER_OZON_STATUS"); ?><a name="opt_OPTION_ORDER_OZON_STATUS_<?= $aTab['DIV']; ?>"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="1" id='OPTION_ORDER_OZON_STATUS_<?= $aTab['DIV']; ?>' name="OPTION_ORDER_OZON_STATUS_<?= $aTab['DIV']; ?>">


							<? foreach ($OrderOzonStatus as $arField) : ?>
								<?
								$option = '';
								$option .= '<option value="' . $arField['ID'] . '"';
								if ($arField['ID'] == $OPTION_ORDER_OZON_STATUS) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $arField['NAME'];
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endforeach; ?>

						</select>
					</td>
				</tr>
				<tr>
					<td><? echo Loc::getMessage('OPTION_IBLOCK_ID') ?></td>
					<td><? echo GetIBlockDropDownListEx($OPTION_IBLOCK_ID, 'OPTION_IBLOCK_TYPE_ID_' . $aTab['DIV'], 'OPTION_IBLOCK_ID_' . $aTab['DIV'], false, "OPTION_IBLOCK_TYPE_ID('OPTION_PRODUCT_LINK_" . $aTab['DIV'] . "')", "OPTION_IBLOCK_ID(this,'$ajax_path','OPTION_PRODUCT_LINK_" . $aTab['DIV'] . "')"); ?></td>
				</tr>
				<tr>
					<?
					$arFields = OzonOrderImport::getIblockFields($OPTION_IBLOCK_ID);
					$OPTION_PRODUCT_LINK = COption::GetOptionString($module_id, 'OPTION_PRODUCT_LINK_' . $aTab['DIV']);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage('OPTION_PRODUCT_LINK'); ?><a name="opt_OPTION_PRODUCT_LINK_<?= $aTab['DIV']; ?>"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="20" id='OPTION_PRODUCT_LINK_<?= $aTab['DIV']; ?>' name="OPTION_PRODUCT_LINK_<?= $aTab['DIV']; ?>">
							<? if (intval($OPTION_IBLOCK_ID) > 0) : ?>

								<? foreach ($arFields as $arField) : ?>
									<?
									$option = '';
									$option .= '<option value="' . $arField['ID'] . '"';
									if ($arField['ID'] == $OPTION_PRODUCT_LINK) {
										$option .= ' selected="selected" ';
									}
									$option .= '>';
									$option .= $arField['NAME'];
									$option .= '</option>';
									?>
									<? echo $option; ?>
								<? endforeach; ?>
							<? endif; ?>
						</select>
					</td>
				</tr>

			<? endif ?>

		<? endif ?>
	<? endforeach; ?>
	<?

	$tabControl->Buttons(); ?>

	<input type="submit" name="Apply" value="<? echo GetMessage('MAIN_SAVE') ?>">
	<input type="reset" name="reset" value="<? echo GetMessage('MAIN_RESET') ?>">
	<?= bitrix_sessid_post(); ?>
</form>
<? $tabControl->End(); ?>

<?
CJSCore::Init(array("jquery"));
?>
<script>
	function OPTION_IBLOCK_ID(selector, PATH, selectorField) {
		var IBLOCK_ID = $(selector).val();
		console.log(IBLOCK_ID);
		$.post(PATH, {
			IBLOCK_ID: $(selector).val(),
			EVENT_CHANGE_IBLOCK_ID: true
		}).done(function(data) {
			for (var i = 0; i < data.length; i++) {
				if (data[i] == "{" || data[i] == "[") {
					break;
				}

			}
			data = data.substring(i);

			data = JSON.parse(data);
			$('#' + selectorField).html('');
			console.log($('#' + selectorField));
			data.forEach(function(field) {
				var option = '<option value="' + field['ID'] + '" >';
				option += field['NAME'];
				option += '</option>';
				$('#' + selectorField).append(option);
			});
		});

		console.log(selectorField);
	}

	function OPTION_IBLOCK_TYPE_ID(selectorField) {
		$('#' + selectorField).html('');
	}
</script>