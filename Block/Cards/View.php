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
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * View constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param \Magento\Framework\Data\Helper\PostHelper         $postDataHelper
     * @param \Magento\Customer\Model\Session                   $customerSession
     * @param \Paycomet\Payment\Helper\Data                     $helper
     * @param array                                             $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Paycomet\Payment\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_postDataHelper = $postDataHelper;
        $this->_isScopePrivate = true;
        $this->_customerSession = $customerSession;
        $this->_helper = $helper;
    }

    /**
     * Get Iframe Url
     *
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/cards/redirect',
            ['_secure' => true]
        );
    }

    /**
     * Get Integration
     *
     * @return string
     */
    public function getIntegration()
    {
        return $this->_helper->getConfigData('integration');
    }

    /**
     * Get JetId param
     *
     * @return string
     */
    public function getJetId()
    {
        return $this->_helper->getEncryptedConfigData('jetid');
    }

    /**
     * Get add Card
     *
     * @return string
     */
    public function getAddCard()
    {
        $url = $this->_urlBuilder->getUrl('paycomet_payment/cards/add');
        $params = [];

        return $this->_postDataHelper->getPostData($url, $params);
    }

    /**
     * Get update Card
     *
     * @param string $hash
     * @return string
     */
    public function getUpdateParams($hash)
    {
        $url = $this->_urlBuilder->getUrl('paycomet_payment/cards/update');
        $params = ['item' => $hash];

        return $this->_postDataHelper->getPostData($url, $params);
    }

    /**
     * Get remove Card
     *
     * @param string $hash
     * @return string
     */
    public function getRemoveParams($hash)
    {
        $url = $this->_urlBuilder->getUrl('paycomet_payment/cards/remove');
        $params = ['item' => $hash];

        return $this->_postDataHelper->getPostData($url, $params);
    }

    /**
     * Get cards
     *
     * @return array $data
     */
    public function getPaycometCards()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $select = $connection->select()
            ->from(
                ['token' => $resource->getTableName('paycomet_token')],
                ['token_id', 'customer_id', 'hash', 'iduser', 'tokenuser', 'cc', 'brand' , 'expiry' , 'desc']
            )
            ->where('customer_id = ?', $this->_customerSession->getCustomerId());
        $data = $connection->fetchAll($select);
        return $data;
    }
}
