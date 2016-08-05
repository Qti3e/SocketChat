<?php

/*****************************************************************************
 *         In the name of God the Most Beneficent the Most Merciful          *
 *___________________________________________________________________________*
 *   This program is free software: you can redistribute it and/or modify    *
 *   it under the terms of the GNU General Public License as published by    *
 *   the Free Software Foundation, either version 3 of the License, or       *
 *   (at your option) any later version.                                     *
 *___________________________________________________________________________*
 *   This program is distributed in the hope that it will be useful,         *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           *
 *   GNU General Public License for more details.                            *
 *___________________________________________________________________________*
 *   You should have received a copy of the GNU General Public License       *
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.   *
 *___________________________________________________________________________*
 *                             Created by  Qti3e                             *
 *        <http://Qti3e.Github.io>    LO-VE    <Qti3eQti3e@Gmail.com>        *
 *****************************************************************************/


include "lib/credis.php";
include "lib/users.php";
include "lib/websockets.php";
class server extends WebSocketServer{
	protected $_users;
	protected $emails;
	protected $redis;
	public function __construct($addr, $port, $bufferLength = 2048) {
		parent::__construct($addr, $port, $bufferLength);
		$this->redis    = new Credis_Client('127.0.0.1',6379);
	}

	/**
	 * @param WebSocketUser $user
	 * @param $message
	 *
	 * @return void
	 */
	public function process($user, $message) {
		//@ -> command
		//! -> msg
		$body   = substr($message,1);
		switch ($message[0]){
			case '@':
				$body   = json_decode($body,true);
				$username   = $body['username'];
				$email      = $body['email'];
				if(strlen($username) > 20 || strlen($username) < 3 || isset($this->_users[strtolower($username)]) ||
					!filter_var($email,FILTER_VALIDATE_EMAIL) || isset($this->emails[strtolower($email)])){
					$this->send($user,'login_nok');
					return;
				}
				$user->username = $username;
				$user->email    = $email;
				$user->avatar   = md5(strtolower(trim($email)));
				$user->lock     = false;
				$this->_users[strtolower($username)] = true;
				$this->emails[strtolower($email)]   = true;
				$this->send($user,'login_ok');
				$this->send2all('@'.json_encode(['s'=>'join','username'=>$user->username,'avatar'=>$user->avatar]));
				break;
			case '!':
				if($user->lock){
					return;
				}
				$body   = htmlspecialchars($body);
				/**
				 * id => {message sender time}
				 */
				$id     = $this->redis->incr('LastMsgId');
				$msg    = [
					'time'  => time(),
					'date'  => date('D, d M Y H:i:s'),
					'name'  => $user->username,
					'avatar'=> $user->avatar,
					'msg'   => $body
				];
				$msg    = json_encode($msg);
				$this->redis->set('msg_'.$id,$msg);
				$this->send2all('!'.$msg);
				break;
		}
	}

	/**
	 * @param WebSocketUser $user
	 *
	 * @return void
	 */
	public function connected($user){
		$user->lock = true;
		$this->send($user,'@'.json_encode(['s'=>'usersList','list'=>$this->onlineList()]));
	}

	/***
	 * @param WebSocketUser $user
	 *
	 * @return void
	 */
	public function closed($user) {
		unset($this->_users[strtolower($user->username)]);
		unset($this->emails[strtolower($user->email)]);
		$this->send2all('@'.json_encode(['s'=>'left','username'=>$user->username,'avatar'=>$user->avatar]));
	}

	/**
	 * @return array
	 */
	protected function onlineList(){
		$re     = [];
		$keys   = array_keys($this->users);
		$count  = count($this->users);
		for($i  = 0;$i < $count;$i++){
			$key= $keys[$i];
			$val= $this->users[$key];
			if(!$val->lock){
				$re[]   = [
					$val->username,
					$val->avatar
				];
			}
		}
		return $re;
	}

	/**
	 * @param $msg
	 *
	 * @return void
	 */
	protected function send2all($msg){
		$keys   = array_keys($this->users);
		$count  = count($this->users);
		for($i  = 0;$i < $count;$i++){
		    $key= $keys[$i];
		    $val= $this->users[$key];
		    $this->send($val,$msg);
		}
	}
}
$server = new server('127.0.0.1',7777);
$server->run();