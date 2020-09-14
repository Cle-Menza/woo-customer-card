<?php
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

class WCC_IMPORTER {
  private $user_data;
  private $user_meta;

  /**
   * WCC_IMPORTER constructor.
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
    ?>
    <h1><?php esc_html_e( 'Import users', WCC_DOMAIN ); ?></h1>
    <form method="POST" enctype="multipart/form-data" action="" accept-charset="utf-8">
      <table class="form-table">
        <tbody>
        <tr>
          <th>
            <label
              for="uploadfile"><?php _e( 'CSV file <span class="description">(required)</span></label>', WCC_DOMAIN ); ?>
          </th>
          <td>
            <input type="file" name="uploadfile" id="uploadfile" size="35">
          </td>
        </tr>
        <tr>
          <th>
            <?php esc_html_e( 'Import users', WCC_DOMAIN ); ?>
          </th>
          <td>
            <input class="button-primary" type="submit" name="uploadfile_submit" id="uploadfile_submit"
                   value="<?php _e( 'Start importing', WCC_DOMAIN ); ?>">
          </td>
        </tr>
        </tbody>
      </table>
      <?php wp_nonce_field( 'codection-security', 'security' ); ?>
    </form>
    <?php
  }

  /**
   * Import user
   */
  public function import() {
    if ( isset( $_POST['uploadfile_submit'] ) ) {
      $header       = null;
      $log          = array();
      $import_users = array();
      $handler      = fopen( $_FILES['uploadfile']['tmp_name'], 'r+' );

      if ( $handler ) {
        while ( ( $row = fgetcsv( $handler, 8192, ',' ) ) !== false ) {
          if ( ! $header ) {
            $header = $row;
          } else {
            $import_users[] = array_combine( $header, $row );
          }
        }

        fclose( $handler );

        foreach ( $import_users as $row ) {
          $user_id = $row['ID'];
          if ( WCC_HELPER::is_user_exist( $user_id ) ) {

            foreach ( $this->user_meta as $key => $value ) {
              if ( update_user_meta( $user_id, $key, sanitize_text_field( $row[ $key ] ) ) ) {
                $log[] = 'User ' . $user_id . ' updated ' . $value . '. New value ' . $row[ $key ];
              } else {
                if ( get_user_meta( $user_id, $key, true ) != $row[ $key ] ) {
                  $log[] = 'User ' . $user_id . ' not updated ' . $value;
                } else {
                  $log[] = 'User ' . $user_id . ' skipped ' . $value;
                }
              }
            }

          }
        }
      }
      foreach ( $log as $message ) {
        echo '<p>' . $message . '</p>';
      }
    }
  }
}
