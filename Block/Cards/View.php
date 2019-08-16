<?php

namespace Paycomet\Payment\Block\Cards;

class View extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;


    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $_postDataHelper;

    /**
     * View constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_postDataHelper = $postDataHelper;
        $this->_isScopePrivate = true;
        $this->_customerSession = $customerSession;
    }

    /**
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/cards/redirect',
            ['_secure' => true]
        );
    }


    public function getUpdateParams($hash)
    {
        $url = $this->_urlBuilder->getUrl('paycomet_payment/cards/update');
        $params = ['item' => $hash];
        
        return $this->_postDataHelper->getPostData($url, $params);
    }

    public function getRemoveParams($hash)
    {
        $url = $this->_urlBuilder->getUrl('paycomet_payment/cards/remove');
        $params = ['item' => $hash];
        
        return $this->_postDataHelper->getPostData($url, $params);
    }


   
    public function getPaycometCards()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $select = $connection->select()
            ->from(
                ['token' => 'paycomet_token'],
                ['token_id', 'customer_id', 'hash', 'iduser', 'tokenuser', 'cc', 'brand' , 'expiry' , 'desc']
            )
            ->where('customer_id = ?', $this->_customerSession->getCustomerId());
        $data = $connection->fetchAll($select);
        return $data;
    }

}
