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
    private $__logEnabled = false;
    private $__push;
    private $__queueCount = 0;
    private $__sendRetryTimes = 3;
    private $__errors = array();

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

    public function __construct(ComponentCollection $collection, $settings = array()) {
        parent::__construct($collection, $settings);
        if(isset($settings['logEnabled'])) 
            $this->__logEnabled = $settings['logEnabled'] ;
    }

    public function setSendRetryTimes($times=3) {
        $this->__sendRetryTimes = $times;
    }
    
    public function getSendRetryTimes() {
        return $this->__sendRetryTimes;
    }

    private function __connect() {
        if(!$this->__push) { 
            $this->__push = new ApnsPHP_Push($this->env, $this->combined_cert_path);
            $this->__push->setProviderCertificatePassphrase($this->cert_passphrase);

            $logger = new ApnsPHP_Log_Custom(!$this->__logEnabled); 
            $this->__push->setLogger($logger);
            //$this->__push->setSendRetryTimes($this->__sendRetryTimes);
            $this->__push->connect();
            return $this->__logError();
        }
        return true;
    }

    public function startup() {
        $this->__loadConfig();
        if(!file_exists($this->combined_cert_path)) {
			throw new CakeException(__("Certification at $this->combined_cert_path does not exist."));
        }
	}

    private function __queue($device_token, $text, $options = array(), $sound='default') {
        $this->__connect();
        $message = new ApnsPHP_Message($device_token);
		$message->setText($text);
		$message->setCustomIdentifier(isset($options['identifier'])
			? $options['identifier'] : $this->identifier);
		$message->setExpiry(isset($options['identifier'])
			? $options['expiry'] : $this->expiry);
        if(isset($options['custom_properties'])) {
            foreach($options['custom_properties'] as $key => $property) {
                $message->setCustomProperty($key, $property);
            }
        }
        $message->setSound($sound);
        $this->__push->add($message);
    }

    public function add($device_token, $text, $options = array(), $sound='default') {
        $this->__connect();
        try {
            $this->__queue($device_token, $text, $options, $sound);
            $this->__queueCount += 1;
        }
        catch(ApnsPHP_Message_Exception $e) {
            $this->__logError($e->getMessage());
        }
    }

    public function pushMany() {
        if($this->__queueCount) {
		    $this->__push->send();
		    $this->__push->disconnect();
            return $this->__logError();
        }
        throw new CakeException('Nothing to send in bulk queue is empty');    
    }

    public function push($device_token, $text, $options = array(), $sound='default') {
        $this->__connect();
        try {
            $this->__queue($device_token, $text, $options, $sound);
        }
        catch(ApnsPHP_Message_Exception $e) {
            $this->__logError($e->getMessage());
        }
		$this->__push->send();
		$this->__push->disconnect();
	    return $this->__logError();	
	}

    public function getErrors() {
        return $this->__errors;
    }

    private function __logError($otherError=null) {
        if(!$this->__push) return false;
        $errors = $this->__push->getErrors();
        if($otherError) $errors[] = $otherError;
		if(empty($errors)) {
			return true;
        } else {
            $this->__errors = $errors;
            CakeLog::write(__CLASS__, json_encode($errors));
			return false;
		}
    }

	/*public function feedback() {
		$feedback = new ApnsPHP_Feedback($env, $this->combined_cert_path);
		$feedback->connect();
		$error = $feedback->receive();
		$feedback->disconnect();
		return $error;
    }*/

}
