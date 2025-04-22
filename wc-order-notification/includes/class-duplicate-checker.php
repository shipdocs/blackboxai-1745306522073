<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class WC_Duplicate_Checker
 *
 * Checks for duplicate products in open orders and recent orders.
 */
class WC_Duplicate_Checker {

    /**
     * Time interval in months to check past orders.
     *
     * @var int
     */
    private $months_interval = 3;

    /**
     * Order statuses considered as open.
     *
     * @var array
     */
    private $open_statuses = array( 'pending', 'on-hold', 'processing' );

    /**
     * Constructor.
     *
     * @param int $months_interval Optional. Number of months to look back for recent orders. Default 3.
     */
    public function __construct( $months_interval = 3 ) {
        $this->months_interval = $months_interval;
    }

    /**
     * Check for duplicate products in open or recent orders for a user.
     *
     * @param int   $user_id    User ID.
     * @param array $cart_items Array of WC_Cart_Item objects or array of product IDs.
     *
     * @return array Array of duplicates with product IDs as keys and array of order info as values.
     */
    public function check_duplicates( $user_id, $cart_items ) {
        if ( empty( $user_id ) || empty( $cart_items ) ) {
            return array();
        }

        $duplicates = array();

        // Get product IDs from cart items.
        $product_ids = $this->extract_product_ids( $cart_items );
        if ( empty( $product_ids ) ) {
            return array();
        }

        // Check open orders.
        $open_order_duplicates = $this->check_orders_by_statuses( $user_id, $product_ids, $this->open_statuses );

        // Check recent completed orders.
        $recent_order_duplicates = $this->check_recent_completed_orders( $user_id, $product_ids );

        // Merge duplicates.
        $duplicates = array_merge_recursive( $open_order_duplicates, $recent_order_duplicates );

        return $duplicates;
    }

    /**
     * Extract product IDs from cart items.
     *
     * @param array $cart_items
     *
     * @return array
     */
    private function extract_product_ids( $cart_items ) {
        $product_ids = array();

        foreach ( $cart_items as $item ) {
            if ( is_object( $item ) && isset( $item['product_id'] ) ) {
                $product_ids[] = intval( $item['product_id'] );
            } elseif ( is_array( $item ) && isset( $item['product_id'] ) ) {
                $product_ids[] = intval( $item['product_id'] );
            } elseif ( is_int( $item ) ) {
                $product_ids[] = $item;
            }
        }

        return array_unique( $product_ids );
    }

    /**
     * Check orders by statuses for duplicate products.
     *
     * @param int   $user_id
     * @param array $product_ids
     * @param array $statuses
     *
     * @return array
     */
    private function check_orders_by_statuses( $user_id, $product_ids, $statuses ) {
        $duplicates = array();

        try {
            $args = array(
                'customer_id' => $user_id,
                'status'      => $statuses,
                'limit'       => -1,
                'return'      => 'ids',
            );

            $orders = wc_get_orders( $args );

            if ( empty( $orders ) ) {
                return $duplicates;
            }

            foreach ( $orders as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( ! $order ) {
                    continue;
                }

                foreach ( $order->get_items() as $item ) {
                    $product_id = $item->get_product_id();
                    if ( in_array( $product_id, $product_ids, true ) ) {
                        if ( ! isset( $duplicates[ $product_id ] ) ) {
                            $duplicates[ $product_id ] = array();
                        }
                        $duplicates[ $product_id ][] = array(
                            'order_id'  => $order_id,
                            'order_url' => $order->get_view_order_url(),
                            'status'    => $order->get_status(),
                        );
                    }
                }
            }
        } catch ( Exception $e ) {
            error_log( 'WC_Duplicate_Checker error: ' . $e->getMessage() );
        }

        return $duplicates;
    }

    /**
     * Check recent completed orders within the months interval.
     *
     * @param int   $user_id
     * @param array $product_ids
     *
     * @return array
     */
    private function check_recent_completed_orders( $user_id, $product_ids ) {
        $duplicates = array();

        try {
            $date_after = date( 'Y-m-d H:i:s', strtotime( '-' . intval( $this->months_interval ) . ' months' ) );

            $args = array(
                'customer_id' => $user_id,
                'status'      => 'completed',
                'limit'       => -1,
                'return'      => 'ids',
                'date_after'  => $date_after,
            );

            $orders = wc_get_orders( $args );

            if ( empty( $orders ) ) {
                return $duplicates;
            }

            foreach ( $orders as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( ! $order ) {
                    continue;
                }

                foreach ( $order->get_items() as $item ) {
                    $product_id = $item->get_product_id();
                    if ( in_array( $product_id, $product_ids, true ) ) {
                        if ( ! isset( $duplicates[ $product_id ] ) ) {
                            $duplicates[ $product_id ] = array();
                        }
                        $duplicates[ $product_id ][] = array(
                            'order_id'  => $order_id,
                            'order_url' => $order->get_view_order_url(),
                            'status'    => $order->get_status(),
                        );
                    }
                }
            }
        } catch ( Exception $e ) {
            error_log( 'WC_Duplicate_Checker error: ' . $e->getMessage() );
        }

        return $duplicates;
    }
}
