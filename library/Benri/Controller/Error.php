<?php
/**
 * Provides a plugin for handling exceptions thrown by the application,
 * including those resulting from missing controllers or actions.
 *
 * @link http://framework.zend.com/manual/1.12/en/zend.controller.plugins.html#zend.controller.plugins.standard.errorhandler Zend_Controller_Plugin_ErrorHandler
 */
class Benri_Controller_Error extends Benri_Controller_Action
{
    /**
     * @internal
     */
    public function errorAction()
    {
        $error      = $this->_getParam('error_handler');
        $exception  = $error->exception;
        $request    = $error->request;
        $field      = implode('/', array(
            $request->getParam('module'),
            $request->getParam('controller'),
            $request->getParam('action')
        ));

        switch ($error->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);

                $message  = 'Page not found.';
                $priority = Zend_Log::NOTICE;
                $code     = static::ERROR_MISSING;
                break;

            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);

                $message  = 'Looks like something went wrong!';
                $priority = Zend_Log::CRIT;
                $code     = static::ERROR_UNKNOWN;
                break;
        }

        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log('Exception data', $priority, $error->exception);
            $log->log('Request Parameters', $priority, $error->request->getParams());
        }

        $this->_pushMessage($message, 'danger')
            ->_pushError('controller', $code, $message, $exception->getMessage());

        if ($this->getRequest()->isXMLHttpRequest()) {
            // This will match the response data just like in
            // Benri_Rest_Controller
            $response = array(
                'data'      => null,
                'errors'    => $this->_errors,
                'messages'  => $this->_messages,
            );

            $this->getResponse()
                ->setHeader('Content-Type', 'application/json; charset=utf-8')
                ->setBody(json_encode($response, JSON_NUMERIC_CHECK | JSON_HEX_AMP))
                ->sendResponse();

            exit(0);
        } else {
            if ($this->getInvokeArg('displayExceptions')) {
                // Some huuuuuuge objects
                $this->view->exception = $error->exception;
                $this->view->request   = $error->request;
            }

            $this->getResponse()
                ->setHeader('Content-Type', 'text/html; charset=utf-8');
        }
    }

    /**
     * @internal
     */
    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');

        if (!$bootstrap->hasResource('Log')) {
            return false;
        }

        return $bootstrap->getResource('Log');
    }
}