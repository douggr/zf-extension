<?php
/**
 * Used to implement Action Controllers for use with the Front Controller.
 *
 * @link Benri_Controller_Abstract.html Benri_Controller_Abstract
 */
abstract class Benri_Controller_Action extends Benri_Controller_Abstract
{
    /**
     * Layout used by this controller.
     *
     * @var string
     */
    protected $_layout = 'default/layout';

    /**
     * Used to override default templates. If this is set, the controller will
     * ignore controller template and use $_mainTemplate.
     *
     * @var string
     */
    protected $_mainTemplate;

    /**
     * A title for an action.
     *
     * @var string
     */
    protected $_pageTitle = null;

    /**
     * Used to override default templates. If this is set, the controller will
     * ignore controller template and use $_mainTemplate together with
     * $_pjaxTemplate.
     *
     * @var string
     */
    protected $_pjaxTemplate;

    /**
     * Used as the index page.
     *
     * @return void
     */
    public function indexAction()
    {
    }

    /**
     * Initialize object.
     *
     * @return void
     */
    public function init()
    {
        parent:: init();

        $this->_helper
            ->layout
            ->setLayout($this->_layout);
    }

    /**
     * Pre-dispatch routines
     *
     * @return void
     */
    public function preDispatch()
    {
        // allow the programmer to use any partial view located in
        // '/views/scripts/components'.
        $this->view
            ->addScriptPath(APPLICATION_PATH . '/views/scripts/components');
    }

    /**
     * Post-dispatch routines.
     *
     * Common usages for `postDispatch()` include rendering content in a
     * sitewide template, link url correction, setting headers, etc.
     *
     * @return void
     */
    public function postDispatch()
    {
        $request     = $this->getRequest();
        $contentType = 'application/json';

        if ($this->view instanceof Zend_View_Interface) {
            $contentType = 'text/html';

            // Common variables used in all views.
            $this->view
                ->assign(array(
                    'action'        => $this->getParam('action'),
                    'controller'    => $this->getParam('controller'),
                    'errors'        => $this->_errors,
                    'identity'      => Benri_Auth::getInstance()->getIdentity(),
                    'messages'      => $this->_messages,
                    'module'        => $this->getParam('module'),
                    'now'           => new Benri_Util_DateTime(),
                    'pageTitle'     => $this->_pageTitle
                ));

            // XMLHttpRequest requests should not render the entire layout,
            // only the correct templates related to the action.
            if ($request->isXmlHttpRequest()) {
                $this->disableLayout();
            }

            if ($this->_mainTemplate) {
                $this->_helper
                    ->ViewRenderer
                    ->setNoController(true);

                $pjaxTemplate = "{$this->getParam('controller')}/{$this->getParam('action')}";

                if ($request->isPjaxRequest()) {
                    $this->_helper
                        ->viewRenderer($pjaxTemplate);

                } else {
                    $this->view->pjaxTemplate = "{$pjaxTemplate}.phtml";

                    $this->_helper
                        ->viewRenderer($this->_mainTemplate);
                }
            }
        }

        $this->getResponse()
            ->setHeader('Content-Type', "{$contentType}; charset=utf-8", true);
    }
}