<?php

namespace Paycomet\Payment\Block\Checkout\Onepage;

class Success extends \Magento\Framework\View\Element\Template
{
	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	private $_checkoutSession;

	/**
	 * @var \Magento\Customer\Model\Session
	 */
	private $_customerSession;

	 /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

	/**
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Magento\Checkout\Model\Session                  $checkoutSession
	 * @param \Magento\Customer\Model\Session                  $customerSession
	 * @param array                                            $data
	 */
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Customer\Model\Session $customerSession,
		array $data = []
	) {
		$this->_urlBuilder = $context->getUrlBuilder();
		$this->_checkoutSession = $checkoutSession;
		$this->_customerSession = $customerSession;
		parent::__construct($context, $data);
	}

	/**
	 * Return success message if paycomet payment.
	 *
	 * @return string
	 */
	protected function _toHtml()
	{

		$order = $this->_checkoutSession->getLastRealOrder();
		if (!$order) {
			return '';
		}
		if ($order->getId()) {
			if ($order->getPayment()->getMethodInstance()->getCode() == 'paycomet_payment') {
				$this->addData(
					[
					'is_paycomet' => true,
					]
				);
				return parent::_toHtml();
			}
			if ($order->getPayment()->getMethodInstance()->getCode() == 'paycomet_multibanco') {
				$MethodData = $order->getPayment()->getAdditionalInformation('METHOD_DATA');

				if ($MethodData) {
					$methodData = json_decode($MethodData);

					$code = $order->getPayment()->getMethodInstance()->getCode();
					$title = $order->getPayment()->getMethodInstance()->getTitle();
					$this->addData(
						[
						'code' => $code,
						'title' => $title,
						'img_src' => $this->getViewFileUrl('Paycomet_Payment::img/apms/'.$code . '.svg')
						]
					);

					if ($methodData->entityNumber && $methodData->referenceNumber) {
						$this->addData(
							[
							'Entity' => $methodData->entityNumber,
							'Reference' => $methodData->referenceNumber
							]
						);
					}

				}

				return parent::_toHtml();
			}
		}
		return '';
	}
}
