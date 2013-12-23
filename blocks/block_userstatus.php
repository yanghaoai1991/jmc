<?php 
/**
 * �û�״̬��ʾ����
 *
 * ��ʾ��ǰ��¼�û��Ļ�����Ϣ�͵�����
 * 
 * ����ģ�壺/templates/blocks/block_userstatus.html
 * 
 * @category   jieqicms
 * @package    system
 * @copyright  Copyright (c) Hangzhou Jieqi Network Technology Co.,Ltd. (http://www.jieqi.com)
 * @author     $Author: juny $
 * @version    $Id: block_userstatus.php 344 2009-06-23 03:06:07Z juny $
 */

class BlockSystemUserstatus extends JieqiBlock
{
	var $module='system';
	var $template='block_userstatus.html';
	var $cachetime = -1;

	function BlockSystemUserstatus(){
		$this->JieqiBlock($vars);
		$this->blockvars['cacheid'] = intval($_SESSION['jieqiUserId']);
	}

	function setContent(){
		global $jieqiTpl;
		global $jieqiGroups;
		global $jieqiConfigs;
		global $jieqi_image_type;
		global $jieqiModules;
		global $jieqiUsersStatus;
		global $jieqiUsersGroup;

		if (!empty($_SESSION['jieqiUserId'])){
			if($jieqiUsersStatus == JIEQI_GROUP_GUEST){
				$jieqiTpl->assign('jieqi_newmessage', 0);
				$jieqiTpl->assign('jieqi_userid', 0);
				$jieqiTpl->assign('jieqi_username', '');
				$jieqiTpl->assign('jieqi_useruname', '');
				$jieqiTpl->assign('jieqi_group', JIEQI_GROUP_GUEST);
				$jieqiTpl->assign('jieqi_groupname', $jieqiGroups[JIEQI_GROUP_GUEST]);
				$jieqiTpl->assign('jieqi_score', 0);
				$jieqiTpl->assign('jieqi_experience', 0);
				$jieqiTpl->assign('jieqi_honor', '');
				$jieqiTpl->assign('jieqi_vip', 0);
				$jieqiTpl->assign('jieqi_egold', 0);
				$jieqiTpl->assign('jieqi_avatar', 0);
			}else{
				$jieqiTpl->assign('jieqi_userid', $_SESSION['jieqiUserId']);
				$jieqiTpl->assign('jieqi_username', jieqi_htmlstr($_SESSION['jieqiUserName']));
				$jieqiTpl->assign('jieqi_useruname', jieqi_htmlstr($_SESSION['jieqiUserUname']));
				$jieqiTpl->assign('jieqi_group', $_SESSION['jieqiUserGroup']);
				$jieqiTpl->assign('jieqi_groupname', $jieqiGroups[$_SESSION['jieqiUserGroup']]);
				$jieqiTpl->assign('jieqi_score', $_SESSION['jieqiUserScore']);
				$jieqiTpl->assign('jieqi_experience', $_SESSION['jieqiUserExperience']);
				$jieqiTpl->assign('jieqi_honor', $_SESSION['jieqiUserHonor']);
				$jieqiTpl->assign('jieqi_vip', $_SESSION['jieqiUserVip']);
				$jieqiTpl->assign('jieqi_egold', $_SESSION['jieqiUserEgold']);
				$jieqiTpl->assign('jieqi_avatar', $_SESSION['jieqiUserAvatar']);
				if(isset($_SESSION['jieqiNewMessage']) && $_SESSION['jieqiNewMessage']>0) $jieqiTpl->assign('jieqi_newmessage', $_SESSION['jieqiNewMessage']);
				else $jieqiTpl->assign('jieqi_newmessage', 0);
			}
			$jieqiTpl->assign('jieqi_userstatus', $jieqiUsersStatus);

			//��ʾ����
			if(!empty($jieqiModules['badge']['publish']) && is_file($jieqiModules['badge']['path'].'/include/badgefunction.php')){
				include_once($jieqiModules['badge']['path'].'/include/badgefunction.php');
				//�ȼ�����
				$jieqiTpl->assign('jieqi_group_imageurl', getbadgeurl(1, $_SESSION['jieqiUserGroup'], 0, true));
				//ͷ�λ���
				$jieqiTpl->assign('jieqi_honor_imageurl', getbadgeurl(2, $_SESSION['jieqiUserHonorid'], 0, true));
				//�Զ������
				if(!empty($_SESSION['jieqiUserBadges'])) $badgeary=unserialize($_SESSION['jieqiUserBadges']);
				else $badgeary = array();
				$jieqi_jieqi_badgerows=array();
				if(is_array($badgeary)){
					$k=0;
					foreach($badgeary as $badge){
						$jieqi_badgerows[$k]['imageurl']=getbadgeurl($badge['btypeid'], $badge['linkid'], $badge['imagetype']);
						$jieqi_badgerows[$k]['caption']=jieqi_htmlstr($badge['caption']);
						$k++;
					}
				}
				$jieqiTpl->assign_by_ref('jieqi_badgerows', $jieqi_badgerows);
				$jieqiTpl->assign('jieqi_use_badge', 1);
			}else{
				$jieqiTpl->assign('jieqi_use_badge', 0);
			}
		}else{
			return false;
		}
	}

}


?>