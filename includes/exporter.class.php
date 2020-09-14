<?php
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

class WCC_EXPORTER {
  private $path_csv;
  private $user_data;
  private $user_meta;

  /**
   * WCC_EXPORTER constructor.
   *
   * @param $user_data
   * @param $user_meta
   */
  public function __construct( $user_data, $user_meta ) {
    $this->user_data = $user_data;
    $this->user_meta = $user_meta;
    $upload_dir      = wp_upload_dir();
    $this->path_csv  = $upload_dir['basedir'] . '/export-users.csv';

    add_action( 'wp_ajax_wcc_export_users_csv', array( $this, 'export' ) );
  }

  /**
   * Gui
   */
  public static function admin_gui() {
    $roles = get_editable_roles();
    ?>
    <h1><?php esc_html_e( 'Export users', WCC_DOMAIN ); ?></h1>
    <form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="POST" target="_blank"
          enctype="multipart/form-data">
      <table class="form-table">
        <tbody>
        <tr>
          <th><?php esc_html_e( 'Roles', WCC_DOMAIN ); ?></th>
          <td>
            <select name="role">
              <option value=''><?php esc_html_e( 'All roles', WCC_DOMAIN ); ?></option>
              <?php foreach ( $roles as $key => $value ) { ?>
                <option value='<?php echo $key; ?>'><?php echo $value['name']; ?></option>
              <?php } ?>
            </select>
          </td>
        </tr>
        <tr>
          <th><?php esc_html_e( 'Download CSV file with users', WCC_DOMAIN ); ?></th>
          <td>
            <input class="button-primary" type="submit" value="<?php esc_html_e( 'Download', WCC_DOMAIN ); ?>"/>
          </td>
        </tr>
        </tbody>
      </table>
      <input type="hidden" name="action" value="wcc_export_users_csv"/>
      <?php wp_nonce_field( 'codection-security', 'security' ); ?>
    </form>
    <?php
  }

  /**
   * Export user
   */
  public function export() {
    check_ajax_referer( 'codection-security', 'security' );
    if ( ! current_user_can( 'create_users' ) ) {
      wp_die( __( 'Only users who are able to create users can export them.', WCC_DOMAIN ) );
    }

    $role = sanitize_text_field( $_POST['role'] );

    $data = array();
    $row  = array();

    foreach ( $this->user_data as $key => $value ) {
      $row[] = $key;
    }

    $row[] = 'roles';

    foreach ( $this->user_meta as $key => $value ) {
      $row[] = $key;
    }

    $data[] = $row;
    $row    = array();

    $users = WCC_HELPER::get_user_id_list( $role );
    foreach ( $users as $user_id ) {
      $user = get_user_by( 'id', $user_id );

      foreach ( $this->user_data as $key => $value ) {
        $row[] = $user->data->{$key};
      }

      $row[] = implode( ',', $user->roles );

      foreach ( $this->user_meta as $key => $value ) {
        $row[] = get_the_author_meta( $key, $user_id );
      }

      $data[] = $row;
      $row    = array();
    }

    $file = fopen( $this->path_csv, 'w' );
    foreach ( $data as $line ) {
      fputcsv( $file, $line, ',' );
    }

    fclose( $file );

    $fsize      = filesize( $this->path_csv );
    $path_parts = pathinfo( $this->path_csv );
    header( "Content-type: text/csv;charset=utf-8" );
    header( "Content-Disposition: attachment; filename=\"" . $path_parts["basename"] . "\"" );
    header( "Content-length: $fsize" );
    header( "Cache-control: private" );
    header( "Content-Description: File Transfer" );
    header( "Content-Transfer-Encoding: binary" );
    header( "Expires: 0" );
    header( "Cache-Control: must-revalidate" );
    header( "Pragma: public" );

    ob_clean();
    flush();

    readfile( $this->path_csv );
    unlink( $this->path_csv );

    wp_die();
  }

}

