<?php
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

class WCC_HELPER {
  /**
   * @param $user_id
   *
   * @return bool
   */
  public static function is_user_exist( $user_id ) {
    if ( $user_id instanceof WP_User ) {
      $user_id = $user_id->ID;
    }

    return (bool) get_user_by( 'id', $user_id );
  }

  /**
   * Get all users by user role
   *
   * @param $role
   *
   * @return array
   */
  public static function get_user_id_list( $role ) {
    $args = array(
      'fields'     => array( 'ID' ),
      'meta_query' => array(
        'relation' => 'OR',
        array(
          'key'     => 'customer_card',
          'value'   => '',
          'compare' => '>'
        ),
        array(
          'key'     => 'customer_card_prev',
          'value'   => '',
          'compare' => '>'
        ),
      )
    );

    if ( ! empty( $role ) ) {
      $args['role'] = $role;
    }

    $users = get_users( $args );
    $list  = array();

    foreach ( $users as $user ) {
      $list[] = $user->ID;
    }

    return $list;
  }
}
