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
use Paycomet\Payment\Observer\DataAssignObserver;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class PaymentSaveCard implements ResolverInterface
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;


    /**
     * @var \Paycomet\Payment\Logger\Logger
    */
    private $_logger;

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Paycomet\Payment\Logger\Logger $logger

    ) {
        $this->_orderFactory = $orderFactory;
        $this->_logger = $logger;
    }

    /**
    * {@inheritdoc}
    */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if ((!$context->getUserId()) || $context->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                    __('The current customer isn\'t authorized.'
                )
            );
        }

        try {
            if (isset($args['input']['order_id'])) {
                $order_id = $args['input']['order_id'];
                $save_card = $args['input']['save_card'];

                $result['result'] = 0;
                $order = $this->_orderFactory->create()->loadByIncrementId($order_id);

                if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT && $order->getPayment() && $order->getCustomerId() == $context->getUserId() ) {

                    $payment = $order->getPayment();
                    $result = $payment->setAdditionalInformation(DataAssignObserver::PAYCOMET_SAVECARD, $save_card);
                    $order->save();

                    $result['result'] = 1;
                }
                return $result;
            }

        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
    }

}
