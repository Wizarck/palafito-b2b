<?php

namespace WPDataAccess\Settings {

    use WPDataAccess\Utilities\WPDA_Message_Box;
    use WPDataAccess\Utilities\WPDA_Remote_Call;
    use WPDataAccess\WPDA;

    class WPDA_Settings_Legacy_DataBackup extends WPDA_Settings_Legacy_Page {

        // Dropbox app client id and secret (necessary for registration)
        const DROPBOX_CLIENT_ID     = 'rp1wxasy1irf3wf';
        const DROPBOX_CLIENT_SECRET = 'm9ltsxehdpgepv8';

        public function show() {

            // TEST BACKUP INTERACTIVELY
            // $backup_test = new \WPDataAccess\Backup\WPDA_Data_Export();
            // $backup_test->wpda_data_backup('large');

            if ( isset( $_REQUEST['action'] ) ) {
                $action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // input var okay.

                // Security check.
                $wp_nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : ''; // input var okay.
                if ( ! wp_verify_nonce( $wp_nonce, 'wpda-databackup-settings-' . WPDA::get_current_user_login() ) ) {
                    wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
                }

                if ( 'save' === $action ) {
                    // Save options.
                    $save_local_path = isset( $_REQUEST['local_path'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['local_path'] ) ) : ''; // input var okay.
                    if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
                        if ( '\\' !== substr( $save_local_path, - 1 ) ) {
                            $save_local_path .= '\\';
                        }
                    } else {
                        if ( '/' !== substr( $save_local_path, - 1 ) ) {
                            $save_local_path .= '/';
                        }
                    }
                    WPDA::set_option( WPDA::OPTION_DB_LOCAL_PATH, $save_local_path );

                    $options_activated = array();
                    if ( isset( $_REQUEST['local_path_activated'] ) ) {
                        $error_level = error_reporting();
                        error_reporting( E_ALL ^ E_WARNING );
                        $local_path      = WPDA::get_option( WPDA::OPTION_DB_LOCAL_PATH );
                        $file_permission = fileperms( $local_path );
                        error_reporting( $error_level );
                        if ( $file_permission && '4' === substr( decoct( $file_permission ), 0, 1 ) ) {
                            $options_activated['local_path'] = true;
                        }
                    }

                    // Need to do this first to prevent overwriting.
                    if ( isset( $_REQUEST['dropbox_folder'] ) ) {
                        $dropbox_folder = sanitize_text_field( wp_unslash( $_REQUEST['dropbox_folder'] ) );
                        if ( '/' !== substr( $dropbox_folder, - 1 ) ) {
                            $dropbox_folder .= '/';
                        }

                        WPDA::set_option( WPDA::OPTION_DB_DROPBOX_PATH, $dropbox_folder );
                    }

                    if ( isset( $_REQUEST['dropbox_auth'] ) ) {
                        $dropbox_auth = sanitize_text_field( wp_unslash( $_REQUEST['dropbox_auth'] ) );
                    } else {
                        $dropbox_auth = '';
                    }
                    $dropbox_auth_saved = get_option( 'wpda_db_dropbox_auth' );
                    if ( '' !== $dropbox_auth && $dropbox_auth_saved !== $dropbox_auth ) {
                        $response = WPDA_Remote_Call::post(
                            'https://api.dropboxapi.com/oauth2/token',
                            array(
                                'code'          => $dropbox_auth,
                                'grant_type'    => 'authorization_code',
                                'client_id'     => self::DROPBOX_CLIENT_ID,
                                'client_secret' => self::DROPBOX_CLIENT_SECRET,
                            )
                        );

                        if ( false === $response || ! isset( $response['body'] ) ) {
                            $this->dropbox_authorization_failed();
                        } else {
                            $body_content = json_decode( $response['body'] );
                            if ( isset( $body_content->refresh_token ) ) {
                                update_option( 'wpda_db_dropbox_refresh_token', $body_content->refresh_token );
                                WPDA::set_option( WPDA::OPTION_DB_DROPBOX_PATH );

                                // Remove old token used for outdated dropbox connection.
                                delete_option( 'wpda_db_dropbox_access_token' );
                            } else {
                                $this->dropbox_authorization_failed();
                            }
                        }
                    }
                    update_option( 'wpda_db_dropbox_auth', $dropbox_auth );

                    if ( isset( $_REQUEST['dropbox_activated'] ) ) {
                        $options_activated['dropbox'] = true;
                    }

                    update_option( 'wpda_db_options_activated', $options_activated );
                } elseif ( 'setdefaults' === $action ) {
                    // Set all data backup settings back to default.
                    WPDA::set_option( WPDA::OPTION_DB_LOCAL_PATH );
                    WPDA::set_option( WPDA::OPTION_DB_DROPBOX_PATH );
                    delete_option( 'wpda_db_options_activated' );
                    delete_option( 'wpda_db_dropbox_refresh_token' );
                    delete_option( 'wpda_db_dropbox_auth' );

                    // Remove old token used for outdated dropbox connection.
                    delete_option( 'wpda_db_dropbox_access_token' );
                }

                $msg = new WPDA_Message_Box(
                    array(
                        'message_text' => __( 'Settings saved', 'wp-data-access' ),
                    )
                );
                $msg->box();
            }

            $error_level = error_reporting();
            error_reporting( E_ALL ^ E_WARNING );
            $local_path      = WPDA::get_option( WPDA::OPTION_DB_LOCAL_PATH );
            $file_permission = fileperms( $local_path );
            error_reporting( $error_level );

            $owner_info  = ( ( $file_permission & 0x0100 ) ? 'r' : '-' );
            $owner_info .= ( ( $file_permission & 0x0080 ) ? 'w' : '-' );
            $owner_info .= ( ( $file_permission & 0x0040 ) ?
                ( ( $file_permission & 0x0800 ) ? 's' : 'x' ) :
                ( ( $file_permission & 0x0800 ) ? 'S' : '-' ) );
            $group_info  = ( ( $file_permission & 0x0020 ) ? 'r' : '-' );
            $group_info .= ( ( $file_permission & 0x0010 ) ? 'w' : '-' );
            $group_info .= ( ( $file_permission & 0x0008 ) ?
                ( ( $file_permission & 0x0400 ) ? 's' : 'x' ) :
                ( ( $file_permission & 0x0400 ) ? 'S' : '-' ) );
            $world_info  = ( ( $file_permission & 0x0004 ) ? 'r' : '-' );
            $world_info .= ( ( $file_permission & 0x0002 ) ? 'w' : '-' );
            $world_info .= ( ( $file_permission & 0x0001 ) ?
                ( ( $file_permission & 0x0200 ) ? 't' : 'x' ) :
                ( ( $file_permission & 0x0200 ) ? 'T' : '-' ) );

            $dropbox_auth   = get_option( 'wpda_db_dropbox_auth' );
            $dropbox_folder = WPDA::get_option( WPDA::OPTION_DB_DROPBOX_PATH );

            $options_activated = get_option( 'wpda_db_options_activated' );
            ?>

            <form id="wpda_settings_databackup" method="post"
                  action="?page=<?php echo esc_attr( $this->page ); ?>&tab=legacy&vtab=databackup">
                <table class="wpda-table-settings">
                    <tr style="border-top: 1px solid #ccc">
                        <th>
                            <?php echo __( 'Local file system' ); ?>
                            <br/><br/>
                            <label style="font-weight: normal">
                                <input type="checkbox"
                                       name="local_path_activated"
                                    <?php
                                    if ( isset( $options_activated['local_path'] ) ) {
                                        echo 'checked';
                                    }
                                    ?>
                                /> <?php echo __( 'Activate', 'wp-data-access' ); ?>
                            </label>
                        </th>
                        <td>
                            <?php echo __( 'Enter the name of the folder where data backup files should be stored.' ); ?>
                            <br/>
                            <input type="text" name="local_path" value="<?php echo esc_attr( $local_path ); ?>"/>
                            <span><?php echo __( 'Make sure the folder exists with permission to write files.' ); ?></span>
                            <?php
                            if ( 'WIN' !== strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
                                if ( ! $file_permission ) {
                                    echo '<br/><br/>';
                                    echo __( 'ERROR: Invalid folder', 'wp-data-access' );
                                } else {
                                    if ( '4' !== substr( decoct( $file_permission ), 0, 1 ) ) {
                                        echo '<br/><br/>';
                                        echo __( 'ERROR: Not a folder', 'wp-data-access' );
                                    } else {
                                        $fileowner  = fileowner( $local_path );
                                        $groupowner = filegroup( $local_path );
                                        ?>
                                        <br/><br/>
                                        {
                                        <?php echo __( '"Permission"' ); ?>:
                                        {
                                        <?php echo __( '"owner"' ); ?>:
                                        {
                                        <?php echo __( '"name"' ); ?>: "<?php echo esc_attr( posix_getpwuid( $fileowner )['name'] ); ?>",
                                        <?php echo __( '"access"' ); ?>: "<?php echo esc_attr( $owner_info ); ?>"
                                        },
                                        <?php echo __( '"group"' ); ?>:
                                        {
                                        <?php echo __( '"name"' ); ?>: "<?php echo esc_attr( posix_getpwuid( $groupowner )['name'] ); ?>",
                                        <?php echo __( '"access"' ); ?>: "<?php echo esc_attr( $group_info ); ?>"
                                        },
                                        <?php echo __( '"world"' ); ?>:
                                        {
                                        <?php echo __( '"access"' ); ?>: "<?php echo esc_attr( $world_info ); ?>"
                                        }
                                        }
                                        }
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php echo __( 'Dropbox' ); ?>
                            <br/><br/>
                            <label style="font-weight: normal">
                                <input type="checkbox"
                                       name="dropbox_activated"
                                    <?php
                                    if ( isset( $options_activated['dropbox'] ) ) {
                                        echo 'checked';
                                    }
                                    ?>
                                /> <?php echo __( 'Activate', 'wp-data-access' ); ?>
                            </label>
                        </th>
                        <td>
                            <a href="https://www.dropbox.com/" class="button button-secondary" target="_blank">
                                <?php echo __( 'Create a Dropbox account' ); ?>
                            </a>
                            <span style="vertical-align:-webkit-baseline-middle;">
								<?php echo __( 'You can skip this step if you already have an account.' ); ?>
							</span>
                            <br/><br/>
                            <?php echo __( 'Authorize the WP Data Access Dropbox app and enter the authorization code in the text box below.' ); ?>
                            <br/>
                            <input type="text" name="dropbox_auth" value="<?php echo esc_attr( $dropbox_auth ); ?>"/>
                            <a href="https://www.dropbox.com/oauth2/authorize?client_id=<?php echo esc_attr( self::DROPBOX_CLIENT_ID ); ?>&response_type=code&token_access_type=offline"
                               class="button button-secondary"
                               target="_blank"
                               style="vertical-align:bottom;">
                                <?php echo __( 'Get Dropbox authorization code' ); ?>
                            </a>
                            <?php
                            if ( '' !== $dropbox_folder ) {
                                // Only older versions are using this option.
                                ?>
                                <br/><br/>
                                <?php echo __( 'Enter the name of the folder where data backup files should be stored. If the folder doesn\'t exists, it\'ll be created for you.' ); ?>
                                <br/>
                                <input type="text" name="dropbox_folder" value="<?php echo esc_attr( $dropbox_folder ); ?>"/>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <div class="wpda-table-settings-button">
                    <input type="hidden" name="action" value="save"/>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-check wpda_icon_on_button"></i>
                        <?php echo __( 'Save Data Backup Settings', 'wp-data-access' ); ?>
                    </button>
                    <a href="javascript:void(0)"
                       onclick="if (confirm('<?php echo __( 'Reset to defaults?', 'wp-data-access' ); ?>')) {
                           jQuery('input[name=&quot;action&quot;]').val('setdefaults');
                           jQuery('#wpda_settings_databackup').trigger('submit')
                           }"
                       class="button">
                        <i class="fas fa-times-circle wpda_icon_on_button"></i>
                        <?php echo __( 'Reset Data Backup To Defaults', 'wp-data-access' ); ?>
                    </a>
                </div>
                <?php wp_nonce_field( 'wpda-databackup-settings-' . WPDA::get_current_user_login(), '_wpnonce', false ); ?>
            </form>
            <?php

        }

        private function dropbox_authorization_failed() {

            $msg = new WPDA_Message_Box(
                array(
                    'message_text' => __( 'Dropbox authorization failed ', 'wp-data-access' ),
                    'message_type' => 'error',
                    'message_is_dismissible' => false,
                )
            );
            $msg->box();

        }

        public static function dropbox_get_token() {

            $refresh_token = get_option( 'wpda_db_dropbox_refresh_token' );
            if ( false === $refresh_token ) {
                return false;
            }

            $response = WPDA_Remote_Call::post(
                'https://api.dropboxapi.com/oauth2/token',
                array(
                    'refresh_token' => $refresh_token,
                    'grant_type'    => 'refresh_token',
                    'client_id'     => self::DROPBOX_CLIENT_ID,
                    'client_secret' => self::DROPBOX_CLIENT_SECRET,
                )
            );

            if ( ! isset( $response['body'] ) ) {
                return false;
            } else {
                $body_content = json_decode( $response['body'] );
                if ( ! isset( $body_content->access_token ) ) {
                    return false;
                } else {
                    return $body_content->access_token;
                }
            }

        }

    }

}