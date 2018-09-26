<?php
namespace App\Extensions;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

//参考了https://medium.com/@sirajul.anik/laravel-api-authenticate-user-with-custom-driver-different-table-using-auth-middleware-fa2cabec2d61

class WX3rdSessionGuard implements Guard
{
	use GuardHelpers;
	private $inputKey = '';
	private $storageKey = '';
	private $request;
	public function __construct (UserProvider $provider, Request $request, $configuration) {
		$this->provider = $provider;
		$this->request = $request;
		// key to check in request:此处将Session当做token
		$this->inputKey = isset($configuration['input_key']) ? $configuration['input_key'] : '3rdSession';
		// key to check in database
		$this->storageKey = isset($configuration['storage_key']) ? $configuration['storage_key'] : 'id';
	}
	public function user () {
		if (!is_null($this->user)) {
			return $this->user;
		}
		$user = null;
		// retrieve via token
		$token = $this->getTokenForRequest();
		if (!empty($token)) {
			// the token was found, how you want to pass?
			$user = $this->provider->retrieveByToken($this->storageKey, $token);
		}
		return $this->user = $user;
	}
	/**
	 * Get the token for the current request.
	 * @return string
	 */
	public function getTokenForRequest () {
		//在Header中查找Token
		$token = $this->request->header($this->inputKey);
		if (empty($token)) {
			//在query中查找Token
			$token = $this->request->query($this->inputKey);
		}
		if (empty($token)) {
			//在Body中查找Token
			$token = $this->request->input($this->inputKey);
		}
		if (empty($token)) {
			//都找不到则获取bearerToken，对于微信登录无用
			$token = $this->request->bearerToken();
		}
		return $token;
	}
	/**
	 * Validate a user's credentials.
	 *
	 * @param  array $credentials
	 *
	 * @return bool
	 */
	public function validate (array $credentials = []) {
		if (empty($credentials[$this->inputKey])) {
			return false;
		}
		$credentials = [ $this->storageKey => $credentials[$this->inputKey] ];
		if ($this->provider->retrieveByCredentials($credentials)) {
			return true;
		}
		return false;
	}
}
