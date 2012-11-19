<?php
App::uses('Component', 'Controller');
App::import('Vendor', 'ApnsPHP/Autoload');

class ApnsComponent extends Component {
	public $env;
	public $app_cert_path;
	public $entrust_cert_path;
	public $identifier = 'CakeApns';
	public $expiry = 30;

	public function startup($controller) {
		if(empty($this->env)) {
			$this->env = (Configure::read('debug') > 0)
				? ApnsPHP_Abstract::ENVIRONMENT_SANDBOX
				: ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION;
		}

		if(!file_exists($this->app_cert_path)) {
			trigger_error("Certification at $this->app_cert_path does not exist.", E_USER_ERROR);
		}

		if(!file_exists($this->entrust_cert_path)) {
			trigger_error("Certification at $this->entrust_cert_path does not exist.", E_USER_ERROR);
		}
	}

	public function push($device_token, $text, $options = array()) {
		$push = new ApnsPHP_Push($this->env, $this->app_cert_path);
		$push->setRootCertificationAuthority($this->entrust_cert_path);

		$push->connect();

		$message = new ApnsPHP_Message($device_token);
		$message->setText($text);

		$message->setCustomIdentifier(isset($options['identifier'])
			? $options['identifier'] : $this->identifier);
		$message->setExpiry(isset($options['identifier'])
			? $options['expiry'] : $this->expiry);

		$push->add($message);
		$push->send();

		$push->disconnect();

		$error = $push->getErrors();
		if(empty($error)) {
			return true;
		} else {
			Debugger::log($error);
			return false;
		}
	}

	public function feedback() {
		$feedback = new ApnsPHP_Feedback($env, $this->app_cert_path);
		$feedback->connect();
		$error = $feedback->receive();
		$feedback->disconnect();
		return $error;
	}

}
