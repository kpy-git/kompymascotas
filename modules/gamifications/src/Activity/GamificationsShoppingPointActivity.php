<?php
/**
 * This file is part of the Gamifications module.
 *
 * @author    Sarunas Jonusas, <jonusas.sarunas@gmail.com>
 * @copyright Copyright (c) permanent, Sarunas Jonusas
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;

/**
 * Class GamificationsShoppingPointActivity
 */
class GamificationsShoppingPointActivity
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * GamificationsShoppingPointActivity constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Process order and reward customer
     *
     * @param Order $order
     * @param bool $createObject
     */
    public function processOrder(Order $order, $createObject)
    {
        $shoppingPoint = null;
        if ($createObject) {
            $shoppingPoint = GamificationsShoppingPoint::create($order, true);
        }

        $shoppingPointOrderStates = Configuration::get(GamificationsConfig::SHOPPING_POINTS_ORDER_STATES);
        $shoppingPointOrderStates = json_decode($shoppingPointOrderStates, true);
        $shoppingPointPointsRatio = (int) Configuration::get(GamificationsConfig::SHOPPING_POINTS_RATIO);


        if ((!in_array($order->current_state, $shoppingPointOrderStates) && !empty($shoppingPointOrderStates)) ||
            0 >= $shoppingPointPointsRatio
        ) {
            return;
        }

        // se vuelve a activar el vale al cancelar un pedido que tiene una vale de puntos
        if ($order->current_state === 6 
            && ($idCartRule = Db::getInstance()->getValue("SELECT id_cart_rule 
                FROM ps_pym_gamifications_voucher 
                WHERE id_cart_rule = (select id_cart_rule FROM ps_order_cart_rule WHERE id_order={$order->id})")) !== false)
        {
            Db::getInstance()->update('cart_rule', [
                'active' => 1,
                'quantity' => 1,
            ], 'id_cart_rule=' . $idCartRule);
        }
        

        if (!Validate::isLoadedObject($shoppingPoint)) {
            $idCustomer = (int) $order->id_customer;
            $idOrder = (int) $order->id;
            $idShop = (int) $order->id_shop;

            /** @var GamificationsShoppingPointRepository $shoppingPointRepository */
            $shoppingPointRepository = $this->em->getRepository('GamificationsShoppingPoint');
            $idShoppingPoint =
                $shoppingPointRepository->findShoppingPointIdByCustomerIdAndOrderId($idCustomer, $idOrder, $idShop);
            $shoppingPoint = new GamificationsShoppingPoint($idShoppingPoint);
            if (null === $idShoppingPoint || !Validate::isLoadedObject($shoppingPoint)) {
                return;
            }
        }

        $this->rewardCustomer($shoppingPoint, $order);
    }

    /**
     * Reward customer
     *
     * @param GamificationsShoppingPoint $shoppingPoint
     * @param Order $order
     */
    protected function rewardCustomer(GamificationsShoppingPoint $shoppingPoint, Order $order)
    {
        $shoppingPointPointsRatio = (int) Configuration::get(GamificationsConfig::SHOPPING_POINTS_RATIO);

        $idDefaultCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $defaultCurrency = new Currency($idDefaultCurrency);

        $totalPaid = $order->getTotalPaid($defaultCurrency);

        if (!Configuration::get(GamificationsConfig::SHOPPING_POINTS_INCLUDE_SHIPPNG_PRICE)) {
            $totalPaid = $order->getTotalProductsWithTaxes();
        }

        // si tiene una devolucion (y no está denegada) se le resta al total pagado lo que se ha devuelto
        $totalPaid -= (float) Db::getInstance()->getValue("
            SELECT r.total
            FROM ps_pym_returns r
            WHERE r.id_order={$order->id}
                and not exists(SELECT * from ps_pym_return_history rh where r.id_return = rh.id_return and rh.id_return_state=96)");

        $totalPaid = floor($totalPaid);

        $earnedPoints = $shoppingPointPointsRatio * $totalPaid;


        if (0 >= $earnedPoints) {
            return;
        }

        /** @var GamificationsCustomerRepository $customerRepository */
        $customerRepository = $this->em->getRepository('GamificationsCustomer');
        $idGamificationsCustomer = $customerRepository->findIdByCustomerId($order->id_customer, $order->id_shop);

        if (null === $idGamificationsCustomer) {
            $customer = new Customer($shoppingPoint->id_customer);
            $gamificationsCustomer = GamificationsCustomer::create($customer, true);
        } else {
            $gamificationsCustomer = new GamificationsCustomer($idGamificationsCustomer);
        }

        if ($gamificationsCustomer->addPoints($earnedPoints)) {
            $shoppingPoint->active = false;
            $shoppingPoint->save();

            $reward = new GamificationsReward();
            $reward->reward_type = GamificationsReward::REWARD_TYPE_POINTS;
            $reward->points = $earnedPoints;
            GamificationsActivityHistory::log(
                $reward,
                $shoppingPoint->id_customer,
                GamificationsActivity::TYPE_SHOPPING_POINT
            );
        }
    }

    /**
     * Calculate how many ponts customer will get after placing an order
     *
     * @return int
     */
    public function calculatePossiblePoints()
    {
        $context = Context::getContext();

        $orderTotalPrice = $context->cart->getOrderTotal();

        $includeShippingPrice = (bool) Configuration::get(GamificationsConfig::SHOPPING_POINTS_INCLUDE_SHIPPNG_PRICE);

        if (!$includeShippingPrice) {
            $shippingPrice = $context->cart->getTotalShippingCost();
            $orderTotalPrice -= $shippingPrice;
        }

        $convertedPrice = Tools::convertPrice($orderTotalPrice, $context->currency, false);
        $convertedPrice = floor($convertedPrice);

        $shoppingPointPointsRatio = (int) Configuration::get(GamificationsConfig::SHOPPING_POINTS_RATIO);

        $possiblePoints = $shoppingPointPointsRatio * $convertedPrice;

        return $possiblePoints;
    }

    public function calculateProductPoints(int $id_product, int $id_product_attribute)
    {
        $context                  = Context::getContext();
        //$id_product               = Tools::getValue('id_product');
        //$id_product_attribute     = Tools::getValue('id_product_attribute');
        $product_price            = (float)Product::getPriceStatic($id_product, true, $id_product_attribute, 2);
        $convertedPrice           = Tools::convertPrice($product_price, $context->currency, false);
        $convertedPrice           = floor($convertedPrice);
        $shoppingPointPointsRatio = (int) Configuration::get(GamificationsConfig::SHOPPING_POINTS_RATIO);

        $possiblePoints = $shoppingPointPointsRatio * $convertedPrice;
        //echo $id_product."-".$id_product_attribute."->".$product_price;

        return $possiblePoints;
    }

}
