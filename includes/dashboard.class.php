<?php
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

class WCC_DASHBOARD {
  private $user_data;
  private $user_meta;

  /**
   * WCC_DASHBOARD constructor.
   *
   * @param $user_data
   * @param $user_meta
   */
  public function __construct( $user_data, $user_meta ) {
    $this->user_data = $user_data;
    $this->user_meta = $user_meta;
  }

  /**
   * Gui
   */
  public static function admin_gui() {
    $total_users = count_users();
    $total_users = $total_users['total_users'];
    $users_per_page      = 20;
    $paged       = 1;

    if ( isset( $_GET['paged'] ) ) {
      $paged = $_GET['paged'];
    }

    $users = get_users(
      array(
        'offset'     => $paged ? ( $paged - 1 ) * $users_per_page : 0,
        'number'     => $users_per_page,
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
      )
    );
    ?>
    <h1><?php esc_html_e( 'Customers Card Discount', WCC_DOMAIN ); ?></h1>

    <div class="wrap">
      <h2><?php esc_html_e( 'Customers table', WCC_DOMAIN ); ?></h2>
      <hr class="wp-header-end">
    </div>

    <table class="widefat customers-table">
      <thead>
      <tr>
        <th><?php esc_html_e( 'ID', WCC_DOMAIN ); ?></th>
        <th><?php esc_html_e( 'Login', WCC_DOMAIN ); ?></th>
        <th><?php esc_html_e( 'Role', WCC_DOMAIN ); ?></th>
        <th><?php esc_html_e( 'Card Number', WCC_DOMAIN ); ?></th>
        <th><?php esc_html_e( 'Prev. Card', WCC_DOMAIN ); ?></th>
        <th><?php esc_html_e( 'Discount', WCC_DOMAIN ); ?></th>
        <th><?php esc_html_e( 'Prev. Discount', WCC_DOMAIN ); ?></th>
        <th><?php esc_html_e( 'Approve', WCC_DOMAIN ); ?></th>
        <th><?php esc_html_e( 'Edit user', WCC_DOMAIN ); ?></th>
      </tr>
      </thead>
      <tbody>
      <?php
      foreach ( $users as $user ) {
        $link                        = admin_url( 'user-edit.php?user_id=') . $user->ID;
        $customer_card               = get_the_author_meta( 'customer_card', $user->ID );
        $customer_card_prev          = get_the_author_meta( 'customer_card_prev', $user->ID );
        $customer_card_discount      = get_the_author_meta( 'customer_card_discount', $user->ID );
        $customer_card_discount_prev = get_the_author_meta( 'customer_card_discount_prev', $user->ID );
        $approve                     = get_the_author_meta( 'customer_card_approve', $user->ID );
        ?>
        <tr>
          <td><?php echo $user->ID; ?></td>
          <td><a href="<?php echo $link; ?>#customer_card_section" target="_blank"><?php echo $user->user_login; ?></a>
          </td>
          <td><?php echo implode( ',', $user->roles ); ?></td>
          <td><?php echo $customer_card; ?></td>
          <td><?php echo $customer_card_prev; ?></td>
          <td><?php echo $customer_card_discount ? $customer_card_discount . '%' : ''; ?></td>
          <td><?php echo $customer_card_discount_prev ? $customer_card_discount_prev . '%' : ''; ?></td>
          <td>
            <?php
            switch ( $approve ) {
              case 2:
                echo '<span style="color: green">' . esc_html__( 'Approve', WCC_DOMAIN ) . '</span>';
                break;
              case 1:
                echo '<span style="">' . esc_html__( 'Pending', WCC_DOMAIN ) . '</span>';
                break;
              case 0:
                echo '<span style="color: red">' . esc_html__( 'Reject', WCC_DOMAIN ) . '</span>';
                break;
            }
            ?>
          </td>
          <td><a href="<?php echo $link; ?>#customer_card_section"
                 target="_blank"><?php esc_html_e( 'Edit', WCC_DOMAIN ); ?></a></td>
        </tr>
        <?php
      }
      ?>
      </tbody>
    </table>
    <?php
    if ( $total_users > $users_per_page ) {
      $pl_args = array(
        'base'    => add_query_arg( 'paged', '%#%' ),
        'format'  => '',
        'total'   => ceil( $total_users / $users_per_page ),
        'current' => max( 1, $paged ),
        'type'    => 'list',
        'prev_next' => false,
      );
      echo '<div class="customers-pagination">' . paginate_links( $pl_args ) . '</div>';
    }
  }
}
