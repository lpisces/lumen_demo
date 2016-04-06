<?php

namespace App\Http\Controllers;
use App\User;
use Illuminate\Http\Request;
use Sms;
use DB;


class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
  public function __construct()
  {
      //
  }
    
  public function login(Request $request) 
  {
    $ret = [];
    $user = User::where('phone', $request->input('phone'))->where('member_pswd', md5($request->input('member_pswd')))->select('member_id', 'phone', 'member_name', 'token')->first();
    if ($user && 11 == strlen($request->input('phone'))) {
      $ret['err_code'] = 0;
      $ret['err_msg'] = '登录成功';
      if (32 != strlen($user->token)) {
        $user->token = User::gen_token();
        $user->save();
      }
      $user = User::where('member_id', $user->member_id)->select('member_id', 'phone', 'member_name')->first();
      $ret['data'] = $user;
      $user = User::where('member_id', $user->member_id)->select('token')->first();
      $ret['token'] = $user->token;
    } else {
      $ret['err_code'] = 1;
      $ret['err_msg'] = '登录失败';
      $ret['data'] = null;
      $ret['token'] = null;
    }
    return $ret;
  }

  public function getyzm(Request $request)
  {
    $phone = $request->input('phone');
    $last_code = DB::select('select * from sd_code where phone = ? order by time desc limit 1', [$phone]);
    $now = time();
    //生产验证码
    $code = rand(100000, 999999);
    if (count($last_code) > 0 AND abs($last_code[0]->time - $now) < 60 * 5) $code = $last_code[0]->code;

    $ret = [];
    $ret['err_code'] = 1;
    $ret['err_msg'] = '发送成功';
    $ret['data'] = $code;

    //sms运营商参数
    $account_sid = env('SMS_ACCOUNT_SID', '');
    $account_token = env('SMS_ACCOUNT_TOKEN', '');
    $app_id = env('SMS_APP_ID', '');
    $server_ip = env('SMS_SERVER_IP', '');
    $server_port = env('SMS_SERVER_PORT', '');
    $soft_version = env('SMS_SOFT_VERSION', '');
    $template_id = env('SMS_TEMPLATE_ID', 0);

    $sms = new Sms($server_ip, $server_port, $soft_version);
    $sms->setAccount($account_sid, $account_token);
    $sms->setAppId($app_id);
    
    $result = $sms->sendTemplateSMS($phone,array($code, 5),$template_id);
    if (NULL == $result OR 0 != $result->statusCode) {
      $ret['err_code'] = 2;
      $ret['err_msg'] = '发送失败.失败原因:' . $result->statusMsg;
    } else {
      if (count($last_code) > 0 AND abs($last_code[0]->time - $now) > 60 * 5) {
        DB::insert('insert into sd_code (code, phone, time) values (?, ?, ?)', [$code, $phone, $now]);
      }
      if (count($last_code) == 0) {
        DB::insert('insert into sd_code (code, phone, time) values (?, ?, ?)', [$code, $phone, $now]);
      }
    }
    return $ret;
  }

  function regsave(Request $request) 
  {
    $ret = [];
    $phone = $request->input('phone');
    $code = $request->input('code');
    $last_code = DB::select('select * from sd_code where phone = ? order by time desc limit 1', [$phone]);
    if (count($last_code) == 0 OR (count($last_code) > 0 AND $last_code[0]->code != $code)) {
      $ret['code'] = 0;
      $ret['err_msg'] = '验证码输入错误';
      $ret['data'] = array();
      return $ret;
    }
    $user = User::where('phone', $phone)->first();
    if ($user) {
      $ret['code'] = 3;
      $ret['err_msg'] = '手机号已注册';
      $ret['data'] = array();
      return $ret;
    }
    $member_pswd = $request->input('member_pswd');
    $member_name = $request->input('member_name');
    if ($member_name == '' or $member_pswd == '') {
      $ret['code'] = 2;
      $ret['err_msg'] = '注册失败';
      $ret['data'] = array();
      return $ret;
    }
    DB::insert("insert into sd_member (phone, member_pswd, member_name, token, registration_time) values (?, ?, ?, ?, ?)", [$phone, md5($member_pswd), $member_name, User::gen_token(), time()]);
    $user = User::where('phone', $phone)->select('member_id', 'phone', 'member_name', 'token')->first();
    $ret['code'] = 1;
    $ret['err_msg'] = '注册成功';
    $ret['data'] = $user;
    return $ret;
  }

  function thirdpartylogin(Request $request) 
  {
    $ret = [];
    $uid = $request->input('uid');
    $user = User::where('qquid', $uid)->or_where('wxuid', $uid)->first();  
    if ($user) {
      $ret['err_code'] = 1;
      $ret['err_msg'] = '登录成功, 欢迎回来';
      $ret['data'] = $user;
      $ret['token'] = $user->token;
      $ret['nickname'] = $request->input('nickname');
    } else {
      $ret['err_code'] = 2;
      $ret['err_msg'] = '您还没有绑定用户';
      $ret['data'] = $user;
      $ret['token'] = '';
      $ret['nickname'] = $request->input('nickname');
    }
    return $ret;
  }

  function bindingaccount()
  {
    $ret = [];
    $user = User::where('member_name', $request->input('name'))->first();
    if (null == $user) {
      $ret['err_code'] = 3;
      $ret['err_msg'] = '没有此用户';
      $ret['data'] = [];
      $ret['token'] = '';
      return $ret;
    }
    if (md5($request->input('member_pswd')) != $user->member_pswd) {
      $ret['err_code'] = 2;
      $ret['err_msg'] = '密码错误';
      $ret['data'] = [];
      $ret['token'] = '';
      return $ret;
    }
    if ($request->input('type') == 1) {
      $user->qquid = $request->input('uid');
    }
    if ($request->input('type') == 2) {
      $user->wxuid = $request->input('uid');
    }
    if ($user->save()) {
      $ret['err_code'] = 1;
      $ret['err_msg'] = '绑定用户成功';
      $ret['data'] = $user;
      $ret['token'] = $user->token;
      return $ret;
    }
    $ret['err_code'] = 4;
    $ret['err_msg'] = '未知错误';
    $ret['data'] = [];
    $ret['token'] = '';
    return $ret;
  }

  function resetpwd(Request $request) 
  {
    $ret = [];
    $last_code = DB::select('select * from sd_code where phone = ? and code = ? order by time desc limit 1', [$request->input('phone'), $request->input('code')]);
    if (count($last_code) == 0 OR (abs($last_code[0]->time - time()) > 60 * 5) OR ($last_code[0]->code != $request->input('code'))) {
      $ret['err_code'] = 0;
      $ret['err_msg'] = '验证码输入错误';
      $ret['data'] = [];
      return $ret;
    }
    $user = User::where('phone', $request->input('phone'))->get('member_id', 'phone', 'member_name', 'token')->first();
    if ($user) {
      $user->member_pswd = $request->input('member_pswd');
      $user->save();
      $ret['err_code'] = 1;
      $ret['err_msg'] = '重置密码成功';
      $ret['data'] = $user;
    } else {
      $ret['err_code'] = 2;
      $ret['err_msg'] = '用户不存在';
      $ret['data'] = [];
    }
    return $ret;
  }
  
}
