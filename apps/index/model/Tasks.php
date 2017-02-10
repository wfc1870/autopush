<?php
namespace app\index\model;
use think\Db;

class Tasks extends \think\Model{
	public function _construct(){
		
	}
	
	public function log($text){
		echo "[".date('y-m-d H:i:s')."] ".$text."\n";
	}

	//微信推送维保员下月应保养的电梯
	public function proximoPush(){
		$weObj = new \weixin\TPWechat(C('wx_config'));
		//明月需要保养电梯
		$maps = [
			'isdel'=>false,
			'nextMaintain'=>array('between',array(strtotime("+1 months",strtotime(date('Y-m').'-1')),strtotime("+2 months",strtotime(date('Y-m').'-1'))-60*60*24)),
		];
		$clientgroup=Db::table('clientgroup')->field('wxTemplate')->where(['_id'=>'57143ae1fe9692241d000029'])->find();
		$clientgroups = Db::table('elevator')->where($maps)->distinct('clientgroup.id');
		foreach($clientgroups as $value){
			$count = Db::table('elevator')->where(array_merge($maps,array('clientgroup.id'=>$value)))->count();
			$clientgroupinfo=Db::table('clientgroup')->field('name')->where(['_id'=>$value,'isdel'=>false])->find();
			if($count>0&&$clientgroupinfo){
				$this->log("推送给维保公司：".$clientgroupinfo['name']);
				$this->log("日期：".date('Y年m月d日').",下月需保养".$count."台电梯.");
				$template = array(
					'touser'=>'',
					'template_id'=>$clientgroup['wxTemplate']['57561f70b8a752482a1a7ba1']['id'],
					'url'=>'http://weibao.dtfwgj.com/Weixin/Weibao/proximopushlist/57143ae1fe9692241d000029/'.date('Y-m'),
					'topcolor'=>'#FF0000',
					'data'=>array(
						'first' => array('value' =>'下月需保养的电梯'),
			            'keyword1' => array('value' =>"共计".$count."台.",'color'=>'#FF0000'),
			            'keyword2' => array('value' =>date('Y-m-d H:i:s')),
			            'keyword3' => array('value' => ''),
			            'keyword4' => array('value' => ''),
			            'keyword5' => array('value' => ''),
			            'remark' => array('value' => ''),
					)
				);
				$allstaff=Db::table('staff')->field('openids,name')->where(['role.fid'=>'56f4e7edfe9692643300002b','isdel'=>false,'clientgroup.id'=>$value])->select();
				$this->log("开始给维保员推送微信消息");
				foreach($allstaff as $staffs){
					$issend = false;
					$this->log("给维保员:".$staffs['name']."推送微信消息.");
					if(!empty($staffs['openids'][$clientgroup['_id']])){
						$template['touser'] = $staffs['openids'][$clientgroup['_id']];
						$issend = $weObj->sendTemplateMessage($template);
						$template['name'] = $staffs['name'];
						Db::table('wxpush')->insert(array_merge($template,['type'=>'auto','action'=>'proximo','time'=>time(),'issend'=>$issend]));
						$this->log($issend?'推送成功.':'推送失败.');
					}
					else{
						$this->log('没有绑定.');
					}
				}				
			}
		}
		
	}
	
	//微信推送，给维保员需要保养的电梯
	public function bypush(){
		$weObj = new \weixin\TPWechat(C('wx_config'));
		//明日需要保养电梯
		$maps = [
			'isdel'=>false,
			'nextMaintain'=>(strtotime(date('Y-m-d'))+60*60*24),
		];
		$clientgroup=Db::table('clientgroup')->field('wxTemplate')->where(['_id'=>'57143ae1fe9692241d000029'])->find();
		$clientgroups = Db::table('elevator')->distinct('clientgroup.id',$maps);
		foreach($clientgroups as $value){
			$count = Db::table('elevator')->where(array_merge($maps,['clientgroup.id'=>$value]))->count();
			$clientgroupinfo=Db::table('clientgroup')->field('name')->where(['_id'=>$value,'isdel'=>false])->find();
			if($count>0&&$clientgroupinfo){
				$this->log("推送给维保公司：".$clientgroupinfo['name']);
				$this->log("日期：".date('Y年m月d日').",今天需保养".$count."台电梯.");
				$template = array(
					'touser'=>'',
					'template_id'=>$clientgroup['wxTemplate']['57561f70b8a752482a1a7ba1']['id'],
					'url'=>'http://weibao.dtfwgj.com/Weixin/Weibao/pushplanlist/57143ae1fe9692241d000029/'.(strtotime(date('Y-m-d'))+60*60*24),
					'topcolor'=>'#FF0000',
					'data'=>array(
						'first' => array('value' =>'明天需保养的电梯'),
			            'keyword1' => array('value' =>"共计".$count."台.",'color'=>'#FF0000'),
			            'keyword2' => array('value' =>date('Y-m-d H:i:s')),
			            'keyword3' => array('value' => ''),
			            'keyword4' => array('value' => ''),
			            'keyword5' => array('value' => ''),
			            'remark' => array('value' => ''),
					)
				);
				$allstaff=Db::table('staff')->field('openids,name')->where(['role.fid'=>'56f4e7edfe9692643300002b','isdel'=>false,'clientgroup.id'=>$value])->select();
				$this->log("开始给维保员推送微信消息");
				foreach($allstaff as $staffs){
					$issend = false;
					$this->log("给维保员:".$staffs['name']."推送微信消息.");
					if(!empty($staffs['openids'][$clientgroup['_id']])){
						$template['touser'] = $staffs['openids'][$clientgroup['_id']];
						$issend = $weObj->sendTemplateMessage($template);
						$template['name'] = $staffs['name'];
						Db::table('wxpush')->insert(array_merge($template,['type'=>'auto','action'=>'bypush','time'=>time(),'issend'=>$issend]));
						$this->log($issend?'推送成功.':'推送失败.');
					}
					else{
						$this->log('没有绑定.');
					}
				}				
			}
		}
	}

	//微信推送未完成工单给维保员
	public function notFinishPush(){
		$weObj = new \weixin\TPWechat(C('wx_config'));
		//明月需要保养电梯
		$maps = [
			'isdel'=>false,
		];
		$mapsOr=['endTime'=>0,'issign'=>array('neq',true),'image.gdCount'=>array('exists',false)];
		$clientgroup=Db::table('clientgroup')->field('wxTemplate')->where(['_id'=>'57143ae1fe9692241d000029'])->find();
		$allstaff=Db::table('staff')->field('openids,name')->where(['role.fid'=>'56f4e7edfe9692643300002b','isdel'=>false])->select();
		foreach($allstaff as $value){
			$repairCount = Db::table('repairorder')->whereOr($mapsOr)->where(array_merge($maps,['clientgroup.staff.id'=>$value['_id']]))->count();
			$maintainCount=Db::table('maintainorder')->whereOr($mapsOr)->where(array_merge($maps,['clientgroup.staff.id'=>$value['_id']]))->count();
			if($repairCount>0||$maintainCount>0){
				$this->log("推送给维保员：".$value['name']);
				$this->log("日期：".date('Y年m月d日').",未完成保养工单".$maintainCount."个,未完成维修工单".$repairCount.'个');
				$template = array(
					'touser'=>'',
					'template_id'=>$clientgroup['wxTemplate']['586c828eb8a752734d2fb19b']['id'],
					'url'=>'http://weibao.dtfwgj.com/Weixin/Weibao/notfinishlist/57143ae1fe9692241d000029/'.(strtotime(date('Y-m-d'))),
					'topcolor'=>'#FF0000',
					'data'=>array(
						'first' => array('value' =>'您好，电梯服务管家系统查询到以下工单需要您及时处理'),
			            'keyword1' => array('value' =>'共计【'.($maintainCount+$repairCount).'】单'),
			            'keyword2' => array('value' =>'工单尚未完成或完整'),
			            'keyword3' => array('value' => $value['name']),
			            'keyword4' => array('value' => ''),
			            'keyword5' => array('value' => ''),
			            'remark' => array('value' => '其中保养工单【'.$maintainCount.'】单,维修工单【'.$repairCount.'】单,请点击详情查阅'),
					)
				);
				$issend = false;
				$template['touser'] = $value['openids'][$clientgroup['_id']];
				$issend = $weObj->sendTemplateMessage($template);
				$template['name'] = $value['name'];
				Db::table('wxpush')->insert(array_merge($template,['type'=>'auto','action'=>'notFinish','time'=>time(),'issend'=>$issend]));
				$this->log($issend?'推送成功.':'推送失败.');
			}				
		}
	}
	//余额不足微信推送
	public function balancePush(){
		$weObj = new \weixin\TPWechat(C('wx_config'));
		//明月需要保养电梯
		$maps = [
			'isdel'=>false,
			'charging.balance'=>['lt',5000000]
		];
		$clientgroup=Db::table('clientgroup')->field('wxTemplate')->where(['_id'=>'57143ae1fe9692241d000029'])->find();
		$groups=['clientgroup'];
		foreach($groups as $group){
			$groupinfo=Db::table($group)->field('name,charging')->where($maps)->select();
			foreach($groupinfo as $value){
				if(is_numeric($value['charging']['balance'])){
					$this->log("推送给企业负责人：".$value['name']);
					$this->log("日期：".date('Y年m月d日')."余额为".$value['charging']['balance']);
					$template = array(
						'touser'=>'',
						'template_id'=>$clientgroup['wxTemplate']['58705f8eb8a752601708702b']['id'],
						//'url'=>'http://'.$_SERVER['SERVER_NAME'].'/Weixin/Weibao/wysigndetail/'.$id.'/'.$orderid,
						'topcolor'=>'#FF0000',
						'data'=>array(
							'first' => array('value' =>'尊敬的企业负责人,您当前账号余额不足'),
				            'keyword1' => array('value' =>$value['name']),
				            'keyword2' => array('value' =>((float)$value['charging']['balance']/100).'元'),
				            'keyword3' => array('value' => ''),
				            'keyword4' => array('value' => ''),
				            'keyword5' => array('value' => ''),
				            'remark' => array('value' => '感谢您的支持，望生意兴隆'),
						)
					);
					$allstaff=Db::table('staff')->field('openids,name')->where(['clientgroup.id'=>$value['_id'],'role.id'=>'56f4e811fe9692643300002d','isdel'=>false])->select();
					$this->log("开始给维保经理推送微信消息");
					foreach($allstaff as $staffs){
						$issend = false;
						$this->log("给维经理:".$staffs['name']."推送微信消息.");
						if(!empty($staffs['openids'][$clientgroup['_id']])){
							$template['touser'] = $staffs['openids'][$clientgroup['_id']];
							$issend = $weObj->sendTemplateMessage($template);
							$template['name'] = $staffs['name'];
							Db::table('wxpush')->insert(array_merge($template,['type'=>'auto','action'=>'balancepush','time'=>time(),'issend'=>$issend]));
							$this->log($issend?'推送成功.':'推送失败.');
						}
						else{
							$this->log('没有绑定.');
						}
					}			
				}
			}
		}
		
	}
	//微信推送，超期未保的电梯
	public function exceedPush(){
		$weObj = new \weixin\TPWechat(C('wx_config'));
		//明日需要保养电梯
		$maps = [
			'isdel'=>false
		];
		$clientgroup=Db::table('clientgroup')->field('wxTemplate')->where(['_id'=>'57143ae1fe9692241d000029'])->find();
		$clientgroups = Db::table('elevator')->distinct('clientgroup.id',$maps);
		foreach($clientgroups as $value){
			$spaces = Db::table('elevator')->where(array_merge($maps,['clientgroup.id'=>$value]))->distinct('contract.space');
			$count=0;
			foreach($spaces as $space){
				if($space<1)
					$space=15;
				$count+=Db::table('elevator')->where(array_merge($maps,['clientgroup.id'=>$value,'contract.space'=>$space,'lastMaintain.time'=>array('lt',strtotime(date('Y-m-d'))-$space*24*60*60)]))->count();
			}
			$clientgroupinfo=Db::table('clientgroup')->field('name')->where(['_id'=>$value,'isdel'=>false])->find();
			if($count>0&&$clientgroupinfo){
				$this->log("推送给维保公司：".$clientgroupinfo['name']);
				$this->log("日期：".date('Y年m月d日').",超期未保".$count."台电梯.");
				$template = array(
					'touser'=>'',
					'template_id'=>$clientgroup['wxTemplate']['57561f70b8a752482a1a7ba1']['id'],
					'url'=>'http://weibao.dtfwgj.com/Weixin/Weibao/exceedpushlist/57143ae1fe9692241d000029/'.(strtotime(date('Y-m-d'))),
					'topcolor'=>'#FF0000',
					'data'=>array(
						'first' => array('value' =>'超期未保的电梯','color'=>'#FF0000'),
			            'keyword1' => array('value' =>"共计".$count."台.",'color'=>'#FF0000'),
			            'keyword2' => array('value' =>date('Y-m-d H:i:s'),'color'=>'#FF0000'),
			            'keyword3' => array('value' => ''),
			            'keyword4' => array('value' => ''),
			            'keyword5' => array('value' => ''),
			            'remark' => array('value' => ''),
					)
				);
				$allstaff=Db::table('staff')->field('openids,name')->where(['role.fid'=>'56f4e7edfe9692643300002b','isdel'=>false,'clientgroup.id'=>$value])->select();
				$this->log("开始给维保员推送微信消息");
				foreach($allstaff as $staffs){
					$issend = false;
					$this->log("给维保员:".$staffs['name']."推送微信消息.");
					if(!empty($staffs['openids'][$clientgroup['_id']])){
						$template['touser'] = $staffs['openids'][$clientgroup['_id']];
						$issend = $weObj->sendTemplateMessage($template);
						$template['name'] = $staffs['name'];
						Db::table('wxpush')->insert(array_merge($template,['type'=>'auto','action'=>'exceed','time'=>time(),'issend'=>$issend]));
						$this->log($issend?'推送成功.':'推送失败.');
					}
					else{
						$this->log('没有绑定.');
					}
				}				
			}
		}
	}
	//扣费服务
	public function autoCharging(){
		$maps = [
			'isdel'=>false,
			'charging.fees'=>true,
			'charging.start'=>array('lte',time()),
			'charging.end'=>array('gt',time())
		];
		$this->log("开始扣费");
		$clientgroups = Db::table('clientgroup')->field('_id,name,charging')->where($maps)->select();
		$feenum=Db::table('species')->field('notice')->where(array('isdel'=>false,'_id'=>'586f558eb8a752a95d08702a'))->find();
		foreach($clientgroups as $value){
			$count=Db::table('elevator')->where(array('isdel'=>false,'clientgroup.id'=>$value['_id']))->count();
			$this->log("开始扣费,公司名称：".$value['name']."电梯数量:".$count."台");
			Db::table('clientgroup')->where(array('isdel'=>false,'_id'=>$value['_id']))->update(array('charging.balance'=>intval($value['charging']['balance'])-$count*intval($feenum)));
			$postinfo=array (
			  'clientgroup' => 
			  array (
			    'id' => $value['_id'],
			    'str' =>$value['name'],
			  ),
			  'type' => 
			  array (
			    'id' => '586f558eb8a752a95d08702a',
			    'str' => '日服务费',
			  ),
			  'inTime' => time(),
			  'money' => intval($value['charging']['balance'])-$count*intval($feenum),
			  'ip' => '127.0.0.1',
			  'port' => '80',
			  'cost' => $count*intval($feenum),
			  'inUser' => 
			  array (
			    'str' => '系统自动扣减',
			  ),
			  'isdel'=>false,
			);
			Db::table('expense')->insert($postinfo);
			$this->log("扣费金额：".$count*intval($feenum));
		}
		$this->log("扣费结束.");
	}
}