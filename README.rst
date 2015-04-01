=================================================
Welcome to CakeApns a CakePHP Plugin
=================================================

``CakeApns`` aimed to be a full wrapper of ApnsPHP_ for CakePHP.   

Features
------------------

- A CakePHP component for pushing messages to APNS
- Feedback Service wrapper (TODO)
- Wrapper for ApnsPHP server (TODO)

Installation
--------------
Make sure you properly baked your app::

    cake bake myapp
  
and provide the following parameters for your ``myapp``, database setup and some other stuffs.


Clone the plugin inside your ``myapp/Plugin`` directory and update the submodules::

    git clone http://[your_username]@202.172.229.26/rhodecode/CakeApns
    cd CakeApns
    git submodule init
    git submodule update

Activate the plugin, in your ``myapp/Config/bootstrap.php`` add this::

    CakePlugin::loadAll(array(
        'CakeApns'
    ));

Configure you APNS certificates in your ``myapp/Config/core.php``::

    Configure::write('CakeApns', array(
        'mode' => 'sandbox',
        'sandbox' => array(
            'combined_certificates' => APP . 'Certificates' . DS . 'combined.pem',
            'cert_passphrase' => 'lightning@sg'
        ),
        'production' => array(
            'combined_certificates' => APP . 'Certificates' . DS . 'combined.pem',
            'cert_passphrase' => 'lightning@sg'
        )
    ));


Usage
--------------

Add CakeApns as component in your controller and send a message::
    
    public $components = array(
        'CakeApns.Apns',
    );

    public function index() {
        $this->Apns->push('device token', 'message')
    }

By default logging were disabled if you want to see the logs returned by APNS::

    public $components = array(
        'CakeApns.Apns' => array('logEnabled' => true),
    );

Adding custom properties and custom sound::
        
    $options = array('custom_properties' => array('my_property'=>'my_value'));
    $sound = 'my_custom_sound.aiff';
    
    $this->Apns->push('device token', 'message', $options, $sound);


Sending messages in one push::

    $this->Apns->add('device token', 'message', $options, $sound);
    $this->Apns->add('antoher device token', 'message', $options, $sound);
    ...
    $this->Apns->pushMany();
    
License
-------

``CakeApns`` is released under the WTFPL_ license.

Support
-----------------

Send me_ a bottle of beer or FORK_ it! :) 

.. _WTFPL: http://sam.zoy.org/wtfpl/
.. _me: dado@neseapl.com
.. _FORK: http://202.172.229.26/rhodecode/CakeApns/fork
.. _ApnsPHP: https://github.com/pyodor/ApnsPHP

