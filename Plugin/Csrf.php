<?php
namespace Paytpv\Payment\Plugin;

class Csrf
{
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        
        if ($request->getModuleName() == 'paytpv_payment') {
            return;
        }
        $proceed($request, $action); 
    }
}