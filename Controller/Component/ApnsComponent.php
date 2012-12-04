<?php
App::uses('Component', 'Controller');
require_once(App::pluginPath('CakeApns') . 'Vendor' . DS . 'ApnsPHP' . DS .'ApnsPHP' . DS .'Autoload.php');

class ApnsComponent extends Component {
	public $env;
	public $app_cert_path;
	public $entrust_cert_path;
    public $combined_cert_path;
    public $cert_passphrase;

	public $identifier = 'CakeApns';
	public $expiry = 30;

    private function __loadConfig() {
        $apns = Configure::read('CakeApns');
        if(!$apns) throw new CakeException(__('Missing CakeApns configurations'));
        if(!$apns['mode'] || !in_array($apns['mode'], array('sandbox', 'production')))
            throw new CakeException(__('Valid modes are "sandbox" & "production" only'));

        if(!$apns[$apns['mode']]['combined_certificates'] || empty($apns[$apns['mode']]['combined_certificates'])) 
            throw new CakeException(__('Specify ' . $apns['mode'] . ' Combined Certificates'));

        $this->combined_cert_path = $apns[$apns['mode']]['combined_certificates'];
        $this->cert_passphrase = $apns[$apns['mode']]['cert_passphrase'];
        
        $this->env = $apns['mode']=='sandbox' 
            ? ApnsPHP_Abstract::ENVIRONMENT_SANDBOX 
            : ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION;
    }

    public function startup() {
        $this->__loadConfig();
        if(!file_exists($this->combined_cert_path)) {
			throw new CakeException(__("Certification at $this->combined_cert_path does not exist."));
        }
	}

    public function push($device_token, $text, $options = array()) {
        $push = new ApnsPHP_Push($this->env, $this->combined_cert_path);
        $push->setProviderCertificatePassphrase($this->cert_passphrase);

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
		$feedback = new ApnsPHP_Feedback($env, $this->combined_cert_path);
		$feedback->connect();
		$error = $feedback->receive();
		$feedback->disconnect();
		return $error;
	}

}
