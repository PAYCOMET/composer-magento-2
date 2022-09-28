<?php
namespace Paycomet\Payment\Plugin;

class Csrf
{
    /**
     * Around Validate
     *
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

        if ($request->getModuleName() == 'paycomet_payment') {
            return;
        }
        $proceed($request, $action);
    }
}
