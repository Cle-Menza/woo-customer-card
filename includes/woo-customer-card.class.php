<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

class WOO_CUSTOMER_CARD {

  /**
   * WOO_CUSTOMER_CARD constructor.
   */
  public function __construct() {
    $this->admin_email  = get_option( 'admin_email' );
    $this->mail_headers = array(
      'From: ' . get_bloginfo( 'name' ) . ' <' . $this->admin_email . '>',
      'Content-Type: text/html; charset=UTF-8'
    );

    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts_and_styles' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts_and_styles' ) );

    add_action( 'admin_menu', array( $this, 'plugin_setup_menu' ) );

    add_action( 'woocommerce_edit_account_form_start', array( $this, 'add_card_field' ) );
    add_action( 'woocommerce_save_account_details', array( $this, 'save_account_details' ) );
    add_action( 'woocommerce_save_account_details_errors', array( $this, 'account_details_errors' ) );
//    add_filter( 'woocommerce_save_account_details_required_fields', array( $this, 'make_field_required' ) );

    add_action( 'show_user_profile', array( $this, 'show_edit_user_profile' ) );
    add_action( 'edit_user_profile', array( $this, 'show_edit_user_profile' ) );

    add_action( 'edit_user_profile_update', array( $this, 'update_profile_fields' ) );
    add_action( 'personal_options_update', array( $this, 'update_profile_fields' ) );

    add_action( 'woocommerce_cart_calculate_fees', array( $this, 'woocommerce_cart_discount' ) );
  }

  /**
   * Enqueue front-end scripts ans styles
   */
  public function enqueue_public_scripts_and_styles() {
    wp_enqueue_style( WCC_PREFIX . '-main', WCC_URL . 'assets/css/main.css' );
    wp_enqueue_script( WCC_PREFIX . '-main', WCC_URL . 'assets/js/main.js', array( 'jquery' ), '', true );
  }

  /**
   * Enqueue admin-side styles and scripts
   */
  public function enqueue_admin_scripts_and_styles() {
    wp_enqueue_style( WCC_PREFIX . '-admin', WCC_URL . 'assets/css/admin.css' );
  }

  /**
   * Register plugin menu link
   */
  public function plugin_setup_menu() {
    add_submenu_page(
      'woocommerce',
      esc_html__( 'Customers Personal Card', WCC_DOMAIN ),
      esc_html__( 'Customers Card', WCC_DOMAIN ),
      'manage_options',
      'customers-card', array( $this, 'plugin_setting_page' )
    );
  }

  /**
   * Render plugin page
   */
  public function plugin_setting_page() {
    $users = get_users(
      array(
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
    <h1><?php esc_html_e( 'Customers table', WCC_DOMAIN ); ?></h1>

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
        $link                        = get_edit_user_link( $user->ID );
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
  }

  /**
   * Adding customer card field to my account
   */
  public function add_card_field() {
    $approve                = get_the_author_meta( 'customer_card_approve', get_current_user_id() );
    $customer_discount      = get_the_author_meta( 'customer_card_discount', get_current_user_id() );
    $customer_discount_text = $customer_discount ? sprintf( esc_html__( "Your discount is %s%%", WCC_DOMAIN ), $customer_discount ) : '';
    $customer_card          = get_the_author_meta( 'customer_card', get_current_user_id() );
    $approve_text           = '';
    $approve_class          = '';

    if ( $customer_card ) {
      switch ( $approve ) {
        case 2:
//          $approve_text  = esc_html__( 'Card is approve.', WCC_DOMAIN ) . ' ' . esc_html__( 'Your discount is:', WCC_DOMAIN ) . ' ' . $customer_discount . '%';
          $approve_text  = esc_html__( "Card is approve.", WCC_DOMAIN ) . ' ' . $customer_discount_text;
          $approve_class = 'approve';
          break;
        case 1:
          $approve_text  = esc_html__( 'Your card is pending.', WCC_DOMAIN );
          $approve_class = 'pending';
          break;
        case 0:
          $approve_text  = esc_html__( 'Your card is reject.', WCC_DOMAIN );
          $approve_class = 'reject';
          break;
      }
    }

    woocommerce_form_field( 'customer_card',
      array(
        'type'        => 'text',
        'required'    => apply_filters( 'wcc_required_customer_card', false ),
        // remember, this doesn't make the field required, just adds an "*"
        'label'       => esc_html__( 'Card number', WCC_DOMAIN ),
        'description' => $approve_text,
        'class'       => array( $approve_class ),
        'priority'    => 20,
      ),
      $customer_card
    );
  }

  /**
   * Validate customer card number
   * @param $args
   */
  public function account_details_errors( $args ) {
    if ( isset( $_POST['customer_card'] ) ) {
      $users = get_users( array(
        'meta_key'     => 'customer_card',
        'meta_value'   => '',
        'meta_compare' => '>',
        'exclude'      => array( get_current_user_id() )
      ) );

      foreach ( $users as $user_obj ) {
        $customer_card = get_the_author_meta( 'customer_card', $user_obj->ID );
        $approve       = get_the_author_meta( 'customer_card_approve', $user_obj->ID );

        if ( $_POST['customer_card'] == $customer_card && $approve == 2 ) {
          $args->add( 'error', esc_html__( 'This card number is engaged', WCC_DOMAIN ) );
          break;
        }
      }
    }
  }

  /**
   * Save customer card to database and send notification
   * @param $user_id
   */
  public function save_account_details( $user_id ) {
    $send_notification           = false;
    $prev_customer_card          = get_the_author_meta( 'customer_card', $user_id );
    $prev_customer_card_discount = get_the_author_meta( 'customer_card_discount', $user_id );
    $link                        = get_edit_user_link( $user_id );
    $userdata                    = get_userdata( $user_id );

    if ( isset( $_POST['customer_card'] ) ) {
      update_user_meta( $user_id, 'customer_card', sanitize_text_field( $_POST['customer_card'] ) );

      if ( ! empty( $_POST['customer_card'] ) && $_POST['customer_card'] != $prev_customer_card ) {
        $send_notification = true;
      }

      if ( $_POST['customer_card'] != $prev_customer_card ) {
        update_user_meta( $user_id, 'customer_card_approve', 1 ); // 1 is pending
        update_user_meta( $user_id, 'customer_card_discount', 0 ); // reset discount
        update_user_meta( $user_id, 'customer_card_prev', $prev_customer_card );
        update_user_meta( $user_id, 'customer_card_discount_prev', $prev_customer_card_discount );
      }
    }

    if ( $send_notification ) {
      $to      = $this->admin_email;
      $subject = sprintf( __( 'Customer "%s" entered card number "%s"', WCC_DOMAIN ), $userdata->user_login, $_POST["customer_card"] );
      $message = '
        <html>
          <head>
            <title>User ID: ' . $user_id . ' entered card: ' . $_POST["customer_card"] . '</title>
          </head>
          <body>
            <table>
              <tr>
                <th>Login</th>
                <th>Card Number</th>
                <th>Prev. Card</th>
                <th>Edit user</th>
              </tr>
              <tr>
                <td><a href="' . $link . '#customer_card_section" target="_blank">' . $userdata->user_login . '</a></td>
                <td>' . $_POST["customer_card"] . '</td>
                <td>' . $prev_customer_card . '</td>
                <td><a href="' . $link . '#customer_card_section" target="_blank">Edit user</a></td>
              </tr>
            </table>
          </body>
        </html>
      ';

      if ( ! wp_mail( $to, $subject, $message, $this->mail_headers ) ) {
        wc_add_notice( esc_html__( 'Your card saved, but notification to administrator not send.', WCC_DOMAIN ), 'error' );
      }
    }
  }

//  public function make_field_required( $required_fields ) {
//    $required_fields['customer_card'] = __( 'User card is required', WCC_DOMAIN );
//
//    return $required_fields;
//  }

  /**
   * Render user customer card field in user-edit page
   * @param $user
   */
  public function show_edit_user_profile( $user ) {
    $approve                     = get_the_author_meta( 'customer_card_approve', $user->ID );
    $customer_card               = get_the_author_meta( 'customer_card', $user->ID );
    $customer_card_discount      = get_the_author_meta( 'customer_card_discount', $user->ID );
    $customer_card_prev          = get_the_author_meta( 'customer_card_prev', $user->ID );
    $customer_card_discount_prev = get_the_author_meta( 'customer_card_discount_prev', $user->ID );
    ?>
    <h2 id="customer_card_section"><?php esc_html_e( 'Customer Discount Card', WCC_DOMAIN ); ?></h2>
    <table class="form-table">
      <tr>
        <th>
          <label for="customer_card"><?php esc_html_e( 'Discount Card', WCC_DOMAIN ); ?></label>
        </th>
        <td>
          <input class="regular-text" type="text" name="customer_card" id="customer_card"
                 value="<?php echo esc_attr( $customer_card ); ?>">
          <?php
          if ( $customer_card_prev ) {
            echo '<p class="description">' . sprintf( esc_html__( 'Previously card: %s', WCC_DOMAIN ), $customer_card_prev ) . '</p>';
          }
          ?>
        </td>
      </tr>
      <tr>
        <th>
          <label for="customer_card_discount"><?php esc_html_e( 'Card Percent (%)', WCC_DOMAIN ); ?></label>
        </th>
        <td>
          <input class="regular-text" type="text" name="customer_card_discount" id="customer_card_discount"
                 value="<?php echo esc_attr( $customer_card_discount ); ?>">
          <?php
          if ( $customer_card_discount_prev ) {
            echo '<p class="description">' . sprintf( esc_html__( 'Previously discount: %s%%', WCC_DOMAIN ), $customer_card_discount_prev ) . '</p>';
          }
          ?>
        </td>
      </tr>
      <tr>
        <th>
          <label for="customer_card_approve"><?php esc_html_e( 'Card status', WCC_DOMAIN ); ?></label>
        </th>
        <td>
          <div class="card-actions">
            <div class="card-action approve">
              <label><?php esc_html_e( 'Approve', WCC_DOMAIN ); ?>
                <input type="radio" name="customer_card_approve" id="user_card_approve"
                  <?php echo( $approve == 2 ? 'checked="checked"' : '' ); ?> value="2">
              </label>
            </div>
            <div class="card-action pending">
              <label><?php esc_html_e( 'Pending', WCC_DOMAIN ); ?>
                <input type="radio" name="customer_card_approve" id="user_card_pending"
                  <?php echo( $approve == 1 ? 'checked="checked"' : '' ); ?> value="1">
              </label>
            </div>
            <div class="card-action reject">
              <label><?php esc_html_e( 'Reject', WCC_DOMAIN ); ?>
                <input type="radio" name="customer_card_approve" id="user_card_reject"
                  <?php echo( $approve == 0 ? 'checked="checked"' : '' ); ?> value="0">
              </label>
            </div>
          </div>
        </td>
      </tr>
    </table>
    <?php
  }

  /**
   * Save user customer card in admin-side and send notification
   * @param $user_id
   */
  public function update_profile_fields( $user_id ) {
    $approve                = get_the_author_meta( 'customer_card_approve', $user_id );
    $customer_card          = get_the_author_meta( 'customer_card', $user_id );
    $customer_card_discount = get_the_author_meta( 'customer_card_discount', $user_id );
    $user_info              = get_userdata( $user_id );

    if ( current_user_can( 'edit_user', $user_id ) ) {
      update_user_meta( $user_id, 'customer_card_discount', sanitize_text_field( $_POST['customer_card_discount'] ) );
      update_user_meta( $user_id, 'customer_card_approve', sanitize_text_field( $_POST['customer_card_approve'] ) );
    }

    if ( $approve != $_POST['customer_card_approve'] && $_POST['customer_card_approve'] == 2 ) {
      $to      = $user_info->user_email;
      $subject = sprintf( __( 'Your card "%s" approved!', WCC_DOMAIN ), $customer_card );
      $message = '
        <html>
          <head>
            <title>Hello! Dear ' . $user_info->display_name . '!</title>
          </head>
          <body>
            <h1>Great news!</h1>
            <h2>We approved your card</h2>
            <p>Current discount by card ' . $customer_card . ': ' . $customer_card_discount . '%</p>
          </body>
        </html>
      ';

      wp_mail( $to, $subject, $message, $this->mail_headers );
    }
  }

  /**
   * Set discount in woocommerce cart
   * @param $cart
   */
  public function woocommerce_cart_discount( $cart ) {
    $user_id = get_current_user_id();
    $approve = get_the_author_meta( 'customer_card_approve', $user_id );

    if ( $approve != 2 ) {
      return;
    }

    $customer_card          = get_the_author_meta( 'customer_card', $user_id );
    $customer_card_discount = get_the_author_meta( 'customer_card_discount', $user_id );

    $coefficient = intval( $customer_card_discount ) / 100;

    $label    = esc_html__( 'Discount card', WCC_DOMAIN ) . ': ' . $customer_card;
    $discount = 0;

    foreach ( $cart->get_cart() as $hash => $item ) {
      $_item = wc_get_product( $item['product_id'] );
      if ( $_item->is_type( 'simple' ) ) {
        $_product = wc_get_product( $item['product_id'] );
      } elseif ( $_item->is_type( 'variable' ) ) {
        $_product = wc_get_product( $item['variation_id'] );
      } else {
        return;
      }

      $regular_price = $_product->get_regular_price();
      $sale_price    = $_product->get_sale_price();

      if ( ! $_product->is_on_sale() ) {
        $discount += round( ( $regular_price * $item['quantity'] ) * $coefficient );
        // debug
        $debug_discount = round( ( $regular_price * $item['quantity'] ) * $coefficient );
        $cart->add_fee( 'Not on sale - ' . $_product->name, - $debug_discount, false, 'standard' );
      } elseif ( $_product->is_on_sale() ) {
        $product_coefficient = 1 - ( $sale_price / $regular_price );
        if ( $product_coefficient < $coefficient ) {
          $different_coefficient = $coefficient - $product_coefficient;
          $discount              += round( ( $regular_price * $item['quantity'] ) * $different_coefficient );
          // debug
          $debug_discount = round( ( $regular_price * $item['quantity'] ) * $different_coefficient );
          $cart->add_fee( 'On sale and personal discount biggest - ' . $_product->name, - $debug_discount, false, 'standard' );
        } else {
          // debug
          $cart->add_fee( 'On sale, but sale price biggest - ' . $_product->name, 0, false, 'standard' );
        }
      }
    }

    if ( $discount > 0 ) {
      $cart->add_fee( $label, - $discount, false, 'standard' );
    }
  }
}
