<?php
namespace Paycomet\Payment\Model\Resolver;


use Magento\Framework\App\ResourceConnection;
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
class RemoveUserToken implements ResolverInterface
{
    /** @var ResourceConnection  */
    private $resourceConnection;

    /**
     * @var \Paycomet\Payment\Logger\Logger
    */
    private $_logger;
    /**
     *
     * @param ValueFactory $valueFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        \Paycomet\Payment\Logger\Logger $logger

    ) {
        $this->resourceConnection = $resourceConnection;
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
    ){
        if ((!$context->getUserId()) || $context->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                    __('The current customer isn\'t authorized.'
                )
            );
        }

        $removed = ['result' => false];

        try {
            if (isset($args['iduser'])) {
                $iduser = $args['iduser'];
                if ($iduser > 0) {
                    $removed['result'] = $this->removePaycometToken($context->getUserId(), $iduser);
                }
            }
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        return $removed;
    }

    /**
    *
    * @param int $customerId
    * @param int $iduser
    * @return int
    */
    private function removePaycometToken($customerId, $iduser) : int
    {
        try {

            $connection = $this->resourceConnection->getConnection();

            $result = $connection->delete(
                $this->resourceConnection->getTableName('paycomet_token'),
                ['customer_id = ?' => $customerId, 'iduser = ?' => $iduser]
            );

            return $result;

        } catch (NoSuchEntityException $e) {
           return 0;
        }
    }


}
