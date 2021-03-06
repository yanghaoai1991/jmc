<?php 
/**
 * 快钱支付第二版-返回处理
 *
 * 快钱支付第二版-返回处理 (http://www.99bill.com)
 * 
 * 调用模板：无
 * 
 * @category   jieqicms
 * @package    pay
 * @copyright  Copyright (c) Hangzhou Jieqi Network Technology Co.,Ltd. (http://www.jieqi.com)
 * @author     $Author: juny $
 * @version    $Id: 99billreturnv2.php 234 2008-11-28 01:53:06Z juny $
 */

define('JIEQI_MODULE_NAME', 'pay');
define('JIEQI_PAY_TYPE', '99billv2');
require_once('../../global.php');
jieqi_loadlang('pay', JIEQI_MODULE_NAME);
jieqi_getconfigs(JIEQI_MODULE_NAME, JIEQI_PAY_TYPE, 'jieqiPayset');

//1-----------接收回的信息--------------------------------------------------------------------
$parmary=array();
//获取神州行网关账户号
$parmary['merchantAcctId']=trim($_REQUEST['merchantAcctId']);

//获取网关版本.固定值
///本代码版本号固定为v2.0
$parmary['version']=trim($_REQUEST['version']);

//获取语言种类.固定选择值。
///只能选择1、2
///1代表中文；2代表英文
$parmary['language']=trim($_REQUEST['language']);

//获取签名类型
///1代表MD5签名
///当前版本固定为1
$parmary['signType']=trim($_REQUEST['signType']);

//获取支付方式
///值为：20 、22
///20代表神州行卡密直接支付；22代表快钱账户神州行余额支付
$parmary['payType']=trim($_REQUEST['payType']);

//获取银行代码
$parmary['bankId']=trim($_REQUEST['bankId']);

//获取商户订单号
$parmary['orderId']=trim($_REQUEST['orderId']);

//获取商户提交订单时的时间
///14位数字。年[4位]月[2位]日[2位]时[2位]分[2位]秒[2位]
///如：20080101010101
$parmary['orderTime']=trim($_REQUEST['orderTime']);

//获取原始订单金额
///订单提交到快钱时的金额，单位为分。
///比方2 ，代表0.02元
$parmary['orderAmount']=trim($_REQUEST['orderAmount']);

//获取快钱交易号
///获取该交易在快钱的交易号
$parmary['dealId']=trim($_REQUEST['dealId']);

//获取银行交易号
///如果使用银行卡支付时，在银行的交易号。如不是通过银行支付，则为空
$parmary['bankDealId']=trim($_REQUEST['bankDealId']);

//获取在快钱交易时间
///14位数字。年[4位]月[2位]日[2位]时[2位]分[2位]秒[2位]
///如；20080101010101
$parmary['dealTime']=trim($_REQUEST['dealTime']);

//获取实际支付金额
///单位为分
///比方 2 ，代表0.02元
$parmary['payAmount']=trim($_REQUEST['payAmount']);

//获取交易手续费
///单位为分
///比方 2 ，代表0.02元
$parmary['fee']=trim($_REQUEST['fee']);

//获取扩展字段1
///与商户提交订单时的扩展字段1保持一致
$parmary['ext1']=trim($_REQUEST['ext1']);

//获取扩展字段2
///与商户提交订单时的扩展字段2保持一致
$parmary['ext2']=trim($_REQUEST['ext2']);

//获取处理结果
///10代表支付成功； 11代表支付失败
$parmary['payResult']=trim($_REQUEST['payResult']);

//获取错误代码
///详细见文档错误代码列表
$parmary['errCode']=trim($_REQUEST['errCode']);

//获取加密签名串
$signMsg=trim($_REQUEST['signMsg']);

//2-----------重新计算md5的值---------------------------------------------------------------------------
$txtmac='';
foreach($parmary as $k => $v){
	if($v != ''){
		if($txtmac != '') $txtmac .= '&';
		$txtmac .= $k.'='.$v;
	}
}
if($txtmac != '') $txtmac .= '&';
$txtmac .= 'key='.$jieqiPayset[JIEQI_PAY_TYPE]['paykey'];
$mysignMsg = strtoupper(md5($txtmac));

//3-----------判断返回信息，如果支付成功，并且支付结果可信，则做进一步的处理----------------------------

if($jieqiPayset[JIEQI_PAY_TYPE]['payid'] != $parmary['merchantAcctId']) jieqi_printfail($jieqiLang['pay']['customer_id_error']);
elseif($parmary['payResult'] != '10') jieqi_printfail($jieqiLang['pay']['pay_return_error']);
elseif (strtoupper($signMsg)==strtoupper($mysignMsg)){
	include_once($jieqiModules['pay']['path'].'/class/paylog.php');
	$paylog_handler=JieqiPaylogHandler::getInstance('JieqiPaylogHandler');
	$orderid=intval($parmary['orderId']);
	$paylog=$paylog_handler->get($orderid);
	if(is_object($paylog)){
		$buyname=$paylog->getVar('buyname');
		$buyid=$paylog->getVar('buyid');
		$payflag=$paylog->getVar('payflag');
		$egold=$paylog->getVar('egold');
		if($payflag == 0){
			include_once(JIEQI_ROOT_PATH.'/class/users.php');
			$users_handler =& JieqiUsersHandler::getInstance('JieqiUsersHandler');
			$uservip=1; //默认的vip等级

			//统计用户总的购买虚拟币，确认vip等级
			/*
			jieqi_getconfigs('system', 'vips', 'jieqiVips');
			if(!empty($jieqiVips)){
				$sql="SELECT SUM(saleprice) as sumegold FROM ".jieqi_dbprefix('obook_osale')." WHERE accountid=".$buyid;
				$query=JieqiQueryHandler::getInstance('JieqiQueryHandler');
				$query->execute($sql);
				$res=$query->getObject();
				if(is_object($res)) $sumegold=intval($res->getVar('sumegold', 'n'));
				else $sumegold=0;
				$sumegold+=$egold;
				foreach($jieqiVips as $k=>$v){
					$k=intval($k);
					if($sumegold >= $v['minegold'] && $k > $uservip) $uservip = $k;
				}
			}
			*/


			$ret=$users_handler->income($buyid, $egold, $jieqiPayset[JIEQI_PAY_TYPE]['paysilver'], $jieqiPayset[JIEQI_PAY_TYPE]['payscore'][$egold], $uservip);
			if($ret) $note=sprintf($jieqiLang['pay']['add_egold_success'], $buyname, JIEQI_EGOLD_NAME, $egold);
			else $note=sprintf($jieqiLang['pay']['add_egold_failure'], $buyid, $buyname, JIEQI_EGOLD_NAME, $egold);
			$paylog->setVar('rettime', JIEQI_NOW_TIME);
			$paylog->setVar('money', intval($amount * 100));
			$paylog->setVar('note', $note);
			$paylog->setVar('payflag', 1);
			if(!$paylog_handler->insert($paylog)) jieqi_printfail($jieqiLang['pay']['save_paylog_failure']);
			else jieqi_msgwin(LANG_DO_SUCCESS,sprintf($jieqiLang['pay']['buy_egold_success'], $buyname, JIEQI_EGOLD_NAME, $egold));
		}else{
			jieqi_printfail($jieqiLang['pay']['already_add_egold']);
		}
	}else{
		jieqi_printfail($jieqiLang['pay']['no_buy_record']);
	}
}
?>