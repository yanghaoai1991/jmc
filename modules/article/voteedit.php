<?php 
/**
 * 编辑文章投票调查
 *
 * 编辑文章投票调查
 * 
 * 调用模板：无
 * 
 * @category   jieqicms
 * @package    article
 * @copyright  Copyright (c) Hangzhou Jieqi Network Technology Co.,Ltd. (http://www.jieqi.com)
 * @author     $Author: juny $
 * @version    $Id: voteedit.php 228 2008-11-27 06:44:31Z juny $
 */

define('JIEQI_MODULE_NAME', 'article');
require_once('../../global.php');
jieqi_checklogin();
if(empty($_REQUEST['aid']) || empty($_REQUEST['id'])) jieqi_printfail(LANG_ERROR_PARAMETER);
jieqi_loadlang('avote', JIEQI_MODULE_NAME);
include_once($jieqiModules['article']['path'].'/class/article.php');
$article_handler =& JieqiArticleHandler::getInstance('JieqiArticleHandler');
$_REQUEST['id']=intval($_REQUEST['id']);
$_REQUEST['aid']=intval($_REQUEST['aid']);
$article=$article_handler->get($_REQUEST['aid']);
if(!$article) jieqi_printfail($jieqiLang['article']['article_not_exists']);
//检查权限
jieqi_getconfigs(JIEQI_MODULE_NAME, 'power');
//管理别人文章权限
$canedit=jieqi_checkpower($jieqiPower['article']['manageallarticle'], $jieqiUsersStatus, $jieqiUsersGroup, true);
if(!$canedit && !empty($_SESSION['jieqiUserId'])){
	//除了斑竹，作者、发表者和代理人可以修改文章
	$tmpvar=$_SESSION['jieqiUserId'];
	if($tmpvar>0 && ($article->getVar('authorid')==$tmpvar || $article->getVar('posterid')==$tmpvar || $article->getVar('agentid')==$tmpvar)){
		$canedit=true;
	}
}
if(!$canedit) jieqi_printfail($jieqiLang['article']['noper_article_votenew']);
jieqi_getconfigs(JIEQI_MODULE_NAME, 'configs');
$jieqiConfigs['article']['articlevote']=intval($jieqiConfigs['article']['articlevote']);
if($jieqiConfigs['article']['articlevote'] <= 0) jieqi_printfail($jieqiLang['article']['article_vote_close']);

include_once($jieqiModules['article']['path'].'/class/avote.php');
$avote_handler =& JieqiAvoteHandler::getInstance('JieqiAvoteHandler');
$avote=$avote_handler->get($_REQUEST['id']);
if(!$avote) jieqi_printfail($jieqiLang['article']['avote_not_exists']);

if (!isset($_REQUEST['action'])) $_REQUEST['action'] = 'edit';
$article_static_url = (empty($jieqiConfigs['article']['staticurl'])) ? $jieqiModules['article']['url'] : $jieqiConfigs['article']['staticurl'];
$article_dynamic_url = (empty($jieqiConfigs['article']['dynamicurl'])) ? $jieqiModules['article']['url'] : $jieqiConfigs['article']['dynamicurl'];

switch($_REQUEST['action']){
	case 'update':
		$errtext='';
		$_POST['title'] = trim($_POST['title']);
		$useitem=0;
		$itemary=array();
		for($i=1;$i<=$jieqiConfigs['article']['articlevote'];$i++){
			$_POST['item'.$i]=trim($_POST['item'.$i]);
			if($_POST['item'.$i] != ''){
				$itemary[$useitem]=$_POST['item'.$i];
				$useitem++;
			}
		}
		if(strlen($_POST['title'])==0) $errtext .= $jieqiLang['article']['avote_need_title'].'<br />';
		if($useitem < 2) $errtext .= $jieqiLang['article']['avote_need_moreitem'].'<br />';

		if(empty($errtext)) {
			$query=JieqiQueryHandler::getInstance('JieqiQueryHandler');
			$avote->setVar('posterid', $_SESSION['jieqiUserId']);
			$avote->setVar('poster', $_REQUEST['jieqiUserName']);
			$avote->setVar('title', $_POST['title']);
			foreach($itemary as $k=>$v){
				$avote->setVar('item'.($k+1), $v);
			}
			$i=$useitem+1;
			while($i<=10){
				$avote->setVar('item'.$i, '');
				$i++;
			}
			$avote->setVar('useitem', $useitem);
			if($avote->getVar('ispublish','n') != $_POST['ispublish']) $changepublish=true;
			else $changepublish=false;
			if($changepublish){
				if($_POST['ispublish']==1){
					$avote->setVar('ispublish', 1);
					$avote->setVar('starttime', JIEQI_NOW_TIME);

					$sql="UPDATE ".jieqi_dbprefix('article_avote')." SET ispublish=0, endtime=".intval(JIEQI_NOW_TIME)." WHERE articleid=".$_REQUEST['aid']." AND ispublish=1";
					$query->execute($sql);
					$setting=unserialize($article->getVar('setting', 'n'));
					$setting['avoteid']=$_REQUEST['id'];
					$article->setVar('setting', serialize($setting));
					$article_handler->insert($article);
				}else{
					$avote->setVar('ispublish', 0);
					$avote->setVar('endtime', JIEQI_NOW_TIME);

					$setting=unserialize($article->getVar('setting', 'n'));
					$setting['avoteid']=0;
					$article->setVar('setting', serialize($setting));
					$article_handler->insert($article);
				}
			}

			if($_POST['mulselect']==1){
				$avote->setVar('mulselect', 1);
			}else{
				$avote->setVar('mulselect', 0);
			}

			$avote->setVar('timelimit', 0);
			$avote->setVar('needlogin', 0);
			$avote->setVar('endtime', 0);

			if (!$avote_handler->insert($avote)) jieqi_printfail($jieqiLang['article']['avote_edit_failure']);
			else {
				jieqi_jumppage($article_static_url.'/votearticle.php?id='.$_REQUEST['aid'], LANG_DO_SUCCESS, $jieqiLang['article']['avote_edit_success']);
			}
		}else{
			jieqi_printfail($errtext);
		}
		break;
	case 'edit':
	default:
		//包含区块参数(定制区块)
		jieqi_getconfigs('article', 'authorblocks', 'jieqiBlocks');
		include_once(JIEQI_ROOT_PATH.'/header.php');
		$jieqiTpl->assign('article_static_url',$article_static_url);
		$jieqiTpl->assign('article_dynamic_url',$article_dynamic_url);

		include_once(JIEQI_ROOT_PATH.'/lib/html/formloader.php');
		$vote_form = new JieqiThemeForm($jieqiLang['article']['article_vote_edit'], 'editvote', $article_static_url.'/voteedit.php');

		$vote_form->addElement(new JieqiFormText($jieqiLang['article']['table_avote_title'], 'title', 50, 100, $avote->getVar('title','e')), true);

		for($i=1;$i<=$jieqiConfigs['article']['articlevote'];$i++){
			$vote_form->addElement(new JieqiFormText(sprintf($jieqiLang['article']['article_vote_item'],$i), 'item'.$i, 50, 100, $avote->getVar('item'.$i,'e')));
		}

		$mulselect=new JieqiFormRadio($jieqiLang['article']['article_vote_mulselect'], 'mulselect', $avote->getVar('mulselect','e'));
		$mulselect->addOption('0', $jieqiLang['article']['article_vote_single']);
		$mulselect->addOption('1', $jieqiLang['article']['article_vote_multiterm']);
		$vote_form->addElement($mulselect);

		$ispublish=new JieqiFormRadio($jieqiLang['article']['article_vote_publish'], 'ispublish', $avote->getVar('ispublish','e'));
		$ispublish->addOption('1', $jieqiLang['article']['article_votepub_yes']);
		$ispublish->addOption('0', $jieqiLang['article']['article_votepub_no']);
		$vote_form->addElement($ispublish);

		$vote_form->addElement(new JieqiFormHidden('action', 'update'));
		$vote_form->addElement(new JieqiFormHidden('id', $_REQUEST['id']));
		$vote_form->addElement(new JieqiFormHidden('aid', $_REQUEST['aid']));
		$vote_form->addElement(new JieqiFormButton('&nbsp;', 'submit', LANG_SUBMIT, 'submit'));
		$jieqiTpl->assign('authorarea', 1);
		$jieqiTpl->setCaching(1);
		$jieqiTpl->assign('jieqi_contents', $vote_form->render(JIEQI_FORM_MIDDLE));
		include_once(JIEQI_ROOT_PATH.'/footer.php');
		break;
}

?>