<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2009
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 **/

/**
 * 
 *
 * @package Omeka
 * @copyright Center for History and New Media, 2009
 **/
abstract class Omeka_Test_AppTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{   
    protected $_isAdminTest = false;
    
    /**
     * @var boolean Whether the view should attempt to load admin scripts for 
     * testing purposes.  Defaults to true.
     */
    protected $_useAdminViews = true;
    
    public function setUp()
    {
        $this->bootstrap = array($this, 'appBootstrap');
        parent::setUp();
    }

    public function appBootstrap()
    {
        // Must happen before all other bootstrapping.
        if ($this->_isAdminTest) {
            $this->_setupAdminTest();
        }
        
        $this->core = new Omeka_Core('testing', array(
            'config' => CONFIG_DIR . DIRECTORY_SEPARATOR . 'application.ini'));
        
        // No idea why we actually need to add the default routes.
        $this->frontController->getRouter()->addDefaultRoutes();
        $this->frontController->setParam('bootstrap', $this->core->getBootstrap());
        $this->getRequest()->setBaseUrl('');
        $this->setUpBootstrap($this->core->getBootstrap());
        $this->core->bootstrap();
        if ($this->_useAdminViews) {
            $this->_useAdminViews();
        }
    }
    
    public function setUpBootstrap($bootstrap)
    {}

    public function tearDown()
    {
        Zend_Registry::_unsetInstance();
        Omeka_Context::resetInstance();
        parent::tearDown();
    }
    
    /**
     * @internal Overrides the parent behavior to enable automatic throwing of
     * exceptions from dispatching.
     */
    public function dispatch($url = null, $throwExceptions = false)
    {
        parent::dispatch($url);
        if ($throwExceptions) {
            if (isset($this->request->error_handler)) {
                throw $this->request->error_handler->exception;
            }
        }        
    }
    
    /**
     * Increment assertion count
     *
     * @todo COPIED FROM ZEND FRAMEWORK 1.10, REMOVE AFTER UPGRADING TO THAT
     * VERSION.
     * @return void
     */
    protected function _incrementAssertionCount()
    {
        $stack = debug_backtrace();
        foreach (debug_backtrace() as $step) {
            if (isset($step['object'])
                && $step['object'] instanceof PHPUnit_Framework_TestCase
            ) {
                if (version_compare(PHPUnit_Runner_Version::id(), '3.3.0', 'lt')) {
                    break;
                } elseif (version_compare(PHPUnit_Runner_Version::id(), '3.3.3', 'lt')) {
                    $step['object']->incrementAssertionCounter();
                } else {
                    $step['object']->addToAssertionCount(1);
                }
                break;
            }
        }
    }
    
    /**
     * Trick the environment into thinking that a user has been authenticated.
     */
    protected function _authenticateUser(User $user)
    {
        if (!$user->exists()) {
            throw new InvalidArgumentException("User is not persistent in db.");
        }
        $bs = $this->core->getBootstrap();
        $bs->auth->getStorage()->write($user->id);
        $bs->currentUser = $user;
    }
    
    protected function _useAdminViews()
    {
        $this->view = Zend_Registry::get('view');
        $this->view->addScriptPath(ADMIN_THEME_DIR . DIRECTORY_SEPARATOR . 'default');
    }
    
    /**
     * @internal Necessary because admin and public have 2 separate bootstraps.
     */
    private function _setupAdminTest()
    {
        // define('THEME_DIR', ADMIN_DIR . DIRECTORY_SEPARATOR . 'themes');
        $this->frontController->registerPlugin(new Omeka_Controller_Plugin_Admin);
    }
}
