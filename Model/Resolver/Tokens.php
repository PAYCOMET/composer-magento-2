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
class Tokens implements ResolverInterface
{
    /** @var ResourceConnection  */
    private $resourceConnection;

    /**
    * @var ValueFactory
    */
    private $valueFactory;

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
        ResourceConnection $resourceConnection,
        \Paycomet\Payment\Logger\Logger $logger

    ) {
        $this->valueFactory = $valueFactory;
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
        ?array $value = null,
        ?array $args = null
    ) : Value {
        if ((!$context->getUserId()) || $context->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                    __('The current customer isn\'t authorized.'
                )
            );
        }

        try {
            $arrToken = $this->getPaycometTokens($context->getUserId());

            $result = function () use ($arrToken) {
                return !empty($arrToken) ? $arrToken : [];
            };
            return $this->valueFactory->create($result);

        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
    }

    /**
    *
    * @param int $customerId
    * @return array
    * @throws NoSuchEntityException|LocalizedException
    */
    private function getPaycometTokens($customerId) : array
    {
        try {
            $arrTokens = [];

            $connection = $this->resourceConnection->getConnection();

            $query = $connection->select()
                ->from(
                    ['main_table' => "paycomet_token"],
                    ['iduser','tokenuser','cc','brand','expiry','desc']
                )
                ->where('customer_id = ?', $customerId)
                ->order('date DESC');
            $retTokens = $connection->fetchAll($query);
            if ($retTokens === false) {
                return null;
            }
            foreach ($retTokens as $Token) {

                if (!$Token["desc"]) $Token["desc"] = "";

                $expiry_yyyymm  = str_replace("/","",$Token["expiry"]);
                $today_yyyymm   = date("Ym");
                // Only not expired
                if ($expiry_yyyymm >= $today_yyyymm) {
                    array_push($arrTokens, $Token);
                }

            }

            return isset($arrTokens)?$arrTokens:[];
        } catch (NoSuchEntityException $e) {
           return [];
        } catch (LocalizedException $e) {
            throw new NoSuchEntityException(__($e->getMessage()));
        }
    }


}
