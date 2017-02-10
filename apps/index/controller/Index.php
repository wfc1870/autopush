<?php
namespace app\index\controller;
use think\Db;
class Index
{
    private function index(){
		$item = M('Staff')->find();
		$weObj = new \weixin\Wxauth(C('wx_config'));
    	print_r($weObj);
    }
	public function find(){
       $item = M('staff')->find();
       return json($item);
    }
	public function run(){
        ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
		set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
		ini_set('memory_limit','512M'); // 设置内存限制
		$interval=1;//每隔1秒监视运行.
		M('tasks')->log('每隔'.$interval.'秒监视运行');
		do{
			//定时按小时工作
			$hour = date('H');
			switch ($hour){
				case 0:{
					if(date('i') == 0&& date('s') == 0){
						M('tasks')->autoCharging();
					}	
					break;
				}
				case 8:{
					if(date('i') == 30&& date('s') == 0){
						M('tasks')->exceedPush();
					}	
					break;
				}
				case 9:{
					if(date('i') == 0&& date('s') == 0){
						M('tasks')->balancePush();
					}
					break;
				}
				case 15:{
					if(date('i') == 30&& date('s') == 0){
						M('tasks')->bypush();
						M('tasks')->balancePush();
						M('tasks')->notFinishPush();
					}
					break;
				}
				case 17:{
					if(date('D') == cal_days_in_month(CAL_GREGORIAN,date('m'), date('Y')) && date('i') == 0 && date('s') == 0){
						M('tasks')->proximoPush();
					}
					
					break;
				}
				default:{
				}
			}
		   sleep($interval);
		}
		while(true);
    }
	public function select(){
       $item =Db::table('elevator')->field('clientgroup')->where(['clientgroup.id'=>'57143ae1fe9692241d000029','isdel'=>false,])->limit(10)->select();
       print_r($item);
    }
	public function push(){
		M('tasks')->bypush();
//		M('tasks')->proximoPush();
//		M('tasks')->notFinishPush();
//		M('tasks')->balancePush();
//		M('tasks')->exceedPush($weObj);	
//		M('tasks')->autoCharging($weObj);	
	}
	public function _empty($action){
       return json(['action'=>$action,'code'=>1,'message'=>'操作完成']);
    }
}
