<?php
namespace Paycomet\Payment\Model\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class OfferSave implements ResolverInterface
{
    /**
    * @var ValueFactory
    */
    private $valueFactory;

    /**
     * @var \Paycomet\Payment\Helper\Data
    */
    private $_helper;

    /**
     * @var \Paycomet\Payment\Logger\Logger
    */
    private $_logger;

    /**
     *
     * @param ValueFactory $valueFactory
     */
    public function __construct(
        ValueFactory $valueFactory,
        \Paycomet\Payment\Helper\Data $helper,
        \Paycomet\Payment\Logger\Logger $logger

    ) {
        $this->valueFactory = $valueFactory;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
    * {@inheritdoc}
    */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if ((!$context->getUserId()) || $context->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                    __('The current customer isn\'t authorized.'
                )
            );
        }

        try {
            return $this->_helper->getConfigData('card_offer_save');
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
    }


}
